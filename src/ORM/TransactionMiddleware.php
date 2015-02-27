<?php
namespace League\Tactician\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\Command;
use Exception;
use League\Tactician\Middleware;

/**
 * Wraps command execution inside a Doctrine ORM transaction
 */
class TransactionMiddleware implements Middleware
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
     * @param Command $command
     * @param callable $next
     * @return mixed
     * @throws Exception
     */
    public function execute(Command $command, callable $next)
    {
        $this->entityManager->beginTransaction();

        try {
            $returnValue = $next($command);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->close();
            $this->entityManager->rollback();
            throw $e;
        }

        return $returnValue;
    }
}
