<?php
namespace Vendimia\Form;

/**
 * Trait to provide a magic function to return FQCN  static function names.
 */
abstract class FunctionHelper
{
    /**
     * Generates the function name
     */
    public static function __callStatic($name, $args)
    {
        $fullclass = get_called_class();
        $slash = strrpos($fullclass, '\\');
        $class = ucfirst(strtolower(substr($fullclass, ++$slash)));

        $function = $fullclass . '::' . $name . $class;

        return $function;
    }   
}