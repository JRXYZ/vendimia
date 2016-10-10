<?php
// Funciones de ayuda de todo tipo

/** 
 *  Returns a value if $varible exists. Otherwise, return $default
 */
function ifset ( &$variable, $default = null ) {
    if ( isset ( $variable ) )
        return $variable;
        
    return $default;
}

/**
 * Returns the 'basename' of a FQCN
 */
function get_class_basename($classname) {
    $pos = strrpos($classname, '\\');
    if ($pos !== false) {
        $pos++;
    }
    $basename = substr($classname, $pos);

    return $basename;
}

/**
 * Versión mejorada del var_dump
 */
function vardump ( $object ) {

    ob_start();
    call_user_func_array ( 'var_dump', func_get_args());

    $vd = ob_get_contents();
    ob_end_clean();
    $vd = $vd;

    echo "<pre>";
    echo htmlentities ($vd);
    echo "</pre>";
}


/**
 * Crea un objeto Vendimia\S
 */
function _S($text = null)
{
    return new Vendimia\S ( $text );
}

/**
 * Crea un objeto Vendimia\DateTime
 */
function _DT($date = null)
{
    return new Vendimia\DateTime($date);
}

/**
 * Alias de explode(' ', $words);
 */
function _W($words) {
    return explode (' ', $words);
}