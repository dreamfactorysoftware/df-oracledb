<?php

namespace DreamFactory\Core\Oracle\Resources;

use DB;
use DreamFactory\Core\Database\Schema\ColumnSchema;
use DreamFactory\Core\Database\Schema\RelationSchema;
use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Enums\DbSimpleTypes;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Exceptions\RestException;
use DreamFactory\Core\SqlDb\Resources\Table;
use DreamFactory\Library\Utility\Scalar;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;


/**
 * Class Table
 *
 * @package DreamFactory\Core\Oracle\Resources
 */
class OracleTable extends Table
{
    protected function runQuery($table, $fields, Builder $builder, $extras)
    {
        $schema = $this->getTableSchema(null, $table);
        if (!$schema) {
            throw new NotFoundException("Table '$table' does not exist in the database.");
        }

        $order = trim(array_get($extras, ApiOptions::ORDER));
        $group = trim(array_get($extras, ApiOptions::GROUP));
        $limit = intval(array_get($extras, ApiOptions::LIMIT, 0));
        $offset = intval(array_get($extras, ApiOptions::OFFSET, 0));
        $countOnly = Scalar::boolval(array_get($extras, ApiOptions::COUNT_ONLY));
        $includeCount = Scalar::boolval(array_get($extras, ApiOptions::INCLUDE_COUNT));

        $maxAllowed = static::getMaxRecordsReturnedLimit();
        $needLimit = false;
        if (($limit < 1) || ($limit > $maxAllowed)) {
            // impose a limit to protect server
            $limit = $maxAllowed;
            $needLimit = true;
        }

        // count total records
        $count = ($countOnly || $includeCount || $needLimit) ? $builder->count() : 0;

        if ($countOnly) {
            return $count;
        }

        $fields = static::fieldsToArray($fields);
        /** @type ColumnSchema[] $availableFields */
        $availableFields = $schema->getColumns(true);
        /** @type RelationSchema[] $availableRelations */
        $availableRelations = $schema->getRelations(true);
        $related = array_get($extras, ApiOptions::RELATED);
        $nestedFields = $this->getNestedFields($fields, $availableFields);
        $idFields = (empty($schema->primaryKey)) ? (array)array_get($extras, 'id_fields') : (array)$schema->primaryKey;

        // see if we need to add anymore fields to select for related retrieval
        if (!empty($fields)) {
            if (!empty($availableRelations) && (!empty($related) || $schema->fetchRequiresRelations)) {
                foreach ($availableRelations as $relation) {
                    if (false === array_search($relation->field, $fields)) {
                        $fields[] = $relation->field;
                    }
                }
            }
            if (!empty($nestedFields)) {
                // nested queries need something to merge back with
                $fields = array_unique(array_merge($fields, (array)$idFields));
            }
        }
        $select = $this->parseSelect($fields, $availableFields);

        // apply the selected fields
        $builder->select($select);

        // apply the rest of the parameters
        if (!empty($order)) {
            if (false !== strpos($order, ';')) {
                throw new BadRequestException('Invalid order by clause in request.');
            }
            $commas = explode(',', $order);
            switch (count($commas)) {
                case 0:
                    break;
                case 1:
                    $spaces = explode(' ', $commas[0]);
                    $orderField = $spaces[0];
                    $direction = (isset($spaces[1]) ? $spaces[1] : 'asc');
                    $builder->orderBy($orderField, $direction);
                    break;
                default:
                    // todo need to validate format here first
                    $builder->orderByRaw($order);
                    break;
            }
        }
        if (!empty($group)) {
            $group = static::fieldsToArray($group);
            if (false !== strpos($group, ';')) {
                throw new BadRequestException('Invalid group by clause in request.');
            }
            $groups = $this->parseGroupBy($group, $availableFields);
            $builder->groupBy($groups);
        }
        $builder->take($limit);
        $builder->skip($offset);

        $result = $builder->get();

        $result->transform(function ($item) use ($availableFields) {
            $item = (array)$item;
            foreach ($item as $field => &$value) {
                if (!is_null($value) && ($fieldInfo = array_get($availableFields, strtolower($field)))) {
                    $value = $this->schema->formatValue($value, $fieldInfo->phpType);
                }
            }

            return $item;
        });

        if (!empty($result)) {
            if (!empty($related) || $schema->fetchRequiresRelations) {
                if (!empty($availableRelations)) {
                    // until this is refactored to collections
                    $data = $result->toArray();
                    $this->retrieveRelatedRecords($schema, $availableRelations, $related, $data);
                    $result = collect($data);
                }
            }

            if (!empty($nestedFields)) {
                $this->retrieveNestedRecords($nestedFields, $builder, $idFields, $availableFields, $result);
            }
        }

        $meta = [];
        if ($includeCount || $needLimit) {
            if ($includeCount || $count > $maxAllowed) {
                $meta['count'] = $count;
            }
            if (($count - $offset) > $limit) {
                $meta['next'] = $offset + $limit;
            }
        }

        if (Scalar::boolval(array_get($extras, ApiOptions::INCLUDE_SCHEMA))) {
            try {
                $meta['schema'] = $schema->toArray(true);
            } catch (RestException $ex) {
                throw $ex;
            } catch (\Exception $ex) {
                throw new InternalServerErrorException("Error describing database table '$table'.\n" .
                    $ex->getMessage(), $ex->getCode());
            }
        }

        $data = $result->toArray();
        if (!empty($meta)) {
            $data['meta'] = $meta;
        }

        return $data;
    }

    /**
     * @param array      $nestedFields
     * @param Builder    $builder
     * @param array      $idFields
     * @param array      $availableFields
     * @param Collection $result
     */
    protected function retrieveNestedRecords($nestedFields, Builder $builder, $idFields, $availableFields, $result)
    {
        $from = $builder->from;
        $select = $this->parseSelect($idFields, $availableFields);
        foreach ($nestedFields as $field) {
            $nest = $field->internalName;
            $nestedResults = $builder->select(array_merge($select, [DB::raw('t2.*')]))
                ->from(DB::raw("$from t1, TABLE(t1.$nest) t2"))
                ->get();
            if (1 == count($idFields)) {
                $idField = reset($idFields);
                // much easier, group and back-fill into matching data
                $nestedResults = $nestedResults->groupBy($idField);
                $result->transform(function ($item) use ($nest, $idField, $nestedResults) {
                    $id = array_get($item, $idField);
                    $item[$nest] = $nestedResults->get($id, []);

                    return $item;
                });
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function parseSelect(array $fields, $avail_fields)
    {
        // Need to parse out non-retrievable nested table fields
        $outArray = [];
        if (empty($fields)) {
            foreach ($avail_fields as $fieldInfo) {
                if ($fieldInfo->isAggregate || (DbSimpleTypes::TYPE_TABLE === $fieldInfo->type)) {
                    continue;
                }
                $outArray[] = $this->parseFieldForSelect($fieldInfo);
            }
        } else {
            foreach ($fields as $field) {
                $ndx = strtolower($field);
                if (!isset($avail_fields[$ndx])) {
                    throw new BadRequestException('Invalid field requested: ' . $field);
                }

                $fieldInfo = $avail_fields[$ndx];
                if (DbSimpleTypes::TYPE_TABLE !== $fieldInfo->type) {
                    $outArray[] = $this->parseFieldForSelect($fieldInfo);
                }
            }
        }

        return $outArray;
    }

    /**
     * @param  array          $fields
     * @param  ColumnSchema[] $avail_fields
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \Exception
     */
    protected function getNestedFields(array $fields, $avail_fields)
    {
        $outArray = [];
        if (empty($fields)) {
            foreach ($avail_fields as $fieldInfo) {
                if (DbSimpleTypes::TYPE_TABLE === $fieldInfo->type) {
                    $outArray[] = $fieldInfo;
                }
            }
        } else {
            foreach ($fields as $field) {
                if ($fieldInfo = array_get($avail_fields, strtolower($field))) {
                    if (DbSimpleTypes::TYPE_TABLE === $fieldInfo->type) {
                        $outArray[] = $fieldInfo;
                    }
                }
            }
        }

        return $outArray;
    }
}