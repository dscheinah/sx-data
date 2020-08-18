<?php
namespace Sx\DataTest;

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
}
