<?php
namespace Vendimia\Form\Validator;

/**
 * Executes a method inside the form class
 */
class Callback extends ValidatorAbstract
{
    protected $args = [
        'callback' => null,
        'messages' => [
            'error' => "Error executing callback for '%control%'.",
            'fail' => "Callback for '%control%' failed.",
        ]
    ];

    public function validate()
    {
        if (is_null($this->args['callback'])) {
            $this->addMessage('error');
            return false;
        }

        $callback = $this->args['callback'];

        if (is_string($callback)) {
            if (!method_exists($this->control->form, $callback))) {
                $this->addMessage('error');
                return false;
            }

            $return = $this->control->form->$callback($this->control);

            if ($return === true) {
                return true;
            } else {
                $this->addMessage($return);
                return false;
            }

        }
    }
}