<?php

declare(strict_types=1);

namespace League\Tactician\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\Tactician\Middleware;
use Throwable;

class RollbackOnlyTransactionMiddleware implements Middleware
{
    /** @var EntityManagerInterface */
    private $entityManager;

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
        } catch (Exception | Throwable $e) {
            $this->entityManager->rollback();

            throw $e;
        }

        return $returnValue;
    }
}
