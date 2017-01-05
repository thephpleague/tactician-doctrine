<?php

namespace League\Tactician\Doctrine\DBAL\Tests;

use Doctrine\DBAL\Driver\Connection;
use Error;
use Exception;
use League\Tactician\Doctrine\DBAL\TransactionMiddleware;
use Mockery;
use Mockery\MockInterface;
use stdClass;

final class TransactionMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection|MockInterface
     */
    private $connection;

    /**
     * @var TransactionMiddleware
     */
    private $middleware;

    public function setUp()
    {
        $this->connection = Mockery::mock(Connection::class);

        $this->middleware = new TransactionMiddleware($this->connection);
    }

    public function testCommandSucceedsAndTransactionIsCommitted()
    {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('commit')->once();
        $this->connection->shouldReceive('rollBack')->never();

        $executed = 0;
        $next = function () use (&$executed) {
            $executed++;
        };

        $this->middleware->execute(new stdClass(), $next);

        $this->assertEquals(1, $executed);
    }

    public function testCommandFailsOnExceptionAndTransactionIsRolledBack()
    {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('commit')->never();
        $this->connection->shouldReceive('rollBack')->once();

        $next = function () {
            throw new Exception('CommandFails');
        };

        $this->setExpectedException(Exception::class, 'CommandFails');

        $this->middleware->execute(new stdClass(), $next);
    }

    /**
     * @requires PHP 7
     */
    public function testCommandFailsOnErrorAndTransactionIsRolledBack()
    {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('commit')->never();
        $this->connection->shouldReceive('rollBack')->once();

        $this->setExpectedException(Error::class, 'CommandFails');

        $next = function () {
            throw new Error('CommandFails');
        };

        $this->middleware->execute(new stdClass(), $next);
    }
}
