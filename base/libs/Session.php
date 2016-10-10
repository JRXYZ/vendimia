<?php
namespace Vendimia;

use Vendimia;

/**
 * Handle session variables for this project
 */
class Session {
    
    /**
     * Session token, used for distinguish this app from another running on
     * this same host.
     */
    public $token = '';

    function __construct()
    {

        // Usamos el ID de la sesión para generar un código de sesión
        $this->token = hash_hmac ( 'sha256', 
            session_id(),
            Vendimia::$settings['APPID']);

        // Enviamos el token como cookie al usuario
        /*if ( Vendimia::$EXECUTION_TYPE != 'CLI') {
            setcookie("Vendimia-session-token", $this->token, 0, Vendimia::$base_url);
        }*/
        
    }

    /**
     * "Getter"
     */
    function __get($var) 
    {
        return ifset($_SESSION[$this->token][$var]);
    }

    /**
     * "Setter"
     */
    function __set($var, $val)
    {
        $_SESSION[$this->token][$var] = $val;
    }

    /**
     * "Unsetter"
     */
    function __unset ($var)
    {
        unset ( $_SESSION[$this->token][$var] );
    }
}
