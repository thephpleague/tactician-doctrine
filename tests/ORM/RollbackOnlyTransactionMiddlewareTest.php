<?php

declare(strict_types=1);

namespace League\Tactician\Doctrine\Tests\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
use League\Tactician\Doctrine\ORM\RollbackOnlyTransactionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class RollbackOnlyTransactionMiddlewareTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private $entityManager;

    /** @var RollbackOnlyTransactionMiddleware */
    private $middleware;

    protected function setUp() : void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->middleware = new RollbackOnlyTransactionMiddleware($this->entityManager);
    }

    public function testCommandSucceedsAndTransactionIsCommitted() : void
    {
        $this->entityManager->expects(self::once())->method('beginTransaction');
        $this->entityManager->expects(self::once())->method('commit');
        $this->entityManager->expects(self::once())->method('flush');
        $this->entityManager->expects(self::never())->method('rollback');
        $this->entityManager->expects(self::never())->method('close');

        $executed = 0;
        $next     = static function () use (&$executed) : void {
            $executed++;
        };

        $this->middleware->execute(new stdClass(), $next);

        self::assertEquals(1, $executed);
    }

    public function testCommandFailsOnExceptionAndTransactionIsRolledBack() : void
    {
        $this->entityManager->expects(self::once())->method('beginTransaction');
        $this->entityManager->expects(self::never())->method('commit');
        $this->entityManager->expects(self::never())->method('flush');
        $this->entityManager->expects(self::once())->method('rollback');
        $this->entityManager->expects(self::never())->method('getConnection');
        $this->entityManager->expects(self::never())->method('close');

        $next = static function () : void {
            throw new Exception('CommandFails');
        };

        $this->expectExceptionObject(new Exception('CommandFails'));
        $this->middleware->execute(new stdClass(), $next);
    }

    public function testCommandFailsOnErrorAndTransactionIsRolledBack() : void
    {
        $this->entityManager->expects(self::once())->method('beginTransaction');
        $this->entityManager->expects(self::never())->method('commit');
        $this->entityManager->expects(self::never())->method('flush');
        $this->entityManager->expects(self::once())->method('rollback');
        $this->entityManager->expects(self::never())->method('getConnection');
        $this->entityManager->expects(self::never())->method('close');

        $next = static function () : void {
            throw new Error('CommandFails');
        };

        $this->expectErrorMessage('CommandFails');
        $this->middleware->execute(new stdClass(), $next);
    }
}
