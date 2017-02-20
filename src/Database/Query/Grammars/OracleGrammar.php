<?php

namespace DreamFactory\Core\Oracle\Database\Query\Grammars;

use Exception;
use PDO;
use Yajra\Pdo\Oci8;

class OracleGrammar extends \Yajra\Oci8\Query\Grammars\OracleGrammar
{
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
