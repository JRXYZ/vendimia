<?php
namespace Vendimia\Form\Control;

use Vendimia;

/**
 * Crea dos textbox, uno hidden, para hacer búsquedas de valores via ajax,
 * usando dropdown.js y dropdown.css
 */
class Dropdown extends ControlAbstract
{
	protected $extra_properties = [
		'ajax_url' => '',
		'ajax_method' => 'post',

		// Variable que será enviado al script con el valor de
		// la caja de búsqueda
		'ajax_search_variable' => 'search',

		// Variables extras para el script
		'ajax_search_extra' => [],

		// Variable que deberá retornar el script. con el listado
		// de resultados
		'ajax_return_variable' => 'result',

		// Funcion que se ejecutará al aceptar el resultado de la
		// búsqueda.
		'ajax_close_callback' => false,

		'html_sufix' => '_search'
	];

	/**
	 * Campo extra donde se guardará la cadena correspondiente al ID
	 */
	private $search_field = '';

	function __construct( $name, $properties, $form ) {
		parent::__construct ( $name, $properties, $form );

		$this->search_field = $this->id() . $this->html_sufix;

		// Añadimos un value_holder para el string
		$this->form->value_holders [ $this->search_field ] = '';

		// Cargamos dropdown.js y dropdown.css
		Vendimia\view\add_js ( 'ajax');
		Vendimia\view\add_js_css ( 'dropdown');
	}

	/**
	 * Asigna un valor a este control.
	 *
	 * Este set() acepta un array, para colocar la cadena de búsqieda
	 */
	function set ( $value )  {
		if ( is_array ( $value )) {
			$this->form->value_holders [ $this->search_field ] = current ( $value );
		}
		else
			$this->value = $value;
	}

	function validate() {

		$valid = true;

        // Chequeamos si permite estar vacio
        if ( !$this->empty && empty( $this->value ) ) {
            // Es vacio, y no se permite.
            $this->message ( $this->msg_empty );
            $valid = false;
        }

        return $valid;
	}
	function draw() {

		if ( $this->ajax_url == "" ) {
			throw new DefinitionError ( "DropdownField: You must set the 'ajax_url' variable." );
		}

		$html = Html\Tag::input ([
			'id' => $this->id(),
			'type' => 'hidden',
			'name' => $this->name,
			'value' => $this->value,
		]);

		$html .= $this->htmltag ( 'input', [
			'id' => $this->search_field,
			'type' => 'text',
			'name' => $this->search_field,
			'value' => $this->form->value_holders [ $this->search_field ],
			'autocomplete' => 'off',
		]);

		// Ahora, un poco de javascript
		$variables = $this->ajax_search_extra;
		$variables["%{$this->ajax_search_variable}"] = "Vendimia.id('{$this->search_field}').value";

		$variables = Vendimia\json ( $variables );

		$dropdown_name = 'dropdown_' . $this->name;
		$script = <<<EOF
// Dropdown field '{$this->name}' activator script.
{$dropdown_name} = dropdown.create ( '{$this->name}', '{$this->search_field}', function(accept) {
    ajax.{$this->ajax_method} ( '{$this->ajax_url}', {$variables}, function(ajax_data){
        if (accept) {
        	// If accept is true, the result must be setted in the textboxes
        	// directly.
        	data = ajax_data.{$this->ajax_return_variable}
        	id = null
        	for (id in data ) {
        		break;
        	}
        	if ( id ) {
	        	Vendimia.id ('{$this->name}').value = id
	        	Vendimia.id ('{$this->search_field}').value = data[id]
	        }
	        else {
	        	// Sin resultados
	        	Vendimia.id ('{$this->name}').value = ''
	        	Vendimia.id ('{$this->search_field}').value = ''
	        }

	        // Execute any callback
	        if ( Vendimia.id({$dropdown_name}) && Vendimia.id({$dropdown_name}).close_callback )
	            Vendimia.id({$dropdown_name}).close_callback()

	        dropdown.close ()
        }
        else {
			dropdown.fill ( {$dropdown_name}, ajax_data.{$this->ajax_return_variable} )
        }
    })
})

EOF;
		// Añadimos el script que ejecutará al cerrar.
		if ( $this->ajax_close_callback ) {
			$script .= "$dropdown_name.close_callback = {$this->ajax_close_callback}";
		}

		//$html .= Html\Tag ('script', ['type' => 'application/javascript'], [$script]);
		// Y lo colocamos en el formulario
		$this->form->add_script ( $script );


		return $html;
	}
}