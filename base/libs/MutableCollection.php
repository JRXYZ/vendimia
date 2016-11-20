<?php
namespace Vendimia;

/**
 * Mutable collection. Every method modifies the array in-place.
 */
class MutableCollection implements \ArrayAccess
{
    private $array = [];

    /**
     * Creates a collection from a array
     */
    public function __construct(array $array = [])
    {
        $this->array = $array;
    }

    /**
     * Sets the array by reference. 
     *
     * Useful for working with a pre-existent array.
     */
    public function setArrayByRef(&$array) 
    {
        $this->array = &$array;
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

    /**
     * Adds an element at the end of the collection.
     */
    public function add($value, $key = null)
    {
        if (is_null($key)) {
            $this->array[] = $value;   
        } else {
            $this->array[$key] = $value;
        }

        return $this;
    }


    /**
     * Returns whether this element exist in the collection
     */
    public function has($element)
    {
        if (is_null($this->array)) {
            return false;
        }
        
        return key_exists($element, $this->array);
    }


    /**
     * Magic function for accessing elements as object properties
     */
    public function __get($element) {
        return $this->get($element);
    }

    public function __set($element, $value) {
        $this->add($value, $element);
        return $this;
    }

    


    // ArrayAccess implementation

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->add($value, $offset);
    }
    public function offsetUnset($offset) {}
}
