<?php

declare(strict_types=1);

namespace League\Tactician\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\Tactician\Middleware;
use Throwable;

/**
 * Wraps command execution inside a Doctrine ORM transaction
 */
class TransactionMiddleware implements Middleware
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Executes the given command and optionally returns a value
     *
     * @return mixed
     *
     * @throws Throwable
     * @throws Exception
     */
    public function execute(object $command, callable $next)
    {
        $this->entityManager->beginTransaction();

        try {
            $returnValue = $next($command);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->rollbackTransaction();

            throw $e;
        } catch (Throwable $e) {
            $this->rollbackTransaction();

            throw $e;
        }

        return $returnValue;
    }

    /**
     * Rollback the current transaction and close the entity manager when possible.
     */
    protected function rollbackTransaction(): void
    {
        $this->entityManager->rollback();

        $connection = $this->entityManager->getConnection();
        if ($connection->isTransactionActive() && ! $connection->isRollbackOnly()) {
            return;
        }

        $this->entityManager->close();
    }
}
