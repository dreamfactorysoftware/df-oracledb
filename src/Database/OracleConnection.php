<?php

namespace DreamFactory\Core\Oracle\Database;

use DreamFactory\Core\Oracle\Database\Query\Grammars\OracleGrammar as QueryGrammar;
use Yajra\Oci8\Oci8Connection;

class OracleConnection extends Oci8Connection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Grammar|\Yajra\Oci8\Query\Grammars\OracleGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar());
    }

    public function getPdo()
    {
        // For some reason the Oci8 dies without warning over multiple uses, this recreates for now.
        if (is_null($this->pdo)) {
            $this->reconnect();
        }

        return parent::getPdo();
    }

    /**
     * Execute a query and return the results as an array.
     * This ensures compatibility with DreamFactory's array-based processing.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        $results = parent::select($query, $bindings, $useReadPdo);
        
        // Ensure all results are arrays, not objects
        if (is_array($results)) {
            foreach ($results as &$row) {
                if (is_object($row)) {
                    $row = (array)$row;
                }
            }
        }
        
        return $results;
    }
}
