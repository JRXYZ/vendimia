<?php
namespace Vendimia\Form\Validator;

/**
 * Validates a value against a minimun/maximun string length.
 */
class Length extends ValidatorAbstract
{
    protected $args = [
        'min' => null,
        'max' => null,
        'messages' => [
            'max' => "'%control%' value length must be less or equal than %max% characters.",
            'min' => "'%control%' value lenght must be at least %min% characters.",
        ]
    ];

    public function validate()
    {
        if ($this->args['min'] && mb_strlen($this->control->value) < $this->args['min']) {
            $this->addMessage('min');
            return false;
        }
        if ($this->args['max'] && mb_strlen($this->control->value) > $this->args['max']) {
            $this->addMessage('max');
            return false;
        }

        // Seems valid
        $this->is_valid = true;
        return true;
    }
}