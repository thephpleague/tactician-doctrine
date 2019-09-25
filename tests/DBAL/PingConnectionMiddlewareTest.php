<?php

namespace League\Tactician\Doctrine\DBAL\Tests;

use Doctrine\DBAL\Connection;
use League\Tactician\Doctrine\DBAL\PingConnectionMiddleware;
use League\Tactician\Doctrine\DBAL\TransactionMiddleware;
use Mockery;
use Mockery\MockInterface;
use stdClass;

final class PingConnectionMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection|MockInterface
     */
    private $connection;

    /**
     * @var TransactionMiddleware
     */
    private $middleware;

    protected function setUp()
    {
        $this->connection = Mockery::mock(Connection::class);

        $this->middleware = new PingConnectionMiddleware($this->connection);
    }

    /**
     * @test
     */
    public function itShouldReconnectIfConnectionExpires()
    {
        $this->connection->shouldReceive('ping')->once()->andReturn(false);
        $this->connection->shouldReceive('close')->once();
        $this->connection->shouldReceive('connect')->once();

        $executed = 0;
        $next = function () use (&$executed) {
            $executed++;
        };

        $this->middleware->execute(new stdClass(), $next);
        
        $this->assertEquals(1, $executed);
    }

    /**
     * @test
     */
    public function itShouldNotReconnectIfConnectionIsStillAlive()
    {
        $this->connection->shouldReceive('ping')->once()->andReturn(true);
        $this->connection->shouldReceive('close')->never();
        $this->connection->shouldReceive('connect')->never();

        $executed = 0;
        $next = function () use (&$executed) {
            $executed++;
        };

        $this->middleware->execute(new stdClass(), $next);

        $this->assertEquals(1, $executed);
    }
}
