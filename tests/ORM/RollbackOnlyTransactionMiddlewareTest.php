<?php
namespace League\Tactician\Doctrine\ORM\Tests;

use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\Doctrine\ORM\RollbackOnlyTransactionMiddleware;
use League\Tactician\Doctrine\ORM\TransactionMiddleware;
use Error;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yoast\PHPUnitPolyfills\Polyfills\ExpectPHPException;

class RollbackOnlyTransactionMiddlewareTest extends TestCase
{
    use ExpectPHPException;

    /**
     * @var EntityManagerInterface|MockInterface
     */
    private $entityManager;

    /**
     * @var RollbackOnlyTransactionMiddleware
     */
    private $middleware;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);

        $this->middleware = new RollbackOnlyTransactionMiddleware($this->entityManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('CommandFails');

        $this->middleware->execute(new stdClass(), $next);
    }

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

        $this->expectException(Error::class);
        $this->expectErrorMessage('CommandFails');

        $this->middleware->execute(new stdClass(), $next);
    }
}
