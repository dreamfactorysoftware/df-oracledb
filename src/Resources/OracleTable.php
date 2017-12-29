<?php

namespace DreamFactory\Core\Oracle\Resources;

use DB;
use DreamFactory\Core\Database\Schema\TableSchema;
use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Enums\DbSimpleTypes;
use DreamFactory\Core\SqlDb\Resources\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;


/**
 * Class Table
 *
 * @package DreamFactory\Core\Oracle\Resources
 */
class OracleTable extends Table
{
    protected function getQueryResults(TableSchema $schema, Builder $builder, $extras)
    {
        $fields = array_get($extras, ApiOptions::FIELDS);
        $nestedFields = [];
        $otherFields = [];
        if (!empty($fields)) {
            if (ApiOptions::FIELDS_ALL === $fields) {
                foreach ($schema->getColumns(true) as $fieldInfo) {
                    switch ($fieldInfo->type) {
                        case DbSimpleTypes::TYPE_ARRAY:
                        case DbSimpleTypes::TYPE_TABLE:
                            $nestedFields[$fieldInfo->getName(true)] = $fieldInfo;
                            break;
                        default:
                            $otherFields[$fieldInfo->getName(true)] = $fieldInfo;
                            break;
                    }
                }
            } else {
                $fields = static::fieldsToArray($fields);
                foreach ($fields as $field) {
                    if ($fieldInfo = $schema->getColumn($field, true)) {
                        switch ($fieldInfo->type) {
                            case DbSimpleTypes::TYPE_ARRAY:
                            case DbSimpleTypes::TYPE_TABLE:
                                $nestedFields[$fieldInfo->getName(true)] = $fieldInfo;
                                break;
                            default:
                                $otherFields[$fieldInfo->getName(true)] = $fieldInfo;
                                break;
                        }
                    }
                }
            }
        }

        if (!empty($nestedFields)) {
            $from = $builder->from . ' pt1';
            foreach ($nestedFields as $field) {
                $from .= ", TABLE(pt1.{$field->internalName}) {$field->name}_t";
            }

            $result = $builder->from(DB::raw($from))->get();

            $result->transform(function ($item) use ($schema) {
                $item = (array)$item;
                foreach ($item as $field => &$value) {
                    if ($fieldInfo = $schema->getColumn($field, true)) {
                        $value = $this->parent->getSchema()->typecastToClient($value, $fieldInfo);
                    }
                }

                return $item;
            });

            $idFields = array_get($extras, ApiOptions::ID_FIELD);
            if (empty($idFields)) {
                $idFields = $schema->getPrimaryKey();
            }
            $idFields = static::fieldsToArray($idFields);
            if (1 == count($idFields)) {
                $idField = reset($idFields);
                // much easier, group and back-fill into matching data
                $nestedResults = $result->groupBy($idField);
                $result = [];
                /** @var Collection $group */
                foreach ($nestedResults as $group) {
                    $record = array_only($group->first(), array_keys($otherFields));
                    foreach ($nestedFields as $nestedField => $nestedFieldInfo) {
                        $nestedTableFields = array_keys((array)array_get($nestedFieldInfo->native, 'nested_columns'));
                        $nestedOutput = [];
                        foreach ($group as $member) {
                            $nestedRecord = [];
                            foreach ($nestedTableFields as $nestedTableField) {
                                $nestedRecord[$nestedTableField] = array_get($member,
                                    $nestedField . '_' . $nestedTableField, array_get($member, $nestedTableField));
                            }
                            $nestedOutput[] = $nestedRecord;
                        }
                        $record[$nestedField] = $nestedOutput;
                    }
                    $result[] = $record;
                }
            }

            return collect($result);
        }

        return parent::getQueryResults($schema, $builder, $extras);
    }

    /**
     * @inheritdoc
     */
    protected function parseFieldForSelect($field)
    {
        switch ($field->type) {
            case DbSimpleTypes::TYPE_TABLE:
                if (!empty($nestedTableFields = array_get($field->native, 'nested_columns'))) {
                    $nestedOutput = [];
                    foreach (array_keys($nestedTableFields) as $member) {
                        $nestedOutput[] = DB::raw($field->name . "_t.$member AS {$field->name}_$member");
                    }

                    return $nestedOutput;
                }

                return DB::raw($field->name . '_t.*');
            case DbSimpleTypes::TYPE_ARRAY:
                return DB::raw($field->name . '_t.*');
            default:
                return parent::parseFieldForSelect($field);
        }
    }
}