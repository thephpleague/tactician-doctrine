<?php
namespace League\Tactician\Doctrine\ORM\Tests;

use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\Doctrine\ORM\TransactionMiddleware;
use Error;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yoast\PHPUnitPolyfills\Polyfills\ExpectPHPException;

class TransactionMiddlewareTest extends TestCase
{
    use ExpectPHPException;

    /**
     * @var EntityManagerInterface|MockInterface
     */
    private $entityManager;

    /**
     * @var TransactionMiddleware
     */
    private $middleware;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);

        $this->middleware = new TransactionMiddleware($this->entityManager);
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
        $this->entityManager->shouldReceive('rollback')->never();
        $this->entityManager->shouldReceive('close')->never();

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
        $this->entityManager->shouldReceive('getConnection->isTransactionActive')->once()->andReturn(false);
        $this->entityManager->shouldReceive('getConnection->isRollbackOnly')->never();
        $this->entityManager->shouldReceive('close')->once();

        $next = function () {
            throw new Exception('CommandFails');
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('CommandFails');

        $this->middleware->execute(new stdClass(), $next);
    }

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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('CommandFails');

        $this->middleware->execute(new stdClass(), $next);
    }

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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('CommandFails');

        $this->middleware->execute(new stdClass(), $next);
    }

    public function testCommandFailsOnErrorAndTransactionIsRolledBack()
    {
        $this->entityManager->expects('beginTransaction');
        $this->entityManager->allows('commit')->never();
        $this->entityManager->allows('flush')->never();
        $this->entityManager->expects('rollback');
        $this->entityManager->expects('getConnection->isTransactionActive')->andReturns(false);
        $this->entityManager->allows('getConnection->isRollbackOnly')->never();
        $this->entityManager->expects('close');

        $next = function () {
            throw new Error('CommandFails');
        };

        $this->expectException(Error::class);
        $this->expectErrorMessage('CommandFails');

        $this->middleware->execute(new stdClass(), $next);
    }
}
