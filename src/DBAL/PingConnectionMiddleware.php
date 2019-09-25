<?php

namespace League\Tactician\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use League\Tactician\Middleware;

/**
 * Verifies if there is a connection established with the database. If not it will reconnect.
 */
final class PingConnectionMiddleware implements Middleware
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Reconnects to the database if the connection is expired.
     *
     * @param object $command
     * @param callable $next
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        if (!$this->connection->ping()) {
            $this->connection->close();
            $this->connection->connect();
        }
        
        return $next($command);
    }
}
