<?php

namespace Sx\DataTest\Backend;

use Sx\Container\Injector;
use Sx\Data\Backend\MySqlBackend;
use Sx\Data\Backend\MySqlBackendFactory;
use PHPUnit\Framework\TestCase;

class MySqlBackendFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new MySqlBackendFactory();
        $factory->create(new Injector(), ['mysql' => ['host' => 'localhost']], MySqlBackend::class);
        $factory->create(new Injector(), [], '');
        self::assertTrue(true);
    }
}
