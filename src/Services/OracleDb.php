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
        $dsn = isset($config['dsn']) ? $config['dsn'] : null;
        if (!empty($dsn)) {
            // default PDO DSN pieces
            $dsn = str_replace(' ', '', $dsn);
            // traditional connection string uses (), reset find
            if (!isset($config['host']) && (false !== ($pos = stripos($dsn, 'host=')))) {
                $temp = substr($dsn, $pos + 5);
                $config['host'] = (false !== $pos = stripos($temp, ')')) ? substr($temp, 0, $pos) : $temp;
            }
            if (!isset($config['port']) && (false !== ($pos = stripos($dsn, 'port=')))) {
                $temp = substr($dsn, $pos + 5);
                $config['port'] = (false !== $pos = stripos($temp, ')')) ? substr($temp, 0, $pos) : $temp;
            }
            if (!isset($config['database']) && (false !== ($pos = stripos($dsn, 'sid=')))) {
                $temp = substr($dsn, $pos + 4);
                $config['database'] = (false !== $pos = stripos($temp, ')')) ? substr($temp, 0, $pos) : $temp;
            }
        }

        if (!isset($config['collation'])) {
            $config['collation'] = 'utf8_unicode_ci';
        }

        // must be there
        if (!array_key_exists('database', $config)) {
            $config['database'] = null;
        }

        // must be there
        if (!array_key_exists('prefix', $config)) {
            $config['prefix'] = null;
        }

        // laravel database config requires options to be [], not null
        if (array_key_exists('options', $config) && is_null($config['options'])) {
            $config['options'] = [];
        }
    }
}