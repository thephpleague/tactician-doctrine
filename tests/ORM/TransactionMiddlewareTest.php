<?php
namespace League\Tactician\Doctrine\ORM\Tests;

use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\Command;
use League\Tactician\Doctrine\ORM\TransactionMiddleware;
use Exception;
use Mockery;
use Mockery\MockInterface;

class TransactionMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManagerInterface|MockInterface
     */
    private $entityManager;

    /**
     * @var TransactionMiddleware
     */
    private $middleware;

    protected function setUp()
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);

        $this->middleware = new TransactionMiddleware($this->entityManager);
    }

    public function testCommandSucceedsAndTransactionIsCommitted()
    {
        $this->entityManager->shouldReceive('beginTransaction')->once();
        $this->entityManager->shouldReceive('commit')->once();
        $this->entityManager->shouldReceive('close')->never();
        $this->entityManager->shouldReceive('flush')->once();
        $this->entityManager->shouldReceive('rollback')->never();

        $executed = 0;
        $next = function () use (&$executed) {
            $executed++;
        };

        $this->middleware->execute(Mockery::mock(Command::class), $next);

        $this->assertEquals(1, $executed);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage CommandFails
     */
    public function testCommandFailsAndTransactionIsRolledBack()
    {
        $this->entityManager->shouldReceive('beginTransaction')->once();
        $this->entityManager->shouldReceive('close')->once();
        $this->entityManager->shouldReceive('rollback')->once();
        $this->entityManager->shouldReceive('commit')->never();
        $this->entityManager->shouldReceive('flush')->never();

        $next = function () {
            throw new Exception('CommandFails');
        };

        $this->middleware->execute(Mockery::mock(Command::class), $next);
    }
}
