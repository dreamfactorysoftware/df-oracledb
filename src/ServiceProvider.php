<?php

namespace DreamFactory\Core\Oracle;

use DreamFactory\Core\Components\DbSchemaExtensions;
use DreamFactory\Core\Enums\LicenseLevel;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Oracle\Database\Connectors\OracleConnector;
use DreamFactory\Core\Oracle\Database\OracleConnection;
use DreamFactory\Core\Oracle\Database\Schema\OracleSchema;
use DreamFactory\Core\Oracle\Models\OracleDbConfig;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\Oracle\Services\OracleDb;
use Illuminate\Database\DatabaseManager;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our database drivers.
        $this->app->resolving('db', function (DatabaseManager $db) {
            $db->extend('oracle', function ($config) {
                $connector = new OracleConnector();
                $connection = $connector->connect($config);
                $oracleConnection = new OracleConnection($connection, $config["database"], $config["prefix"], $config);

                // Registering via extend() bypasses yajra's Connection::resolverFor('oracle'),
                // which is where laravel-oci8 normally applies its NLS session settings.
                // Without these, the session uses Oracle defaults (NLS_DATE_FORMAT='DD-MON-RR'),
                // so ISO 8601 date/datetime strings fail implicit conversion (ORA-01861).
                if (empty($config['skip_session_vars'])) {
                    $sessionVars = [
                        'NLS_TIME_FORMAT'         => 'HH24:MI:SS',
                        'NLS_DATE_FORMAT'         => 'YYYY-MM-DD HH24:MI:SS',
                        'NLS_TIMESTAMP_FORMAT'    => 'YYYY-MM-DD HH24:MI:SS',
                        'NLS_TIMESTAMP_TZ_FORMAT' => 'YYYY-MM-DD HH24:MI:SS TZH:TZM',
                        'NLS_NUMERIC_CHARACTERS'  => '.,',
                    ];
                    // Unlike yajra's resolver, do NOT map $config['schema'] to CURRENT_SCHEMA:
                    // DreamFactory's service config uses 'schema' for schema filtering
                    // (including the literal value 'default'), not an Oracle schema name.
                    if (isset($config['session'])) {
                        $sessionVars = array_merge($sessionVars, $config['session']);
                    }
                    $oracleConnection->setSessionVars($sessionVars);
                }

                return $oracleConnection;
            });
        });

        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType([
                    'name'                  => 'oracle',
                    'label'                 => 'Oracle',
                    'description'           => 'Database service supporting SQL connections.',
                    'group'                 => ServiceTypeGroups::DATABASE,
                    'subscription_required' => LicenseLevel::SILVER,
                    'config_handler'        => OracleDbConfig::class,
                    'factory'               => function ($config) {
                        return new OracleDb($config);
                    },
                ])
            );
        });

        // Add our database extensions.
        $this->app->resolving('df.db.schema', function (DbSchemaExtensions $db) {
            $db->extend('oracle', function ($connection) {
                return new OracleSchema($connection);
            });
        });
    }
}
