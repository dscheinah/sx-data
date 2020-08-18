<?php
namespace Sx\DataTest;

use Psr\Container\NotFoundExceptionInterface;
use Sx\Container\Injector;
use Sx\Data\BackendInterface;
use Sx\Data\Storage;
use Sx\Data\StorageFactory;
use PHPUnit\Framework\TestCase;
use Sx\DataTest\Mock\Backend;

class StorageFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $backend = new Backend();

        $injector = new Injector();
        $injector->set(BackendInterface::class, $backend);

        $factory = new StorageFactory();
        $factory->create($injector, [], Storage::class);

        self::assertTrue(true);

        $this->expectException(NotFoundExceptionInterface::class);
        $factory->create(new Injector(), [], Storage::class);
    }
}
