<?php

namespace BotMan\BotMan\Container;

use ReflectionException;
use Psr\Container\ContainerInterface;
use Illuminate\Contracts\Container\Container;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Illuminate\Container\EntryNotFoundException;

class LaravelContainer implements ContainerInterface
{
    /** @var Container */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if ($this->has($id)) {
            return $this->container->make($id);
        }
        throw new EntryNotFoundException;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        if ($this->container->bound($id) || $this->container->resolved($id)) {
            return true;
        }
        try {
            $this->container->make($id);

            return true;
        } catch (ReflectionException $e) {
            return false;
        }
    }
}
