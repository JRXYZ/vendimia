<?php
namespace Vendimia\Form\Control;

/**
 * Control for drawing password text boxes.
 */
class Password extends Text
{
    public function draw()
    {
        return parent::draw([
            'type' => 'password',
            'value' => '',
        ]);
    }    
}
