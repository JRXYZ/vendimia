<?php
namespace Vendimia;

class Collection implements \ArrayAccess
{
    private $array = null;

    /**
     * Creates a collection from a array
     */
    public function __construct(array $array = [])
    {
        $this->array = $array;
    }

    /**
     * Returns a collection value
     */
    public function get($element, $default_value = null)
    {
        if ($this->has($element)) {
            return $this->array[$element];
        } else {
            return $default_value;
        }
    }

    public function __get($element) {
        return $this->get($element);
    }

    /**
     * Returns whether this element exist in the collection
     */
    public function has($element)
    {
        return array_key_exists($element, $this->array);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value) {}
    public function offsetUnset($offset) {}
}