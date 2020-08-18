<?php
namespace Sx\Data;

use Generator;

/**
 * This interface defines all methods expected from a database backend to be used with the Storages.
 *
 * @package Sx\Data
 */
interface BackendInterface
{
    /**
     * Is called by the storage before every operation executed. This should create the database connection.
     * If no connection is available an exception is thrown. It is not expected to do a reconnect.
     *
     * @throws BackendException
     */
    public function connect(): void;

    /**
     * Is called once for each different statement by the storage. This method must return a resource usable by execute,
     * fetch and insert as the first parameter.
     * It is meant to be used with prepared statements. So it is safe to assume no user parameters are included.
     * Escaping of arguments is done by binding the parameters with the executing functions.
     *
     * @param string $statement
     *
     * @return mixed
     * @throws BackendException
     */
    public function prepare(string $statement);

    /**
     * Is executed for update, delete and custom operations by the storage.
     * It should return the number of affected rows. All errors are indicated by throwing an exception.
     * The given resource is always the result from a call to prepare. There is no need to support other resources.
     *
     * @param mixed $resource
     * @param array $params
     *
     * @return int
     * @throws BackendException
     */
    public function execute($resource, array $params = []): int;

    /**
     * Is executed for select operations of the storage.
     * It must return a Generator to iterate over the complete result set.
     * The given resource is always the result from a call to prepare. There is no need to support other resources.
     *
     * @param mixed $resource
     * @param array $params
     *
     * @return Generator
     * @throws BackendException
     */
    public function fetch($resource, array $params = []): Generator;

    /**
     * Is executed for insert operations of the storage and must return the last insert ID.
     * The given resource is always the result from a call to prepare. There is no need to support other resources.
     *
     * @param mixed $resource
     * @param array $params
     *
     * @return int
     * @throws BackendException
     */
    public function insert($resource, array $params = []): int;
}
