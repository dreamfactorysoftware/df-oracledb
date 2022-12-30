<?php
namespace DreamFactory\Core\Oracle\Models;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\SqlDb\Models\SqlDbConfig;
use Arr;

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

    public function validate($data, $throwException = true)
    {
        $connection = $this->getAttribute('connection');
        if (!empty(Arr::get($connection, 'tns'))) {
            return true; // overrides everything else
        }

        if (empty(Arr::get($connection, 'host'))) {
            throw new BadRequestException("If not using TNS, connection information must contain host name.");
        }

        if (empty(Arr::get($connection, 'database')) && empty(Arr::get($connection, 'service_name'))) {
            throw new BadRequestException("If not using TNS, connection information must contain either database (SID) or service_name (SERVICE_NAME).");
        }

        return true;
    }

    protected function getConnectionFields()
    {
        $fields = parent::getConnectionFields();

        return array_merge($fields, ['service_name', 'tns', 'protocol', 'charset']);
    }

    public static function getDefaultConnectionInfo()
    {
        $defaults = parent::getDefaultConnectionInfo();
        $defaults[] = [
            'name'        => 'service_name',
            'label'       => 'Service Name',
            'type'        => 'string',
            'description' => 'Optional service name if database (i.e. SID) is not set.'
        ];
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

        return $defaults;
    }
}