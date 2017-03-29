<?php
namespace DreamFactory\Core\Oracle\Models;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\SqlDb\Models\SqlDbConfig;

/**
 * OracleDbConfig
 *
 */
class OracleDbConfig extends SqlDbConfig
{
    public static function getDriverName()
    {
        return 'oracle';
    }

    public static function getDefaultPort()
    {
        return 1521;
    }

    public static function getDefaultCharset()
    {
        return 'AL32UTF8';
    }

    public static function validateConfig($config, $create = true)
    {
        if (!empty(array_get($config, 'tns'))) {
            return true; // overrides everything else
        }

        if ($create) {
            if (empty(array_get($config, 'host'))) {
                throw new BadRequestException("If not using TNS, connection information must contain host name.");
            }

            if (empty(array_get($config, 'database')) && empty(array_get($config, 'service_name'))) {
                throw new BadRequestException("If not using TNS, connection information must contain either database (SID) or service_name (SERVICE_NAME).");
            }
        }

        return true;
    }

    public static function getSchema()
    {
        $schema = parent::getSchema();
        $extras = [
            [
                'name'        => 'tns',
                'label'       => 'TNS Full Connection String',
                'type'        => 'string',
                'description' => 'Overrides all other settings.'
            ],
            [
                'name'        => 'service_name',
                'label'       => 'Service Name',
                'type'        => 'string',
                'description' => 'Optional service name if database (i.e. SID) is not set.'
            ],
            [
                'name'        => 'protocol',
                'label'       => 'Connection Protocol',
                'type'        => 'string',
                'description' => 'Defaults to TCP.'
            ],
            [
                'name'        => 'charset',
                'label'       => 'Character Set',
                'type'        => 'string',
                'description' => 'The character set to use for this connection, i.e. ' . static::getDefaultCharset()
            ]
        ];

        $pos = array_search('options', array_keys($schema));
        $front = array_slice($schema, 0, $pos, true);
        $end = array_slice($schema, $pos, null, true);

        return array_merge($front, $extras, $end);
    }
}