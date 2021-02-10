<?php

namespace DreamFactory\Core\Oracle\Database\Query\Grammars;

use Exception;
use Illuminate\Database\Query\Builder;
use PDO;
use Yajra\Pdo\Oci8;

class OracleGrammar extends \Yajra\Oci8\Query\Grammars\OracleGrammar
{
    /**
     * @inheritDoc
     */
    protected function compileTableExpression($sql, $constraint, $query)
    {
        if ($query->limit == 1 && is_null($query->offset)) {
            return "select * from ({$sql}) where rownum {$constraint}";
        }

        if (! is_null($query->limit && ! is_null($query->offset))) {
            $start  = $query->offset + 1;
            $finish = $query->offset + $query->limit;

            return "select t2.* from ( select rownum AS \"rn\", t1.* from ({$sql}) t1 where rownum <= {$finish}) t2 where t2.\"rn\" >= {$start}";
        }

        return "select t2.* from ( select rownum AS \"rn\", t1.* from ({$sql}) t1 ) t2 where t2.\"rn\" {$constraint}";
    }


    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        // Should stick with the natural casing allowed by the database, don't assume a case either way
        // after all, everything is quoted to escape the reserved words anyway
//        $value = $this->isReserved($value) ? Str::lower($value) : Str::upper($value);

        return '"' . str_replace('"', '""', $value) . '"';
    }
}
