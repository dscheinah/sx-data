<?php
namespace Sx\DataTest;

use Exception;
use Sx\Data\BackendException;
use Sx\Data\Storage;
use PHPUnit\Framework\TestCase;
use Sx\DataTest\Mock\Backend;

class StorageTest extends TestCase
{
    private $storage;

    private $backend;

    protected function setUp(): void
    {
        $this->backend = new Backend();
        $this->storage = new Storage($this->backend);
    }

    public function testFetch(): void
    {
        $statement = 'SELECT * FROM `table` WHERE `column` = ?;';
        $params = ['test'];
        try {
            $result = $this->storage->fetch($statement, $params);
            self::assertEquals([Backend::RESULT_1, Backend::RESULT_2], iterator_to_array($result));
            self::assertTrue($this->backend->connected);
            self::assertTrue($this->backend->prepared[$statement]);
            self::assertEquals($params, $this->backend->fetched[$statement]);
        } catch (BackendException $e) {
            self::assertFalse(true);
        }
    }

    public function testInsert(): void
    {
        $statement = 'INSERT INTO `table` VALUES (?);';
        $params = ['test'];
        try {
            $result = $this->storage->insert($statement, $params);
            self::assertEquals(Backend::RESULT_1, $result);
            self::assertTrue($this->backend->connected);
            self::assertTrue($this->backend->prepared[$statement]);
            self::assertEquals($params, $this->backend->inserted[$statement]);
        } catch (BackendException $e) {
            self::assertFalse(true);
        }
    }

    public function testExecute(): void
    {
        $statement = 'UPDATE `table` SET `column` = ?;';
        $params = ['test'];
        try {
            $result = $this->storage->execute($statement, $params);
            self::assertEquals(Backend::RESULT_1, $result);
            self::assertTrue($this->backend->connected);
            self::assertTrue($this->backend->prepared[$statement]);
            self::assertEquals($params, $this->backend->executed[$statement]);

            $this->storage->execute($statement, []);
            self::assertEquals([], $this->backend->executed[$statement]);
        } catch (BackendException $e) {
            self::assertFalse(true);
        }
    }

    public function testTransactional(): void
    {
        try {
            $this->backend->beginCalled = false;
            $this->backend->commitCalled = false;
            $this->backend->rollbackCalled = false;
            $this->storage->transactional(fn () => true);
            self::assertTrue($this->backend->beginCalled);
            self::assertTrue($this->backend->commitCalled);
            self::assertFalse($this->backend->rollbackCalled);
        } catch (BackendException) {
            self::assertFalse(true);
        }
        try {
            $this->backend->beginCalled = false;
            $this->backend->commitCalled = false;
            $this->backend->rollbackCalled = false;
            $this->storage->transactional(fn () => false);
            self::assertTrue($this->backend->beginCalled);
            self::assertFalse($this->backend->commitCalled);
            self::assertTrue($this->backend->rollbackCalled);
        } catch (BackendException) {
            self::assertFalse(true);
        }
        try {
            $this->backend->beginCalled = false;
            $this->backend->commitCalled = false;
            $this->backend->rollbackCalled = false;
            $this->storage->transactional(fn () => throw new Exception());
            self::assertTrue(false);
        } catch (BackendException) {
            self::assertTrue($this->backend->beginCalled);
            self::assertFalse($this->backend->commitCalled);
            self::assertTrue($this->backend->rollbackCalled);
        }
    }
}
