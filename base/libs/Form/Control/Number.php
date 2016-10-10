<?php
namespace Vendimia\Form\Control;

class Number extends Text
{
    function draw() {
        return parent::draw([
            'type' => 'number',
            'value' => '',
        ]);
    }    
   
}