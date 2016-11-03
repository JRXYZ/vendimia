<?php
namespace Vendimia\Form\Control;

use Vendimia;

class Checkbox extends ControlAbstract {
    protected $extra_properties = [
        'style' => 'normal',  // 'flat' dibuja el label al lado del widget
    ];

    function setValue($value) 
    {
        $this->value = (bool)$value;
    }

    public function getValue()
    {
        return (bool)$this->value;
    }

    function draw() 
    {
        // El checkbox field se dibuja de forma distinta: El label
        // va después del widget.

        $vars = [
            'type' => 'checkbox',
            'name' => $this->name,
            'id' => $this->id(),
        ];

        if ($this->value) {
            $vars ['checked'] = 'true';
        }

        $widget = $this->htmltag('input', $vars);

        if ($this->style == 'flat') {
            $label = $this->drawLabel(false);
            $info = $this->drawInfo();
            
            $this->draw_label = false;
            $this->draw_info = false;
        
            $control = $widget . ' ' . $label . $info;
        }
        else {
            $this->draw_label = true;
            $control = $widget;
        }

        
        return $control;
    }

    function validate() {
        // Este control simepre es válido
        return true;
    }
}
