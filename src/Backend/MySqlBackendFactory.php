<?php
namespace Sx\Data\Backend;

use Sx\Container\FactoryInterface;
use Sx\Container\Injector;

/**
 * The default implementation to create a MySqlBackend.
 */
class MySqlBackendFactory implements FactoryInterface
{
    /**
     * Creates the MySqlBackend using the options for 'mysql' from the global configuration state.
     *
     * @param Injector             $injector
     * @param array<string, mixed> $options
     * @param string               $class
     *
     * @return MySqlBackend
     */
    public function create(Injector $injector, array $options, string $class): MySqlBackend
    {
        return new MySqlBackend($options['mysql'] ?? []);
    }
}
