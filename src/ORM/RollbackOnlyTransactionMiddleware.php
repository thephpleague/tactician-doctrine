<?php

namespace League\Tactician\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\Tactician\Middleware;
use Throwable;

class RollbackOnlyTransactionMiddleware implements Middleware
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Executes the given command and optionally returns a value
     *
     * @param object $command
     * @param callable $next
     * @return mixed
     * @throws \Throwable
     * @throws \Exception
     */
    public function execute($command, callable $next)
    {
        $this->entityManager->beginTransaction();

        try {
            $returnValue = $next($command);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();

            throw $e;
        } catch (Throwable $e) {
            $this->entityManager->rollback();

            throw $e;
        }

        return $returnValue;
    }
}
