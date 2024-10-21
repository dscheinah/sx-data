<?php

namespace Sx\DataTest\Backend {

    use mysqli;
    use mysqli_stmt;
    use Sx\Data\Backend\MySqlBackend;
    use PHPUnit\Framework\TestCase;
    use Sx\Data\BackendException;

    class MySqlBackendTest extends TestCase
    {
        private const OPTIONS = [
            'server' => 'server.local',
            'user' => 'user',
            'password' => 'password',
            'database' => 'database',
            'port' => 1337,
            'socket' => '/var/run/mysql.sock',
        ];

        private $backend;

        protected function setUp(): void
        {
            $this->backend = new MySqlBackend(self::OPTIONS);
        }

        public function testConnect(): void
        {
            try {
                $this->backend->connect();
                $this->backend->connect();
                self::assertEquals(array_values(self::OPTIONS), mysqli::$options);
                self::assertTrue(true);
            } catch (BackendException $e) {
                self::assertFalse(true);
            }
            $backend = new MySqlBackend();
            try {
                $backend->connect();
                self::assertTrue(false);
            } catch (BackendException $e) {
                self::assertEquals(42, $e->getCode());
            }
            try {
                $backend->connect();
                self::assertTrue(false);
            } catch (BackendException $e) {
                self::assertEquals(42, $e->getCode());
            }
        }

        public function testPrepare(): void
        {
            try {
                $this->backend->connect();
                self::assertNotNull($this->backend->prepare('SELECT * FROM `table`;'));
            } catch (BackendException $e) {
                self::assertFalse(true);
            }
            $this->expectException(BackendException::class);
            $this->expectExceptionCode(42);
            self::assertNull($this->backend->prepare(mysqli::ERROR_STATEMENT));
        }

        public function testInsert(): void
        {
            try {
                $this->backend->connect();
                self::assertEquals(1, $this->backend->insert(new mysqli_stmt(), ['test', true]));
            } catch (BackendException $e) {
                self::assertFalse(true);
            }
            try {
                $this->backend->insert(new mysqli_stmt(true), ['param']);
                self::assertTrue(false);
            } catch (BackendException $e) {
                self::assertEquals(42, $e->getCode());
            }
            try {
                $this->backend->insert(new mysqli_stmt(false, true));
                self::assertTrue(false);
            } catch (BackendException $e) {
                self::assertEquals(42, $e->getCode());
            }
            try {
                $this->backend->insert(new mysqli_stmt(), [[]]);
                self::assertTrue(false);
            } catch (BackendException $e) {
                self::assertTrue(true);
            }
        }

        public function testFetch(): void
        {
            try {
                $this->backend->connect();
                $result = iterator_to_array($this->backend->fetch(new mysqli_stmt(), [42, 23.0, null]));
                self::assertCount(10, $result);
            } catch (BackendException $e) {
                self::assertFalse(true);
            }
            $this->expectException(BackendException::class);
            $this->expectExceptionCode(42);
            iterator_to_array($this->backend->fetch(new mysqli_stmt(false, false, true)));
        }

        public function testBegin(): void
        {
            try {
                $this->backend->connect();
                $this->backend->begin();
                self::assertTrue(true);
            } catch (BackendException $e) {
                self::assertFalse(true);
            }
            try {
                $this->backend->begin();
                self::assertFalse(true);
            } catch (BackendException $e) {
                self::assertTrue(true);
            }
        }

        public function testCommit(): void
        {
            try {
                $this->backend->connect();
                $this->backend->commit();
                self::assertTrue(true);
            } catch (BackendException $e) {
                self::assertFalse(true);
            }
            try {
                $this->backend->commit();
                self::assertFalse(true);
            } catch (BackendException $e) {
                self::assertTrue(true);
            }
        }

        public function testRollback(): void
        {
            try {
                $this->backend->connect();
                $this->backend->rollback();
                self::assertTrue(true);
            } catch (BackendException $e) {
                self::assertFalse(true);
            }
            try {
                $this->backend->rollback();
                self::assertFalse(true);
            } catch (BackendException $e) {
                self::assertTrue(true);
            }
        }
    }
}

namespace {

    class mysqli
    {
        public const ERROR_STATEMENT = 'error';

        public static $options = [];

        public $connect_errno = 0;

        public $connect_error = '';

        public $errno = 0;

        public $error = '';

        private $beginCounter = 0;

        private $commitCounter = 0;

        private $rollbackCounter = 0;

        public function __construct()
        {
            $options = array_filter(func_get_args());
            if (!$options) {
                $this->connect_errno = 42;
                $this->connect_error = 'error';
            }
            self::$options = $options;
        }

        public function prepare($statement): ?mysqli_stmt
        {
            $this->errno = 0;
            $this->error = '';
            if ($statement === self::ERROR_STATEMENT) {
                $this->errno = 42;
                $this->error = 'error';
                return null;
            }
            return new mysqli_stmt();
        }

        public function query(): void
        {
        }

        public function set_charset(): void
        {
        }

        public function ping(): bool
        {
            if (!self::$options) {
                $this->errno = 42;
                $this->error = 'error';
                return false;
            }
            $this->errno = 0;
            $this->error = '';
            return true;
        }

        public function begin_transaction(): bool
        {
            return !$this->beginCounter++;
        }

        public function commit(): bool
        {
            return !$this->commitCounter++;
        }

        public function rollback(): bool
        {
            return !$this->rollbackCounter++;
        }
    }

    class mysqli_stmt
    {
        public $affected_rows = 0;

        public $error = '';

        public $errno = 0;

        public $insert_id = 1;

        private $bindError;

        private $execError;

        private $resultError;

        public function __construct($bindError = false, $execError = false, $resultError = false)
        {
            $this->bindError = $bindError;
            $this->execError = $execError;
            $this->resultError = $resultError;
        }

        public function bind_param(): bool
        {
            if ($this->bindError) {
                $this->errno = 42;
                $this->error = 'error';
                return false;
            }
            $this->errno = 0;
            $this->error = '';
            return true;
        }

        public function execute(): bool
        {
            if ($this->execError) {
                $this->errno = 42;
                $this->error = 'error';
                return false;
            }
            $this->errno = 0;
            $this->error = '';
            return true;
        }

        public function get_result(): ?mysql_result
        {
            if ($this->resultError) {
                $this->errno = 42;
                $this->error = 'error';
                return null;
            }
            $this->errno = 0;
            $this->error = '';
            return new mysql_result();
        }
    }

    class mysql_result
    {
        public $num_rows = 10;

        public function data_seek(): void
        {
        }

        public function fetch_assoc(): string
        {
            return 'result';
        }
    }
}
