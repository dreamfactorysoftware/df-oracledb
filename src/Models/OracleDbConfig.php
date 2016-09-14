<?php
namespace DreamFactory\Core\Oracle\Models;

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

    protected function getConnectionFields()
    {
        $fields = parent::getConnectionFields();

        return array_merge($fields, ['charset', 'tns', 'protocol', 'service_name']);
    }

    public static function getDefaultConnectionInfo()
    {
        $defaults = parent::getDefaultConnectionInfo();
        $defaults[] = [
            'name'        => 'tns',
            'label'       => 'TNS Full Connection String',
            'type'        => 'string',
            'description' => 'Overrides all other settings.'
        ];
        $defaults[] = [
            'name'        => 'protocol',
            'label'       => 'Connection Protocol',
            'type'        => 'string',
            'description' => 'Defaults to TCP.'
        ];
        $defaults[] = [
            'name'        => 'charset',
            'label'       => 'Character Set',
            'type'        => 'string',
            'description' => 'The character set to use for this connection, i.e. ' . static::getDefaultCharset()
        ];
        $defaults[] = [
            'name'        => 'service_name',
            'label'       => 'Service Name',
            'type'        => 'string',
            'description' => 'Optional service name if database (i.e. SID) is not set.'
        ];

        return $defaults;
    }
}