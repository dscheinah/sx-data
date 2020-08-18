<?php
namespace Sx\Data;

use Sx\Container\FactoryInterface;
use Sx\Container\Injector;

/**
 * Factory for any Storages which only requires the default Backend.
 *
 * @package Sx\Data
 */
class StorageFactory implements FactoryInterface
{
    /**
     * Creates the requested Storages with the default Backend represented by BackendInterface.
     *
     * @param Injector $injector
     * @param array    $options
     * @param string   $class
     *
     * @return Storage
     */
    public function create(Injector $injector, array $options, string $class): Storage
    {
        return new $class($injector->get(BackendInterface::class));
    }
}
