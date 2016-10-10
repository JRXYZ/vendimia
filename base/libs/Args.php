<?php

/**
 * Parámetros (argumentos) a la llamada de un controlador.
 */
namespace Vendimia;

class Args implements \ArrayAccess {
    public $container = [];

    /**
     * Busca una variable $name en los valores del contenedor, y lo 
     * convierte en un elemento de $container
     *
     * Si ya existe un elemento NO lo sobreescribe.
     */
    private function extract_named( $names ){

        reset ( $this->container );

        while ( $each = each ( $this->container ) ) {
            $name = $each[1];
            if ( in_array ( $name, $names ) ) {

                // Solo procesamos strings/// o variables que no existan.
                if ( !is_string ( $name )  )
                    continue;

                $this->container [ $name ] = each ( $this->container )[1];
            }
        }
    }

    /**
     * Appends an array to the arguments.
     */
    function append(array &$data) 
    {
        $this->container = array_merge ( $this->container, $data );
    }

    /**
     * Prepends an array to the arguments.
     */
    function prepend(array &$data)
    {
        array_unshift ( $this->container, $data );
    }

    /**
     * Obtains a named argument.
     * 
     * If no arguments are passed, returns all the controller arguments.
     */
    function get() 
    {
        $vars = func_get_args();
        if ( $vars ) {
            $this->extract_named ( $vars );
            $res = [];
            foreach ( $vars as $var ) {
                if ( isset ( $this->container [$var] ))
                    $res[$var] = $this->container [$var];
                else
                    $res[$var] = null;
            }

            return $res;
        }
        else {
            return $this->container;
        }
    }

    /* ArrayAccess */

    function offsetExists( $offset ) {
        return isset ( $this->container [ $offset ] );
    }
    function offsetGet( $offset ) {
        return ifset ( $this->container [ $offset ] );
    }
    function offsetSet( $offset, $value ) {
        $this->container [ $offset ] = $value;
    }
    function offsetUnset( $offset ) {
        unset ( $this->container [ $offset ] );
    }
}