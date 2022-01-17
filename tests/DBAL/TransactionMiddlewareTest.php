<?php

namespace League\Tactician\Doctrine\DBAL\Tests;

use Doctrine\DBAL\Driver\Connection;
use Error;
use Exception;
use League\Tactician\Doctrine\DBAL\TransactionMiddleware;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yoast\PHPUnitPolyfills\Polyfills\ExpectPHPException;

final class TransactionMiddlewareTest extends TestCase
{
    use ExpectPHPException;

    /**
     * @var Connection|MockInterface
     */
    private $connection;

    /**
     * @var TransactionMiddleware
     */
    private $middleware;

    public function setUp(): void
    {
        $this->connection = Mockery::mock(Connection::class);

        $this->middleware = new TransactionMiddleware($this->connection);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCommandSucceedsAndTransactionIsCommitted()
    {
        $this->connection->expects('beginTransaction');
        $this->connection->expects('commit');
        $this->connection->allows('rollBack')->never();

        $executed = 0;
        $next = function () use (&$executed) {
            $executed++;
        };

        $this->middleware->execute(new stdClass(), $next);

        $this->assertEquals(1, $executed);
    }

    public function testCommandFailsOnExceptionAndTransactionIsRolledBack()
    {
        $this->connection->expects('beginTransaction');
        $this->connection->allows('commit')->never();
        $this->connection->expects('rollBack');

        $next = function () {
            throw new Exception('CommandFails');
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('CommandFails');

        $this->middleware->execute(new stdClass(), $next);
    }

    public function testCommandFailsOnErrorAndTransactionIsRolledBack()
    {
        $this->connection->expects('beginTransaction');
        $this->connection->allows('commit')->never();
        $this->connection->expects('rollBack');


        $this->expectException(Error::class);
        $this->expectErrorMessage('CommandFails');

        $next = function () {
            throw new Error('CommandFails');
        };

        $this->middleware->execute(new stdClass(), $next);
    }
}
