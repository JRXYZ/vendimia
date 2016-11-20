<?php
namespace Vendimia;

use Vendimia;

/**
 * Session variables handler
 */
class Session extends MutableCollection {
    function __construct()
    {
        // Creamos un session_id en base del proyecto.
        $session_id = md5(Vendimia::$settings['APPID']
            . $_SERVER['HTTP_HOST']);

        // No iniciamos la sesión si venimos por la CLI
        if (Vendimia::$execution_type != 'cli') {
            session_id($session_id);
            session_start();
        }

        parent::__construct();
        $this->setArrayByRef($_SESSION);
    }
}