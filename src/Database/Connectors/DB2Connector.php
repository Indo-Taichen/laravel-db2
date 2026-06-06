<?php

namespace Cooperl\DB2\Database\Connectors;

use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
use Throwable;

/**
 * Class IBMConnector
 *
 * @package Cooperl\DB2\Database\Connectors
 */
class DB2Connector extends Connector implements ConnectorInterface
{
    /**
     * @param array $config
     *
     * @return \PDO
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);
        $options = $this->getOptions($config);
        $connection = $this->createConnection($dsn, $config, $options);

        if (isset($config['schema'])) {
            $schema = $config['schema'];

            $connection->prepare('set schema ' . $schema)
                       ->execute();
        }

        return $connection;
    }

    /**
     * Create a new PDO connection, retrying on DB2 connection errors.
     *
     * @param string $dsn
     * @param array  $config
     * @param array  $options
     *
     * @return \PDO
     */
    public function createConnection($dsn, array $config, array $options)
    {
        $attempts = (int) ($config['reconnect_attempts'] ?? 3);
        $delayMs  = (int) ($config['reconnect_delay_ms'] ?? 1000);
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->createPdoConnection($dsn, $username, $password, $options);
            } catch (Throwable $e) {
                if (! $this->causedByLostConnection($e)) {
                    throw $e;
                }

                $lastException = $e;

                if ($attempt < $attempts) {
                    usleep($delayMs * 1000);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Determine if the given exception was caused by a DB2 lost connection.
     *
     * @param Throwable $e
     *
     * @return bool
     */
    protected function causedByLostConnection(Throwable $e): bool
    {
        $message = $e->getMessage();

        foreach ([
            'SQL30081N',
            'SQL1224N',
            'selectForConnectTimeout',
            'SQLSTATE=08001',
            'SQLSTATE[08001]',
        ] as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        return parent::causedByLostConnection($e);
    }
}
