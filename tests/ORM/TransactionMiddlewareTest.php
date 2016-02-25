<?php
namespace League\Tactician\Doctrine\ORM\Tests;

use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\Doctrine\ORM\TransactionMiddleware;
use Error;
use Exception;
use Mockery;
use Mockery\MockInterface;
use stdClass;

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
        $this->entityManager->shouldReceive('flush')->once();
        $this->entityManager->shouldReceive('rollback')->never();
        $this->entityManager->shouldReceive('close')->never();

        $executed = 0;
        $next = function () use (&$executed) {
            $executed++;
        };

        $this->middleware->execute(new stdClass(), $next);

        $this->assertEquals(1, $executed);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage CommandFails
     */
    public function testCommandFailsOnExceptionAndTransactionIsRolledBack()
    {
        $this->entityManager->shouldReceive('beginTransaction')->once();
        $this->entityManager->shouldReceive('commit')->never();
        $this->entityManager->shouldReceive('flush')->never();
        $this->entityManager->shouldReceive('rollback')->once();
        $this->entityManager->shouldReceive('getConnection->isTransactionActive')->once()->andReturn(false);
        $this->entityManager->shouldReceive('getConnection->isRollbackOnly')->never();
        $this->entityManager->shouldReceive('close')->once();

        $next = function () {
            throw new Exception('CommandFails');
        };

        $this->middleware->execute(new stdClass(), $next);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage CommandFails
     */
    public function testCommandFailsWhileInANestedTransactionButWithoutSavepoints()
    {
        $this->entityManager->shouldReceive('beginTransaction')->once();
        $this->entityManager->shouldReceive('commit')->never();
        $this->entityManager->shouldReceive('flush')->never();
        $this->entityManager->shouldReceive('rollback')->once();
        $this->entityManager->shouldReceive('getConnection->isTransactionActive')->once()->andReturn(true);
        $this->entityManager->shouldReceive('getConnection->isRollbackOnly')->once()->andReturn(true);
        $this->entityManager->shouldReceive('close')->once();

        $next = function () {
            throw new Exception('CommandFails');
        };

        $this->middleware->execute(new stdClass(), $next);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage CommandFails
     */
    public function testCommandFailsWhileInANestedTransactionWithSavepointOn()
    {
        $this->entityManager->shouldReceive('beginTransaction')->once();
        $this->entityManager->shouldReceive('commit')->never();
        $this->entityManager->shouldReceive('flush')->never();
        $this->entityManager->shouldReceive('rollback')->once();
        $this->entityManager->shouldReceive('getConnection->isTransactionActive')->once()->andReturn(true);
        $this->entityManager->shouldReceive('getConnection->isRollbackOnly')->once()->andReturn(false);
        $this->entityManager->shouldReceive('close')->never();

        $next = function () {
            throw new Exception('CommandFails');
        };

        $this->middleware->execute(new stdClass(), $next);
    }

    /**
     * @requires PHP 7
     *
     * @expectedException Error
     * @expectedExceptionMessage CommandFails
     */
    public function testCommandFailsOnErrorAndTransactionIsRolledBack()
    {
        $this->entityManager->shouldReceive('beginTransaction')->once();
        $this->entityManager->shouldReceive('commit')->never();
        $this->entityManager->shouldReceive('flush')->never();
        $this->entityManager->shouldReceive('rollback')->once();
        $this->entityManager->shouldReceive('getConnection->isTransactionActive')->once()->andReturn(false);
        $this->entityManager->shouldReceive('getConnection->isRollbackOnly')->never();
        $this->entityManager->shouldReceive('close')->once();

        $next = function () {
            throw new Error('CommandFails');
        };

        $this->middleware->execute(new stdClass(), $next);
    }
}
