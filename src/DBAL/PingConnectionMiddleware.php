<?php

declare(strict_types=1);

namespace League\Tactician\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use ErrorException;
use League\Tactician\Middleware;
use Throwable;

use function restore_error_handler;
use function set_error_handler;

/**
 * Verifies if there is a connection established with the database. If not it will reconnect.
 */
final class PingConnectionMiddleware implements Middleware
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Reconnects to the database if the connection is expired.
     *
     * @return mixed
     */
    public function execute(object $command, callable $next)
    {
        if (! $this->ping($this->connection)) {
            $this->connection->close();
            $this->connection->connect();
        }

        return $next($command);
    }

    private function ping(Connection $connection): bool
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new ErrorException($message, $severity, $severity, $file, $line);
        });

        try {
            $dummySelectSQL = $connection->getDatabasePlatform()->getDummySelectSQL();

            $connection->executeQuery($dummySelectSQL);

            return true;
        } catch (Throwable $exception) {
            return false;
        } finally {
            restore_error_handler();
        }
    }
}
