<?php

namespace DreamFactory\Core\Oracle\Database\Connectors;

use Exception;
use PDO;
use Yajra\Pdo\Oci8;

class OracleConnector extends \Yajra\Oci8\Connectors\OracleConnector
{
    /**
     * The default PDO connection options.
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE         => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE      => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
    ];

    /**
     * @inheritdoc
     */
    public function createConnection($tns, array $config, array $options)
    {
        \Log::warning("TNS used in the connection: ",[$tns]);
        $configForLog = $config;
        $configForLog["password"] = "****";
        \Log::warning("All configs of the service: ",[$configForLog]);
        // add fallback in case driver is not set, will use pdo instead
        if (! in_array($config['driver'], ['oci8', 'pdo-via-oci8', 'oracle'])) {
            return parent::createConnection($tns, $config, $options);
        }

        $config             = $this->setCharset($config);
        $options['charset'] = $config['charset'];

        try {
            $pdo = new Oci8($tns, $config['username'], $config['password'], $options);
        } catch (Exception $e) {
            if ($this->causedByLostConnection($e)) {
                return new Oci8($tns, $config['username'], $config['password'], $options);
            }

            throw $e;
        }

        return $pdo;
    }
}
