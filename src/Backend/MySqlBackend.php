<?php
namespace Sx\Data\Backend;

use Generator;
use mysqli;
use mysqli_stmt;
use RuntimeException;
use Sx\Data\BackendInterface;
use Sx\Data\BackendException;

/**
 * This is a simple implementation of a database backend for MySQL using the mysql interface.
 */
class MySqlBackend implements BackendInterface
{
    /**
     * Defines all available options in the order of the mysqli constructor parameters.
     * This way the options values can be expanded in connect.
     *
     * @var array
     */
    private $options = [
        'server' => null,
        'user' => null,
        'password' => null,
        'database' => null,
        'port' => null,
        'socket' => null,
    ];

    /**
     * The current mysqli instance. It will be created using connect.
     *
     * @var mysqli
     */
    private $mysqli;

    /**
     * Creates the backend and sets the options. Only options provided in the defaults will be applied.
     * To create the underlying mysqli instance, connect must be called.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        foreach (array_keys($this->options) as $key) {
            if (isset($options[$key])) {
                $this->options[$key] = $options[$key];
            }
        }
    }

    /**
     * Creates the database connection on first call. Consecutive calls just check if the connection still exists.
     * If the initial connection or the check fails an exception is thrown.
     *
     * @throws BackendException
     */
    public function connect(): void
    {
        if ($this->mysqli) {
            // Use the ping function to test if the connection is still alive.
            // Do not attempt to reconnect as the application should not need to do that.
            if (!$this->mysqli->ping()) {
                throw new BackendException(
                    'connection to mysql was lost: ' . $this->mysqli->error,
                    $this->mysqli->errno
                );
            }
            return;
        }
        // Create the instance with the provided options. The options array has correctly ordered defaults and can not
        // be extended. So parameter expansion is valid to use here.
        $this->mysqli = new mysqli(...array_values($this->options));
        if ($this->mysqli->connect_errno) {
            throw new BackendException(
                'error connection to mysql: ' . $this->mysqli->connect_error,
                $this->mysqli->connect_errno
            );
        }
        // Everything should be unicode by now. So simply assume this without an extra option.
        $this->mysqli->query('SET NAMES utf8;');
        $this->mysqli->query('SET CHARACTER SET utf8;');
        $this->mysqli->set_charset('utf8');
    }

    /**
     * Sends the statement to the database as a prepared statement and returns it to be used in all executing functions.
     *
     * @param string $statement
     *
     * @return mysqli_stmt
     * @throws BackendException
     */
    public function prepare(string $statement): mysqli_stmt
    {
        $resource = $this->mysqli->prepare($statement);
        if (!$resource) {
            throw new BackendException('error preparing statement: ' . $this->mysqli->error, $this->mysqli->errno);
        }
        return $resource;
    }

    /**
     * Executes the statement from prepare with the given bound parameters. The number of affected rows is returned.
     * To retrieve a result from select operations or the last insert ID the fetch/ insert methods must be used.
     * The given parameters must be correctly typed. Currently only numeric, bool and string are supported.
     * Each error is indicated by an exception since the affected rows can be zero for successful statements.
     *
     * @param mysqli_stmt $resource
     * @param array       $params
     *
     * @return int
     * @throws BackendException
     * @throws RuntimeException
     */
    public function execute($resource, array $params = []): int
    {
        // Since the interface cannot provide type safety for the resource parameter it must be checked manually.
        if (!$resource instanceof mysqli_stmt) {
            throw new RuntimeException('only mysql_stmt are supported for queries');
        }
        // Parameter binding in MySQL needs type hints. So iterate all parameters and create the type sequence.
        $types = '';
        foreach ($params as $param) {
            // NULL does not have a corresponding type. But it works by using a string.
            if ($param === null) {
                $types .= 's';
                continue;
            }
            $type = gettype($param);
            switch ($type) {
                // Boolean are interpreted as small integers with just 0 and 1 as a value.
                case 'boolean':
                case 'integer':
                    $types .= 'i';
                    break;
                // There is no difference in the gettype response for float and double.
                case 'double':
                    $types .= 'd';
                    break;
                case 'string':
                    $types .= 's';
                    break;
                default:
                    // No type casting. Every unknown type is an error.
                    throw new BackendException('unsupported param type: ' . $type);
            }
        }
        // Bind the parameters with the computes types and execute the statement.
        if ($params && !$resource->bind_param($types, ...$params)) {
            throw new BackendException('error binding parameter', $resource->errno);
        }
        if (!$resource->execute()) {
            throw new BackendException('error executing: ' . $resource->error, $resource->errno);
        }
        return $resource->affected_rows;
    }

    /**
     * Executes the statement with the given bound parameters using the execute function. Afterwards the result set
     * is retrieved using a Generator to avoid high memory usage for partially read results.
     * If any error occurs an exception is thrown.
     *
     * @param mysqli_stmt $resource
     * @param array       $params
     *
     * @return Generator
     * @throws BackendException
     */
    public function fetch($resource, array $params = []): Generator
    {
        // The execute function will check the resource type and params.
        $this->execute($resource, $params);
        $result = $resource->get_result();
        if (!$result) {
            throw new BackendException('error getting result: ' . $resource->error, $resource->errno);
        }
        // Iterate over all results and fetch each one by one.
        $count = $result->num_rows;
        for ($no = 0; $no < $count; $no++) {
            $result->data_seek($no);
            yield $result->fetch_assoc();
        }
    }

    /**
     * Executes the statement with the bound parameters using execute and return the last insert ID.
     *
     * @param mysqli_stmt $resource
     * @param array       $params
     *
     * @return int
     * @throws BackendException
     */
    public function insert($resource, array $params = []): int
    {
        // The execute function will check the resource type and params.
        $this->execute($resource, $params);
        return $resource->insert_id;
    }
}
