<?php
namespace Vendimia\Form\Control;

use Vendimia;

/**
 * Campo de Hora. Dibuja un tag INPUT con type="time".
 */
class Time extends ControlAbstract
{
    function draw() {
        return parent::draw([
            'type' => 'time',
            'name' => $this->name,
            'id' => $this->name,
            'value' => $this->value,
        ]);
    }
}