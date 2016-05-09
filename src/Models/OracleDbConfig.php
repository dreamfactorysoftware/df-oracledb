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
        return 'oci';
    }

    public static function getDefaultDsn()
    {
        // http://php.net/manual/en/ref.pdo-oci.connection.php
        return
            'oci:dbname=(DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.1.1)(PORT = 1521))) (CONNECT_DATA = (SID = db)))';
    }
}