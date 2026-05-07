<?php

namespace DreamFactory\Core\Oracle\Database;

use DreamFactory\Core\Oracle\Database\Query\Grammars\OracleGrammar as QueryGrammar;
use Yajra\Oci8\Oci8Connection;

class OracleConnection extends Oci8Connection
{
    /**
     * Get the default query grammar instance.
     */
    protected function getDefaultQueryGrammar(): \Yajra\Oci8\Query\Grammars\OracleGrammar
    {
        return new QueryGrammar($this);
    }

    public function getPdo()
    {
        // For some reason the Oci8 dies without warning over multiple uses, this recreates for now.
        if (is_null($this->pdo)) {
            $this->reconnect();
        }

        return parent::getPdo();
    }
}
