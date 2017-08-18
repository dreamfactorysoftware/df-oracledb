<?php

namespace DreamFactory\Core\Oracle\Services;

use DreamFactory\Core\Oracle\Resources\OracleTable;
use DreamFactory\Core\SqlDb\Services\SqlDb;
use DreamFactory\Core\Enums\DbResourceTypes;
use DreamFactory\Core\SqlDb\Resources\Schema;
use DreamFactory\Core\SqlDb\Resources\StoredFunction;
use DreamFactory\Core\SqlDb\Resources\StoredProcedure;
use DreamFactory\Core\SqlDb\Resources\Table;

/**
 * Class OracleDb
 *
 * @package DreamFactory\Core\SqlDb\Services
 */
class OracleDb extends SqlDb
{
    protected static $resources = [
        Schema::RESOURCE_NAME          => [
            'name'       => Schema::RESOURCE_NAME,
            'class_name' => Schema::class,
            'label'      => 'Schema',
        ],
        Table::RESOURCE_NAME           => [
            'name'       => Table::RESOURCE_NAME,
            'class_name' => OracleTable::class,
            'label'      => 'Tables',
        ],
        StoredProcedure::RESOURCE_NAME => [
            'name'       => StoredProcedure::RESOURCE_NAME,
            'class_name' => StoredProcedure::class,
            'label'      => 'Stored Procedures',
        ],
        StoredFunction::RESOURCE_NAME  => [
            'name'       => StoredFunction::RESOURCE_NAME,
            'class_name' => StoredFunction::class,
            'label'      => 'Stored Functions',
        ],
    ];

    public function __construct($settings = [])
    {
        parent::__construct($settings);

        $prefix = parent::getConfigBasedCachePrefix();
        if ($service = array_get($this->config, 'service_name')) {
            $prefix = $service . $prefix;
        }
        if ($tns = array_get($this->config, 'tns')) {
            $prefix = $tns . $prefix;
        }
        $this->setConfigBasedCachePrefix($prefix);
    }

    public static function adaptConfig(array &$config)
    {
        $config['driver'] = 'oracle';
        parent::adaptConfig($config);
    }
}