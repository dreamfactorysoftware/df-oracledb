<?php

namespace DreamFactory\Core\Oracle\Services;

use DreamFactory\Core\SqlDb\Services\SqlDb;

/**
 * Class OracleDb
 *
 * @package DreamFactory\Core\SqlDb\Services
 */
class OracleDb extends SqlDb
{
    public static function adaptConfig(array &$config)
    {
        $config['driver'] = 'oracle';
        parent::adaptConfig($config);
    }
}