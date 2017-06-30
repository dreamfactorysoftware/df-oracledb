<?php
namespace DreamFactory\Core\Oracle\Database\Schema;

use DreamFactory\Core\Database\Schema\ColumnSchema;
use DreamFactory\Core\Database\Schema\FunctionSchema;
use DreamFactory\Core\Database\Schema\ParameterSchema;
use DreamFactory\Core\Database\Schema\ProcedureSchema;
use DreamFactory\Core\Database\Schema\RoutineSchema;
use DreamFactory\Core\Database\Schema\TableSchema;
use DreamFactory\Core\Enums\DbSimpleTypes;
use DreamFactory\Core\SqlDb\Database\Schema\SqlSchema;

/**
 * Schema is the class for retrieving metadata information from an Oracle database.
 */
class OracleSchema extends SqlSchema
{
    /**
     * Default fetch mode, base class uses NAMED which OCI8 does not support
     */
    const ROUTINE_FETCH_MODE = \PDO::FETCH_ASSOC;

    /**
     * @var array the abstract column types mapped to physical column types.
     */
    public $columnTypes = [
        // no autoincrement, requires sequences and optionally triggers or client input
        'pk' => 'NUMBER(10) NOT NULL PRIMARY KEY',
        // new no sequence identity setting from 12c
        //        'pk' => 'NUMBER GENERATED ALWAYS AS IDENTITY',
    ];

    protected function translateSimpleColumnTypes(array &$info)
    {
        // override this in each schema class
        $type = (isset($info['type'])) ? $info['type'] : null;
        switch ($type) {
            // some types need massaging, some need other required properties
            case 'pk':
            case DbSimpleTypes::TYPE_ID:
                $info['type'] = 'number';
                $info['type_extras'] = '(10)';
                $info['allow_null'] = false;
                $info['auto_increment'] = false;
                $info['is_primary_key'] = true;
                break;

            case 'fk':
            case DbSimpleTypes::TYPE_REF:
                $info['type'] = 'number';
                $info['type_extras'] = '(10)';
                $info['is_foreign_key'] = true;
                // check foreign tables
                break;

            case DbSimpleTypes::TYPE_TIMESTAMP_ON_CREATE:
            case DbSimpleTypes::TYPE_TIMESTAMP_ON_UPDATE:
                $info['type'] = 'timestamp';
                $default = (isset($info['default'])) ? $info['default'] : null;
                if (!isset($default)) {
                    $default = 'CURRENT_TIMESTAMP';
                    // ON UPDATE CURRENT_TIMESTAMP not supported by Oracle, use triggers
                    $info['default'] = $default;
                }
                break;

            case DbSimpleTypes::TYPE_USER_ID:
            case DbSimpleTypes::TYPE_USER_ID_ON_CREATE:
            case DbSimpleTypes::TYPE_USER_ID_ON_UPDATE:
                $info['type'] = 'number';
                $info['type_extras'] = '(10)';
                break;

            case DbSimpleTypes::TYPE_INTEGER:
                $info['type'] = 'number';
                $info['type_extras'] = '(10)';
                break;
            case DbSimpleTypes::TYPE_FLOAT:
                $info['type'] = 'BINARY_FLOAT';
                break;
            case DbSimpleTypes::TYPE_DOUBLE:
                $info['type'] = 'BINARY_DOUBLE';
                break;
            case DbSimpleTypes::TYPE_DECIMAL:
                $info['type'] = 'NUMBER';
                break;
            case DbSimpleTypes::TYPE_DATETIME:
            case DbSimpleTypes::TYPE_TIME:
                $info['type'] = 'TIMESTAMP';
                break;

            case DbSimpleTypes::TYPE_BOOLEAN:
                $info['type'] = 'number';
                $info['type_extras'] = '(1)';
                $default = (isset($info['default'])) ? $info['default'] : null;
                if (isset($default)) {
                    // convert to bit 0 or 1, where necessary
                    $info['default'] = (int)filter_var($default, FILTER_VALIDATE_BOOLEAN);
                }
                break;

            case DbSimpleTypes::TYPE_MONEY:
                $info['type'] = 'number';
                $info['type_extras'] = '(19,4)';
                $default = (isset($info['default'])) ? $info['default'] : null;
                if (isset($default)) {
                    $info['default'] = floatval($default);
                }
                break;

            case DbSimpleTypes::TYPE_STRING:
                $fixed =
                    (isset($info['fixed_length'])) ? filter_var($info['fixed_length'], FILTER_VALIDATE_BOOLEAN) : false;
                $national =
                    (isset($info['supports_multibyte'])) ? filter_var($info['supports_multibyte'],
                        FILTER_VALIDATE_BOOLEAN) : false;
                if ($fixed) {
                    $info['type'] = ($national) ? 'nchar' : 'char';
                } elseif ($national) {
                    $info['type'] = 'nvarchar2';
                } else {
                    $info['type'] = 'varchar2';
                }
                break;

            case DbSimpleTypes::TYPE_TEXT:
                $national =
                    (isset($info['supports_multibyte'])) ? filter_var($info['supports_multibyte'],
                        FILTER_VALIDATE_BOOLEAN) : false;
                if ($national) {
                    $info['type'] = 'nclob';
                } else {
                    $info['type'] = 'clob';
                }
                break;

            case DbSimpleTypes::TYPE_BINARY:
                $fixed =
                    (isset($info['fixed_length'])) ? filter_var($info['fixed_length'], FILTER_VALIDATE_BOOLEAN) : false;
                $info['type'] = ($fixed) ? 'blob' : 'varbinary';
                break;
        }
    }

    protected function validateColumnSettings(array &$info)
    {
        // override this in each schema class
        $type = (isset($info['type'])) ? $info['type'] : null;
        switch ($type) {
            // some types need massaging, some need other required properties
            case 'numeric':
            case 'binary_float':
            case 'binary_double':
                if (!isset($info['type_extras'])) {
                    $length =
                        (isset($info['length']))
                            ? $info['length']
                            : ((isset($info['precision'])) ? $info['precision']
                            : null);
                    if (!empty($length)) {
                        $scale =
                            (isset($info['decimals']))
                                ? $info['decimals']
                                : ((isset($info['scale'])) ? $info['scale']
                                : null);
                        $info['type_extras'] = (!empty($scale)) ? "($length,$scale)" : "($length)";
                    }
                }

                $default = (isset($info['default'])) ? $info['default'] : null;
                if (isset($default) && is_numeric($default)) {
                    $info['default'] = floatval($default);
                }
                break;

            case 'char':
            case 'nchar':
                $length = (isset($info['length'])) ? $info['length'] : ((isset($info['size'])) ? $info['size'] : null);
                if (isset($length)) {
                    $info['type_extras'] = "($length)";
                }
                break;

            case 'varchar':
            case 'varchar2':
            case 'nvarchar':
            case 'nvarchar2':
                $length = (isset($info['length'])) ? $info['length'] : ((isset($info['size'])) ? $info['size'] : null);
                if (isset($length)) {
                    $info['type_extras'] = "($length)";
                } else // requires a max length
                {
                    $info['type_extras'] = '(' . static::DEFAULT_STRING_MAX_SIZE . ')';
                }
                break;

            case 'timestamp':
                $length = (isset($info['length'])) ? $info['length'] : ((isset($info['size'])) ? $info['size'] : null);
                if (isset($length)) {
                    $info['type_extras'] = "($length)";
                }
                break;
        }
    }

    /**
     * @param array $info
     *
     * @return string
     * @throws \Exception
     */
    protected function buildColumnDefinition(array $info)
    {
        $type = (isset($info['type'])) ? $info['type'] : null;
        $typeExtras = (isset($info['type_extras'])) ? $info['type_extras'] : null;

        $definition = $type . $typeExtras;

        $default = (isset($info['default'])) ? $info['default'] : null;
        if (isset($default)) {
            $quoteDefault =
                (isset($info['quote_default'])) ? filter_var($info['quote_default'], FILTER_VALIDATE_BOOLEAN) : false;
            if ($quoteDefault) {
                $default = "'" . $default . "'";
            }

            $definition .= ' DEFAULT ' . $default;
        }

        $isUniqueKey = (isset($info['is_unique'])) ? filter_var($info['is_unique'], FILTER_VALIDATE_BOOLEAN) : false;
        $isPrimaryKey =
            (isset($info['is_primary_key'])) ? filter_var($info['is_primary_key'], FILTER_VALIDATE_BOOLEAN) : false;
        if ($isPrimaryKey && $isUniqueKey) {
            throw new \Exception('Unique and Primary designations not allowed simultaneously.');
        }
        if ($isUniqueKey) {
            $definition .= ' UNIQUE';
        } elseif ($isPrimaryKey) {
            $definition .= ' PRIMARY KEY';
        }

        $allowNull = (isset($info['allow_null'])) ? filter_var($info['allow_null'], FILTER_VALIDATE_BOOLEAN) : false;
        $definition .= ($allowNull) ? ' NULL' : ' NOT NULL';

        return $definition;
    }

    /**
     * @param boolean $refresh if we need to refresh schema cache.
     *
     * @return string default schema.
     */
    public function getDefaultSchema($refresh = false)
    {
        return strtoupper($this->getUserName());
    }

    /**
     * @param string $table table name with optional schema name prefix, uses default schema name prefix is not
     *                      provided.
     *
     * @return array tuple as ($schemaName,$tableName)
     */
    protected function getSchemaTableName($table)
    {
        $table = strtoupper($table);
        if (count($parts = explode('.', str_replace('"', '', $table))) > 1) {
            return [$parts[0], $parts[1]];
        } else {
            return [$this->getDefaultSchema(), $parts[0]];
        }
    }

    /**
     * @inheritdoc
     */
    protected function findColumns(TableSchema $table)
    {
//        $params = [$table->resourceName, $table->schemaName];
        $sql = <<<EOD
SELECT a.column_name, a.data_type, a.data_precision, a.data_scale, a.data_length, a.nullable, a.data_default,
    (   SELECT D.constraint_type
        FROM ALL_CONS_COLUMNS C
        inner join ALL_constraints D on D.OWNER = C.OWNER and D.constraint_name = C.constraint_name
        WHERE C.OWNER = B.OWNER
           and C.table_name = B.object_name
           and C.column_name = A.column_name
           and D.constraint_type = 'P') as Key,
    com.comments as column_comment,
    ct.coll_type AS collection_type, nt.table_name AS nested_table_name, nt.owner AS nested_table_owner
FROM ALL_TAB_COLUMNS A
INNER JOIN ALL_OBJECTS B ON b.owner = a.owner and ltrim(B.OBJECT_NAME) = ltrim(A.TABLE_NAME)
LEFT JOIN user_col_comments com ON (A.table_name = com.table_name AND A.column_name = com.column_name)
LEFT JOIN all_coll_types ct ON (A.data_type = ct.type_name AND A.data_type_owner = ct.owner)
LEFT JOIN all_nested_tables nt ON (ct.type_name = nt.table_type_name AND ct.owner = nt.table_type_owner)
WHERE a.owner = '{$table->schemaName}' and b.object_name = '{$table->resourceName}' and (b.object_type = 'TABLE' or b.object_type = 'VIEW')
ORDER by a.column_id
EOD;

        if (!empty($columns = $this->connection->select($sql))) {
            $sql = <<<EOD
SELECT trigger_body FROM ALL_TRIGGERS
WHERE table_owner = '{$table->schemaName}' and table_name = '{$table->resourceName}'
and triggering_event = 'INSERT' and status = 'ENABLED' and trigger_type = 'BEFORE EACH ROW'
EOD;

            $trig = $this->connection->select($sql);
            if (!empty($trig[0])) {
                $row = array_change_key_case((array)$trig[0], CASE_LOWER);
                foreach ($columns as &$column) {
                    $column = array_change_key_case((array)$column, CASE_LOWER);
                    if ('P' === array_get($column, 'key')) {
                        $column['auto_increment'] = true;
                        $seq = stristr(array_get($row, 'trigger_body', ''), '.nextval', true);
                        $seq = substr($seq, strrpos($seq, ' ') + 1);
                        $column['sequence'] = $seq;
                    }
                }
            }
        }

        return $columns;
    }

    /**
     * Creates a table column.
     *
     * @param array $column column metadata
     *
     * @return ColumnSchema normalized column metadata
     */
    protected function createColumn($column)
    {
        $c = new ColumnSchema(['name' => $column['column_name']]);
        $c->autoIncrement = array_get($column, 'auto_increment', false);
        $c->quotedName = $this->quoteColumnName($c->name);
        $c->allowNull = $column['nullable'] === 'Y';
        $c->isPrimaryKey = strpos(strval(array_get($column,'key')), 'P') !== false;
        $c->dbType = $column['data_type'];
        $c->precision = intval($column['data_precision']);
        $c->scale = intval($column['data_scale']);
        // all of this is for consistency across drivers
        if ($c->precision > 0) {
            if ($c->scale <= 0) {
                $c->size = $c->precision;
                $c->scale = null;
            }
        } else {
            $c->precision = null;
            $c->scale = null;
            $c->size = intval($column['data_length']);
            if ($c->size <= 0) {
                $c->size = null;
            }
        }
        $this->extractLimit($c, $c->dbType);
        $c->fixedLength = $this->extractFixedLength($c->dbType);
        $c->supportsMultibyte = $this->extractMultiByteSupport($c->dbType);
        $collectionType = array_get($column, 'collection_type');
        switch (strtolower($collectionType)) {
            case 'table':
                $this->extractType($c, 'table');
                $nestedTable = array_get($column, 'nested_table_name');
                $nestedTableOwner = array_get($column, 'nested_table_owner');
                $sql = <<<EOD
SELECT column_name, data_type, data_precision, data_scale, data_length, nullable, data_default
FROM ALL_NESTED_TABLE_COLS
WHERE owner = '$nestedTableOwner' and table_name = '$nestedTable'
and HIDDEN_COLUMN = 'NO'
EOD;

                $result = $this->connection->select($sql);
                $nestedColumns = [];
                foreach ($result as $nestedColumn) {
                    $nestedColumn = array_change_key_case((array)$nestedColumn, CASE_LOWER);
                    $nc = $this->createColumn($nestedColumn);
                    $nestedColumns[$nc->name] = $nc->toArray();
                }
                $c->native = ['nested_columns' => $nestedColumns];
                break;
            case 'varying array':
                $this->extractType($c, 'array');
                break;
            default:
                $this->extractType($c, $c->dbType);
                break;
        }
        $this->extractDefault($c, $column['data_default']);
        $c->comment = array_get($column, 'column_comment', '');

        return $c;
    }

    /**
     * @inheritdoc
     */
    protected function findTableReferences()
    {
        $sql = <<<EOD
		SELECT D.constraint_type, C.position, D.r_constraint_name,
            C.owner as table_schema,
            C.table_name as table_name,
		    C.column_name as column_name,
            E.owner as referenced_table_schema,
            E.table_name as referenced_table_name,
            F.column_name as referenced_column_name
        FROM ALL_CONS_COLUMNS C
        inner join ALL_constraints D on D.OWNER = C.OWNER and D.constraint_name = C.constraint_name
        left join ALL_constraints E on E.OWNER = D.r_OWNER and E.constraint_name = D.r_constraint_name
        left join ALL_cons_columns F on F.OWNER = E.OWNER and F.constraint_name = E.constraint_name and F.position = C.position
        WHERE D.constraint_type = 'R'
        ORDER BY D.constraint_name, C.position
EOD;

        return $this->connection->select($sql);
    }

    protected function findSchemaNames()
    {
        if ('SYSTEM' == $this->getDefaultSchema()) {
            $sql = 'SELECT username FROM all_users';
        } else {
            $sql = <<<SQL
SELECT username FROM all_users WHERE username not in ('SYSTEM','SYS','SYSAUX')
SQL;
        }

        return $this->selectColumn($sql);
    }

    /**
     * @inheritdoc
     */
    protected function findTableNames($schema = '')
    {
        $sql = <<<EOD
SELECT table_name, owner as table_schema FROM all_tables WHERE nested = 'NO'
EOD;

        if (!empty($schema)) {
            $sql .= " AND owner = '$schema'";
        }

        $defaultSchema = $this->getNamingSchema();

        $rows = $this->connection->select($sql);
        $addSchema = (!empty($schema) && ($defaultSchema !== $schema));

        $names = [];
        foreach ($rows as $row) {
            $row = array_change_key_case((array)$row, CASE_UPPER);
            $schemaName = array_get($row, 'TABLE_SCHEMA', '');
            $resourceName = array_get($row, 'TABLE_NAME', '');
            $internalName = $schemaName . '.' . $resourceName;
            $name = ($addSchema) ? $internalName : $resourceName;
            $quotedName = $this->quoteTableName($schemaName) . '.' . $this->quoteTableName($resourceName);;
            $settings = compact('schemaName', 'resourceName', 'name', 'internalName', 'quotedName');
            $names[strtolower($name)] = new TableSchema($settings);
        }

        return $names;
    }

    /**
     * @inheritdoc
     */
    protected function findViewNames($schema = '')
    {
        $sql = <<<EOD
SELECT object_name as table_name, owner as table_schema FROM all_objects WHERE object_type = 'VIEW'
EOD;

        if (!empty($schema)) {
            $sql .= " AND owner = '$schema'";
        }

        $defaultSchema = $this->getNamingSchema();

        $rows = $this->connection->select($sql);
        $addSchema = (!empty($schema) && ($defaultSchema !== $schema));

        $names = [];
        foreach ($rows as $row) {
            $row = array_change_key_case((array)$row, CASE_UPPER);
            $schemaName = array_get($row, 'TABLE_SCHEMA', '');
            $resourceName = array_get($row, 'TABLE_NAME', '');
            $internalName = $schemaName . '.' . $resourceName;
            $name = ($addSchema) ? $internalName : $resourceName;
            $quotedName = $this->quoteTableName($schemaName) . '.' . $this->quoteTableName($resourceName);;
            $settings = compact('schemaName', 'resourceName', 'name', 'internalName', 'quotedName');
            $settings['isView'] = true;
            $names[strtolower($name)] = new TableSchema($settings);
        }

        return $names;
    }

    /**
     * @inheritdoc
     */
    protected function findRoutineNames($type, $schema = '')
    {
        $bindings = [':type' => $type];
        $where = 'OBJECT_TYPE = :type';
        if (!empty($schema)) {
            $where .= ' AND OWNER = :schema';
            $bindings[':schema'] = $schema;
        }

        $sql = <<<MYSQL
SELECT OBJECT_NAME, PROCEDURE_NAME FROM all_procedures WHERE {$where}
MYSQL;

        $rows = $this->connection->select($sql, $bindings);

        // Package support
        $bindings = [];
        $where = '';
        if (!empty($schema)) {
            $where .= ' AND p.OWNER = :schema';
            $bindings[':schema'] = $schema;
        }
        $argCheck = ('FUNCTION' === $type) ? 'a.ARGUMENT_NAME IS NULL' : 'NOT (a.ARGUMENT_NAME IS NULL)';

        $sql = <<<MYSQL
SELECT DISTINCT p.OBJECT_NAME, p.PROCEDURE_NAME FROM all_procedures p
JOIN all_arguments a ON (a.PACKAGE_NAME = p.OBJECT_NAME OR a.OBJECT_NAME = p.PROCEDURE_NAME) AND a.OWNER = p.OWNER AND a.DATA_LEVEL = '0' AND {$argCheck}
WHERE p.OBJECT_TYPE = 'PACKAGE' AND p.PROCEDURE_NAME IS NOT NULL {$where}
MYSQL;

        $rows2 = $this->connection->select($sql, $bindings);
        $rows = array_merge($rows, $rows2);

        $defaultSchema = $this->getNamingSchema();
        $addSchema = (!empty($schema) && ($defaultSchema !== $schema));

        $names = [];
        foreach ($rows as $row) {
            $row = array_change_key_case((array)$row, CASE_UPPER);
            $resourceName = array_get($row, 'OBJECT_NAME');
            $schemaName = $schema;
            $internalName = $schemaName . '.' . $resourceName;
            $name = ($addSchema) ? $internalName : $resourceName;
            $quotedName = $this->quoteTableName($schemaName) . '.' . $this->quoteTableName($resourceName);
            if (!empty($addPackage = array_get($row, 'PROCEDURE_NAME'))) {
                $resourceName .= '.' . $addPackage;
                $name .= '.' . $addPackage;
                $internalName .= '.' . $addPackage;
                $quotedName .= '.' . $this->quoteTableName($addPackage);
            }
            $settings = compact('schemaName', 'resourceName', 'name', 'quotedName', 'internalName');
            $names[strtolower($name)] =
                ('PROCEDURE' === $type) ? new ProcedureSchema($settings) : new FunctionSchema($settings);
        }

        return $names;
    }

    /**
     * @inheritdoc
     */
    protected function loadParameters(RoutineSchema $holder)
    {
        $sql = <<<MYSQL
SELECT argument_name, position, sequence, data_type, in_out, data_length, data_precision, data_scale, 
 default_value, data_level, char_length
FROM all_arguments
WHERE OBJECT_NAME = :object AND OWNER = :schema AND DATA_LEVEL = '0'
MYSQL;

        $bindings = [':object' => $holder->name, ':schema' => $holder->schemaName];
        if (false !== $pos = strpos($holder->name, '.')) {
            $sql .= ' AND PACKAGE_NAME = :package';
            $bindings[':object'] = substr($holder->name, $pos + 1);
            $bindings[':package'] = substr($holder->name, 0, $pos);
        }

        $rows = $this->connection->select($sql, $bindings);
        foreach ($rows as $row) {
            $row = array_change_key_case((array)$row, CASE_UPPER);
            $name = array_get($row, 'ARGUMENT_NAME');
            $pos = intval(array_get($row, 'POSITION'));
            $simpleType = static::extractSimpleType(array_get($row, 'DATA_TYPE'));
            if ((0 === $pos) || is_null($name)) {
                $holder->returnType = $simpleType;
            } else {
                $holder->addParameter(new ParameterSchema([
                        'name'          => $name,
                        'position'      => $pos,
                        'param_type'    => str_replace('/', '', array_get($row, 'IN_OUT')),
                        'type'          => $simpleType,
                        'db_type'       => array_get($row, 'DATA_TYPE'),
                        'length'        => (isset($row['DATA_LENGTH']) ? intval(array_get($row, 'DATA_LENGTH')) : null),
                        'precision'     => (isset($row['DATA_PRECISION']) ? intval(array_get($row, 'DATA_PRECISION'))
                            : null),
                        'scale'         => (isset($row['DATA_SCALE']) ? intval(array_get($row, 'DATA_SCALE')) : null),
                        'default_value' => array_get($row, 'DEFAULT_VALUE'),
                    ]
                ));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function renameTable($table, $newName)
    {
        return <<<MYSQL
ALTER TABLE {$this->quoteTableName($table)} RENAME TO {$this->quoteTableName($newName)};
MYSQL;
    }

    /**
     * @inheritdoc
     */
    public function alterColumn($table, $column, $definition)
    {
        return <<<MYSQL
ALTER TABLE $table MODIFY {$this->quoteColumnName($column)} {$this->getColumnType($definition)}
MYSQL;
    }

    /**
     * @inheritdoc
     */
    public function dropColumns($table, $columns)
    {
        $columns = (array)$columns;

        if (!empty($columns)) {
            if (1 === count($columns)) {
                return $this->connection->statement("ALTER TABLE $table DROP COLUMN " . $columns[0]);
            } else {
                return $this->connection->statement("ALTER TABLE $table DROP (" . implode(',', $columns) . ")");
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function makeConstraintName($prefix, $table, $column = null)
    {
        $temp = parent::makeConstraintName($prefix, $table, $column);
        // must be less than 30 characters
        if (30 < strlen($temp)) {
            $temp = substr($temp, strlen($prefix . '_'));
            $temp = $prefix . '_' . hash('crc32', $temp);
        }

        return $temp;
    }

    /**
     * @inheritdoc
     */
    public function requiresCreateIndex($unique = false, $on_create_table = false)
    {
        return !($unique && $on_create_table);
    }

    /**
     * @inheritdoc
     */
    public function dropIndex($name, $table)
    {
        return 'DROP INDEX ' . $this->quoteTableName($name);
    }

    /**
     * @inheritdoc
     */
    public function resetSequence($table, $value = null)
    {
        if ($table->sequenceName === null) {
            return;
        }

        if ($value !== null) {
            $value = (int)$value;
        } else {
            $value = (int)$this->selectValue("SELECT MAX(\"{$table->primaryKey}\") FROM {$table->quotedName}");
            $value++;
        }
        $tableName = str_replace('.', '_', $table->internalName);
        // sequence and trigger names maximum length is 30
        if (26 < strlen($tableName)) {
            $tableName = hash('crc32', $tableName);
        }
        $sequence = $this->quoteTableName(strtoupper($tableName) . '_SEQ');
        $this->connection->statement("DROP SEQUENCE $sequence");
        $this->connection->statement(
            "CREATE SEQUENCE $sequence START WITH {$value} INCREMENT BY 1 NOMAXVALUE NOCACHE"
        );
    }

    /**
     * @inheritdoc
     */
    public function checkIntegrity($check = true, $schema = '')
    {
        if ($schema === '') {
            $schema = $this->getDefaultSchema();
        }
        $mode = $check ? 'ENABLE' : 'DISABLE';
        $query = "SELECT CONSTRAINT_NAME FROM USER_CONSTRAINTS WHERE TABLE_NAME=:t AND OWNER=:o";
        foreach ($this->getTableNames($schema) as $table) {
            $constraints = $this->selectColumn($query, [':t' => $table->resourceName, ':o' => $table->schemaName]);
            foreach ($constraints as $constraint) {
                $this->connection->statement("ALTER TABLE {$table->quotedName} $mode CONSTRAINT \"{$constraint}\"");
            }
        }
    }

    /**
     * {@InheritDoc}
     */
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        // ON UPDATE not supported by Oracle
        return parent::addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, null);
    }

    /**
     * @inheritdoc
     */
    public function dropTable($table)
    {
        $result = parent::dropTable($table);

        $table = str_replace(['.', '"'], ['_', ''], $table);
        // sequence and trigger names maximum length is 30
        if (26 < strlen($table)) {
            $table = hash('crc32', $table);
        }
        $sequence = $this->quoteTableName(strtoupper($table) . '_SEQ');
        $trigger = $this->quoteTableName(strtoupper($table) . '_TRG');
        $sql = <<<MYSQL
BEGIN
  EXECUTE IMMEDIATE 'DROP SEQUENCE {$sequence}';
EXCEPTION
  WHEN OTHERS THEN
    IF SQLCODE != -2289 THEN
      RAISE;
    END IF;
END;
MYSQL;
        $this->connection->statement($sql);

        $sql = <<<MYSQL
BEGIN
  EXECUTE IMMEDIATE 'DROP TRIGGER {$trigger}';
EXCEPTION
  WHEN OTHERS THEN
    IF SQLCODE != -4080 THEN
      RAISE;
    END IF;
END;
MYSQL;
        $this->connection->statement($sql);

        return $result;
    }

    public function getPrimaryKeyCommands($table, $column)
    {
        // pre 12c versions need sequences and trigger to accomplish autoincrement
        $trigTable = $this->quoteTableName($table);
        $trigField = $this->quoteColumnName($column);
        $table = str_replace('.', '_', $table);
        // sequence and trigger names maximum length is 30
        if (26 < strlen($table)) {
            $table = hash('crc32', $table);
        }
        $sequence = $this->quoteTableName(strtoupper($table) . '_SEQ');
        $trigger = $this->quoteTableName(strtoupper($table) . '_TRG');

        $extras = [];
        $extras[] = "CREATE SEQUENCE $sequence";
        $extras[] = <<<SQL
CREATE OR REPLACE TRIGGER {$trigger}
BEFORE INSERT ON {$trigTable}
FOR EACH ROW
BEGIN
  IF :new.{$trigField} IS NULL THEN
    SELECT {$sequence}.NEXTVAL
    INTO   :new.{$trigField}
    FROM   dual;
  END IF;
END;
SQL;

        return $extras;
    }

    public function getTimestampForSet()
    {
        return $this->connection->raw('(CURRENT_TIMESTAMP)');
    }

//    /**
//     * Extracts the PHP type from DB type.
//     *
//     * @param ColumnSchema $column
//     * @param string       $dbType DB type
//     */
//    public function extractType(ColumnSchema $column, $dbType)
//    {
//        parent::extractType($column, $dbType);
//        if (strpos($dbType, 'FLOAT') !== false) {
//            $column->phpType = 'double';
//        }
//
//        if (strpos($dbType, 'NUMBER') !== false || strpos($dbType, 'INTEGER') !== false) {
//            if (strpos($dbType, '(') && preg_match('/\((.*)\)/', $dbType, $matches)) {
//                $values = explode(',', $matches[1]);
//                if (isset($values[1]) and (((int)$values[1]) > 0)) {
//                    $column->phpType = 'double';
//                } else {
//                    $column->phpType = 'integer';
//                }
//            } else {
//                $column->phpType = 'double';
//            }
//        } else {
//            $column->phpType = 'string';
//        }
//    }
//
    /**
     * Extracts the default value for the column.
     * The value is typecasted to correct PHP type.
     *
     * @param ColumnSchema $field
     * @param mixed        $defaultValue the default value obtained from metadata
     */
    public function extractDefault(ColumnSchema $field, $defaultValue)
    {
        if (stripos($defaultValue, 'timestamp') !== false) {
            $field->defaultValue = null;
        } else {
            parent::extractDefault($field, $defaultValue);
        }
    }

    /**
     * @inheritdoc
     */
    protected function getProcedureStatement(RoutineSchema $routine, array $param_schemas, array &$values)
    {
        $paramStr = $this->getRoutineParamString($param_schemas, $values);

        return "BEGIN {$routine->quotedName}($paramStr); END;";
    }

    /**
     * @inheritdoc
     */
    protected function getFunctionStatement(RoutineSchema $routine, array $param_schemas, array &$values)
    {
        switch ($routine->returnType) {
            case DbSimpleTypes::TYPE_TABLE:
                $paramStr = $this->getRoutineParamString($param_schemas, $values);

                return "SELECT * from TABLE({$routine->quotedName}($paramStr))";
                break;
            default:
                return parent::getFunctionStatement($routine, $param_schemas, $values) . ' FROM DUAL';
                break;
        }
    }

    /**
     * @inheritdoc
     */
    protected function doRoutineBinding($statement, array $paramSchemas, array &$values)
    {
        /**
         * @type string          $key
         * @type ParameterSchema $paramSchema
         */
        foreach ($paramSchemas as $key => $paramSchema) {
            switch ($paramSchema->paramType) {
                case 'IN':
                    $this->bindValue($statement, ':' . $paramSchema->name, array_get($values, $key));
                    break;
                case 'INOUT':
                case 'OUT':
                    if (0 === strcasecmp('REF CURSOR', $paramSchema->dbType)) {
                        $pdoType = \PDO::PARAM_STMT;
                        $this->bindParam($statement, ':' . $paramSchema->name, $values[$key],
                            $pdoType | \PDO::PARAM_INPUT_OUTPUT, -1, OCI_B_CURSOR);
                    } else {
                        $pdoType = $this->extractPdoType($paramSchema->type);
                        $this->bindParam($statement, ':' . $paramSchema->name, $values[$key],
                            $pdoType | \PDO::PARAM_INPUT_OUTPUT, $paramSchema->length);
                    }
                    break;
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function postProcedureCall(array $param_schemas, array &$values)
    {
        foreach ($param_schemas as $key => $paramSchema) {
            switch ($paramSchema->paramType) {
                case 'INOUT':
                case 'OUT':
                    if ((0 === strcasecmp('REF CURSOR', $paramSchema->dbType)) && isset($values[$key])) {
                        oci_execute($values[$key], OCI_DEFAULT);
                        oci_fetch_all($values[$key], $array, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
                        oci_free_cursor($values[$key]);
                        $values[$key] = $array;
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function handleRoutineException(\Exception $ex)
    {
        if (false !== stripos($ex->getMessage(), 'has not been implemented')) {
            return true;
        }

        return false;
    }
}
