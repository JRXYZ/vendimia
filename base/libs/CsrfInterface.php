<?php
namespace Vendimia;

interface CsrfInterface
{
    /**
     * Generates a new CSRF token
     */
    public function generateToken();

    /**
     * Obtains the generated token
     */
    public function getToken();
}