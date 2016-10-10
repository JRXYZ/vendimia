<?php
namespace Vendimia\Form\Control;

use Vendimia;

class Checklist extends ControlAbstract
{

    protected $extra_properties = [
        'html_element' => []      // Tag que rodeará cada elemento
    ];

    function draw() {
        $html = '';
        foreach ( $this->list as $id => $value ) {

            $optid = $this->id() . "_" . $id;


            $label = $this->htmltag ( 'label', [
                'for' => $optid,
            ], $value);

            $params = [
                'type' => 'checkbox',
                'name' => $this->name . "[]", 
                'value' => $id,
                'id' => $optid,
            ];

            if ( is_array ( $this->value ) && in_array ( $optid,  $this->value ) )
                $params['checked'] = 'true';

            $ctrl = $this->htmltag ( 'input', $params );

            $all = $ctrl . $label;

            if ( $this->html_element ) {

                $tag = $this->html_element;
                $tag[2] = [$all];    // Con esto evitamos que escape.

                $html .= $this ->htmltag ( $tag );
            }
            else {
                $html .= $all;
            }
        }

        return $html;
    }
    function validate() {
        // Esto siempre será válido
        return true;
    }
}
