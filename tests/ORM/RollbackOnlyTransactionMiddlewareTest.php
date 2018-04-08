<?php
namespace League\Tactician\Doctrine\ORM\Tests;

use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\Doctrine\ORM\RollbackOnlyTransactionMiddleware;
use League\Tactician\Doctrine\ORM\TransactionMiddleware;
use Error;
use Exception;
use Mockery;
use Mockery\MockInterface;
use stdClass;

class RollbackOnlyTransactionMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManagerInterface|MockInterface
     */
    private $entityManager;

    /**
     * @var RollbackOnlyTransactionMiddleware
     */
    private $middleware;

    protected function setUp()
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);

        $this->middleware = new RollbackOnlyTransactionMiddleware($this->entityManager);
    }

    public function testCommandSucceedsAndTransactionIsCommitted()
    {
        $this->entityManager->shouldReceive('beginTransaction')->once();
        $this->entityManager->shouldReceive('commit')->once();
        $this->entityManager->shouldReceive('flush')->once();
        $this->entityManager->shouldNotReceive('rollback');
        $this->entityManager->shouldNotReceive('close');

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
        $this->entityManager->shouldNotReceive('getConnection');
        $this->entityManager->shouldNotReceive('close');

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
        $this->entityManager->shouldNotReceive('getConnection');
        $this->entityManager->shouldNotReceive('close');

        $next = function () {
            throw new Error('CommandFails');
        };

        $this->middleware->execute(new stdClass(), $next);
    }
}
