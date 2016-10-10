<?php
namespace Vendimia;

class ServiceContainer
{
    /** Object builders */
    private $builders = [];

    /** Stored object */
    private $objects = [];

    /**
     * Stores a closure for creating an object.
     */
    public function bind($name, $closure)
    {
        if (is_object($closure)) {
             if ($closure instanceof \Closure) {
                $this->builders[$name] = $closure;
             }
             else {
                $this->objects[$name] = $closure;
             }
        }
    }

    /**
     * Obtains an already created instance of $name. Crates it otherwise.
     */
    public function get($name, ...$args)
    {
        if (isset($this->objects[$name])) {
            return $this->objects[$name];
        } else {
            $object = $this->make($name, ...$args);
            $this->objects[$name] = $object;
            return $object;
        }
    }

    /**
     * Instanciate a new object. Doesn't save it.
     */
    public function make($name, ...$args)
    {
        if (!key_exists($name, $this->builders)) {
            throw new \RuntimeException ("Service '$name' undefined.");
        }
        $closure = $this->builders[$name];
        return $closure(...$args);
    }
}