<?php

declare(strict_types=1);

namespace League\Tactician\Doctrine\Tests\DBAL;

use Doctrine\DBAL\Driver\Connection;
use Error;
use Exception;
use League\Tactician\Doctrine\DBAL\TransactionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

final class TransactionMiddlewareTest extends TestCase
{
    /** @var Connection&MockObject */
    private $connection;

    private TransactionMiddleware $middleware;

    public function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $this->middleware = new TransactionMiddleware($this->connection);
    }

    public function testCommandSucceedsAndTransactionIsCommitted(): void
    {
        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::once())->method('commit');
        $this->connection->expects(self::never())->method('rollBack');

        $executed = 0;
        $next     = static function () use (&$executed): void {
            $executed++;
        };

        $this->middleware->execute(new stdClass(), $next);

        self::assertEquals(1, $executed);
    }

    public function testCommandFailsOnExceptionAndTransactionIsRolledBack(): void
    {
        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::never())->method('commit');
        $this->connection->expects(self::once())->method('rollBack');

        $next = static function (): void {
            throw new Exception('CommandFails');
        };

        $this->expectExceptionObject(new Exception('CommandFails'));

        $this->middleware->execute(new stdClass(), $next);
    }

    public function testCommandFailsOnErrorAndTransactionIsRolledBack(): void
    {
        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::never())->method('commit');
        $this->connection->expects(self::once())->method('rollBack');

        $next = static function (): void {
            throw new Error('CommandFails');
        };

        $this->expectErrorMessage('CommandFails');
        $this->middleware->execute(new stdClass(), $next);
    }
}
