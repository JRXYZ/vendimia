<?php
namespace Vendimia;

use Vendimia;

/**
 * Decorator-like class to integration with Vendimia
 */
class Form extends Form\Form
{
    public function __construct()
    {
        parent::__construct(new Csrf);
    }

    /**
     * Process forms with POST data.
     */
    function isValidFromPost()
    {
        if (Vendimia::$request->isPost()) {
            $this->setValues(Vendimia::$request->getParsedBody());
            return $this->isValid();
        }
        return false;        
    }

}
