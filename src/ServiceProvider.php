<?php
namespace DreamFactory\Core\Oracle;

use DreamFactory\Core\Database\DbSchemaExtensions;
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
                $connector  = new OracleConnector();
                $connection = $connector->connect($config);
                return new OracleConnection($connection, $config["database"], $config["prefix"], $config);
            });
        });

        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df){
            $df->addType(
                new ServiceType([
                    'name'           => 'oracle',
                    'label'          => 'Oracle',
                    'description'    => 'Database service supporting SQL connections.',
                    'group'          => ServiceTypeGroups::DATABASE,
                    'config_handler' => OracleDbConfig::class,
                    'factory'        => function ($config){
                        return new OracleDb($config);
                    },
                ])
            );
        });

        // Add our database extensions.
        $this->app->resolving('db.schema', function (DbSchemaExtensions $db){
            $db->extend('oracle', function ($connection){
                return new OracleSchema($connection);
            });
        });
    }
}
