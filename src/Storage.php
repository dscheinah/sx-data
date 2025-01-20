<?php
namespace Sx\Data;

use Generator;
use Throwable;

/**
 * Implements an abstraction for a database backend to be used in repositories.
 * A backend should always be accessed using a storage.
 * This class is not defined as an abstract since it contains no abstract functionality. But it should be extended
 * to provide functions abstracting the underlying table structure.
 */
class Storage
{
    /**
     * The backend to use for all database operations.
     *
     * @var BackendInterface
     */
    protected $backend;

    /**
     * Cache for all resources from the backend. No statement will be prepared twice.
     *
     * @var array<string, mixed>
     */
    private $statements = [];

    /**
     * Creates the storage and assigns the database backend.
     *
     * @param BackendInterface $backend
     */
    public function __construct(BackendInterface $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Executes a plain SQL statement on the backend and returns the number of affected rows.
     * The statement is meant to contain placeholders for prepared statements to be bound with the params.
     * No user params must be included in the statement since only the params are escaped.
     *
     * @param string       $statement
     * @param array<mixed> $params
     *
     * @return int
     * @throws BackendException
     */
    public function execute(string $statement, array $params = []): int
    {
        $resource = $this->getResource($statement);
        return $this->backend->execute($resource, $params);
    }

    /**
     * Executes a plain select statement and returns the result set as a Generator.
     * The statement is meant to contain placeholders for prepared statements to be bound with the params.
     * No user params must be included in the statement since only the params are escaped.
     *
     * @param string       $statement
     * @param array<mixed> $params
     *
     * @return Generator
     * @throws BackendException
     */
    public function fetch(string $statement, array $params = []): Generator
    {
        $resource = $this->getResource($statement);
        yield from $this->backend->fetch($resource, $params);
    }

    /**
     * Executes a plain insert statement and returns the last insert ID.
     * The statement is meant to contain placeholders for prepared statements to be bound with the params.
     * No user params must be included in the statement since only the params are escaped.
     *
     * @param string       $statement
     * @param array<mixed> $params
     *
     * @return int
     * @throws BackendException
     */
    public function insert(string $statement, array $params = []): int
    {
        $resource = $this->getResource($statement);
        return $this->backend->insert($resource, $params);
    }

    /**
     * Runs the given callback within a transaction.
     * If the callback returns false or throws, the transaction is rolled back.
     *
     * @param callable():bool $function
     *
     * @return void
     * @throws BackendException
     */
    public function transactional(callable $function): void
    {
        $this->backend->connect();
        $this->backend->begin();
        $success = false;
        try {
            $success = $function();
        } catch (Throwable $e) {
            throw new BackendException($e->getMessage(), $e->getCode(), $e);
        } finally {
            if ($success) {
                $this->backend->commit();
            } else {
                $this->backend->rollback();
            }
        }
    }

    /**
     * Retrieve a prepared statement from the database backend. Each prepared statement is cached by it's SQL.
     *
     * @param string $statement
     *
     * @return mixed
     * @throws BackendException
     */
    private function getResource(string $statement)
    {
        $statement = trim($statement);
        // First connect or check connection.
        $this->backend->connect();
        // If a statement was already prepared return the cached resource.
        if (isset($this->statements[$statement])) {
            return $this->statements[$statement];
        }
        // Prepare the statement and cache the result.
        return $this->statements[$statement] = $this->backend->prepare($statement);
    }
}
