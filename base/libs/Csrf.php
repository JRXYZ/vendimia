<?php
namespace Vendimia;

use Vendimia;

class Csrf implements CsrfInterface
{
    /** Generated CSRF token */
    static $token = null;

    public function generateToken()
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' .
            'abcdefghijklmnopqrstuvwxyz' .
            '0123456789';
        $lc = strlen($letters) - 1; 
        $token = '';
        for ($i = 0; $i < 48; $i++) { 
            $token .= $letters{rand(0, $lc)};
        }

        static::$token = $token;
        Vendimia::$security_token = $token;
        Vendimia::$session->security_token = $token;
        return $token;
    }

    public function getToken()
    {
        if (is_null(static::$token)) {
            return $this->generateToken();
        } else {
            return static::$token;
        }
    }

    public function getSavedToken()
    {
        // Debería de existir...
        return Vendimia::$session->security_token;
    }
}
