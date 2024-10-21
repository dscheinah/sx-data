<?php
namespace Sx\DataTest\Mock;

use Generator;
use Sx\Data\BackendException;
use Sx\Data\BackendInterface;

class Backend implements BackendInterface
{
    public const RESULT_1 = 1;
    public const RESULT_2 = 2;

    public $connected = false;

    public $prepared = [];

    public $executed = [];

    public $fetched = [];

    public $inserted = [];

    public $beginCalled = false;

    public $commitCalled = false;

    public $rollbackCalled = false;

    public function connect(): void
    {
        $this->connected = true;
    }

    public function prepare(string $statement)
    {
        $this->prepared[$statement] = true;
        return $statement;
    }

    public function execute($resource, array $params = []): int
    {
        $this->executed[$resource] = $params;
        return self::RESULT_1;
    }

    public function fetch($resource, array $params = []): Generator
    {
        $this->fetched[$resource] = $params;
        yield self::RESULT_1;
        yield self::RESULT_2;
    }

    public function insert($resource, array $params = []): int
    {
        $this->inserted[$resource] = $params;
        return self::RESULT_1;
    }

    public function begin(): void
    {
       $this->beginCalled = true;
    }

    public function commit(): void
    {
        $this->commitCalled = true;
    }

    public function rollback(): void
    {
        $this->rollbackCalled = true;
    }
}
