<?php
namespace Vendimia\Form\Control;

use Vendimia;
use Vendimia\Html;

class Select extends ControlAbstract
{
    
    protected $extra_properties = [
        'multiple' => false    // true cuando permite selección múltiple
    ];

/*	function validate() {

		$valid = true;

		// Es vacío
        $isempty = $this->value === "" || $this->value === [] ;

        // Chequeamos si permite estar vacio
        if ( !$this->empty_allowed && $isempty ) {

            // Es vacio, y no se permite.
            $this->message ( $this->msg_empty );
            $valid = false;

        }
        

        if ( ! $isempty ) { 

            // Esto es raro, pero si es un listado múltiple, aplicamos todas
            // las comprobaciones a cada elemento del array...

            if ( $this->multiple )
                $values = $this->value;
            else
                $values = [ $this->value ];

            foreach ( $values as $value ) {
            
                // Aplicamos las expresiones regulares
                if ( $this->regexp && !preg_match ( '/^' . $this->regexp . '$/', 
                    $value )) {
    
                    // No cumple la expresión regular
                    $this->messager ( $this->msg_regexp );
                    $valid = false;
                }
    
                // Verificamos min y max 
                if ( $this->minval && $value < $this->minval ) {
                    $this->message ( $this->msg_minval, $this->minval );
                    $valid = false;
                }
                if ( $this->maxval && $value > $this->maxval ) {
                    $this->message ( $this->msg_maxval, $this->maxval );
                    $valid = false;
                }
    
                // Verificamos la longitud
                $len = strlen ( $value );
    
                if ( $this->minlen && $len < $this->minlen ) {
                    $this->message ( $this->msg_minlen, $this->minlen );
                    $valid = false;
                }
    
                if ( $this->maxlen && $len > $this->maxlen ) {
                    $this->message ( $this->msg_maxlen, $this->maxlen );
                    $valid = false;
                }
            }
        }

        return $valid;

	}
*/

	/**
	 * Dibuja el HTML para este control
	 */
	function draw( ) {

        // Por cada elemento del $ctrl['list'], dibujamos un 
        // Option. Si es un array, entonces usamos labels
        $vars = [
            'name' => $this->name,
            'id' => $this->name,
        ];

        if ( $this->multiple ) {
            $vars ['multiple'] = 'true';
            $vars ['name'] .= "[]";
        }

        $values = $this->value;
        // Si el array es múltiple, y value es un string vacío,
        // lo convertimos en un array vacio
        if ( $this->multiple && $this->value  == "" ) {
            $values = [];
        }
            
        $options = Html\Helpers::options ( 
            $this->list, 
            $values
        );


        // Creamos una función para dibujar los options
        return $this->htmltag('select', $vars, $options, ['escapecontent' => false ]); 
    }
}
