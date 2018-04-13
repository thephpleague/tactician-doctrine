<?php

namespace League\Tactician\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\Middleware;

/**
 * Close Doctrine EntityManager on any exception
 */
class EntityManagerCloseMiddleware implements Middleware
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @inheritdoc
     */
    public function execute($command, callable $next)
    {
        try {
            return $next($command);
        } catch (\Exception $exception) {
            // Only for backward capability ( < PHP 7.0.0)
            $this->tryToCloseEntityManager();
            throw $exception;
        } catch (\Throwable $throwable) {
            $this->tryToCloseEntityManager();
            throw  $throwable;
        }
    }

    /**
     * Close entityManager when possible
     */
    private function tryToCloseEntityManager()
    {
        $connection = $this->entityManager->getConnection();
        if (!$connection->isTransactionActive() || $connection->isRollbackOnly()) {
            $this->entityManager->close();
        }
    }
}
