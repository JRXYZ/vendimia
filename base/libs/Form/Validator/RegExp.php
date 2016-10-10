<?php
namespace Vendimia\Form\Validator;

/**
 * Validates a value against a regular expression. 
 *
 * The regexp is surrounded by the '%' character, so it must be escaped if 
 * used inside.
 */
class RegExp extends ValidatorAbstract
{
    protected $args = [
        'regexp' => null,
        'messages' => [
            'fail' => '%control% contains invalid characters.',
        ]
    ];
    public function validate()
    {
        $options = '';
        if ($this->args['case_insensitive']) {
            $options .= 'i';
        }
        if (preg_match("%{$this->args['regexp']}%{$options}", $this->control->value) === 0) {
            $this->addMessage('fail');
            return false;
        }
        return true;
    }
}