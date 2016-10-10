<?php
namespace Vendimia;

/**
 * Extended exception. Accepts an array for debugging purposes.
 */
class Exception extends \Exception 
{
    public $__EXPORTED_DATA = [];

    public function __construct($message = "", $exported = []) {
        $this->__EXPORTED_DATA = $exported;
        parent::__construct($message, 0, null);
    }    
}

// This is not PSR-ish, but using a separated file for each one seems a little
// overkill to me.
class Inception extends Exception {}
class RouteException extends Exception {}
class ControllerNotFound extends Exception {}
class AppRegisterException extends Exception {}