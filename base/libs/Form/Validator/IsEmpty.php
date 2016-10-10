<?php
namespace Vendimia\Form\Validator;

class IsEmpty extends ValidatorAbstract
{
    protected $args = [
        'messages' => [
            'empty' => "'%control%' cannot be empty.",
        ]
    ];

    public function validate()
    {
        if ($this->control->isEmpty()) {
            $this->addMessage('empty');
            return false;
        }

        return true;
    }
}