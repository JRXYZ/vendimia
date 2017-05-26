<?php
namespace Vendimia;

use Vendimia;

class Csrf implements CsrfInterface
{
    /** Generated CSRF token */
    private $token = null;

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

        return $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function __construct() 
    {
        if (is_null(Vendimia::$session->security_token)) {
            $this->token = $this->generateToken();
            Vendimia::$session->security_token = $this->token;
        } else {
            $this->token = Vendimia::$session->security_token;
        }
    }
}
