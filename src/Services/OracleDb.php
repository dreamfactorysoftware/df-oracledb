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
    public static function adaptConfig(array &$config)
    {
        $config['driver'] = 'oracle';
        parent::adaptConfig($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getResources($only_handlers = false)
    {
        $types = $this->getSchema()->getSupportedResourceTypes();
        $resources = [
            Schema::RESOURCE_NAME => [
                'name'       => Schema::RESOURCE_NAME,
                'class_name' => Schema::class,
                'label'      => 'Schema',
            ],
            Table::RESOURCE_NAME  => [
                'name'       => Table::RESOURCE_NAME,
                'class_name' => OracleTable::class,
                'label'      => 'Tables',
            ]
        ];
        if (in_array(DbResourceTypes::TYPE_PROCEDURE, $types)) {
            $resources[StoredProcedure::RESOURCE_NAME] = [
                'name'       => StoredProcedure::RESOURCE_NAME,
                'class_name' => StoredProcedure::class,
                'label'      => 'Stored Procedures',
            ];
        }
        if (in_array(DbResourceTypes::TYPE_FUNCTION, $types)) {
            $resources[StoredFunction::RESOURCE_NAME] = [
                'name'       => StoredFunction::RESOURCE_NAME,
                'class_name' => StoredFunction::class,
                'label'      => 'Stored Functions',
            ];
        }

        return ($only_handlers) ? $resources : array_values($resources);
    }
}