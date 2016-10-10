<?php
namespace Vendimia;

class S implements \ArrayAccess, \Iterator
{

	/**
	 * La cadena literal
	 * @var string
	 */
	private $cadena;

    /**
     * La codificación de la cadena
     */
    private $encoding;

    /**
     * La posición del iterador
     */
    private $pos_iter;

	/**
	 * Mapeo de comandos de esta clase, con los comandos PHP
	 * @var array
	 */
	private $funciones = [
		'toUpper' => 'mb_strtoupper',
        'toLower' => 'mb_strtolower',
        'slice' => 'mb_substr',
        'length' => 'mb_strlen',
        'indexOf' => 'mb_strpos',
        'pad' => 'str_pad',
	];

	/**
	 * Constructor from a string
     *
     * @param string $string String literal
     * @param string $encoding Force encoding to string. Default autodetecs.
	 */
	function __construct ($string = '', $encoding = false)
    {
        $this->cadena = (string)$cadena;

        if (!$codificación) {
            $this->codificación = mb_detect_encoding($cadena);
        }
        else {
            $this->codificación = $codificación;
        }
	}

	function __call($funcion, $args = [])
    {
        return $this->ejecuta_función($funcion, $args);
	}

    /**
     * Ejecuta funciones PHP sobre la cadena. Usa la tabla de alias 
     * $funciones.
     *
     * @return S Nuevo objeto S con el resultado
     */
    private function ejecuta_función ($funcion, $args = [])
    {
        // Si existe un equivalente en $thid->_functions, lo
        // usamos.
        if ( array_key_exists ( $funcion, $this->funciones )) {
            $funcion_real = $this->funciones [ $funcion ];
        }
        else {
            $funcion_real = $funcion;
        }

        // Existe la función?
        if ( !is_callable ( $funcion_real ) ) {
            throw new BadFunctionException ( "$funcion no es una función válida.");
        }

        // Colocamos la cadena como primer paráemtro
        array_unshift ( $args, $this->cadena );

        // Cambiamos la codificación interna de las funciones mb_
        if ( ! mb_internal_encoding ( $this->codificación ) ) {
            throw new EncodingException ( "Error colocando la codificación a {$this->codificación}" );
        }

        // Llamamos a la función
        $res = call_user_func_array ( $funcion_real, $args );
        
        // Si lo que devuelve es un string, lo reencodeamos
        if ( is_string ( $res )) {
            return new self ( $res );
        }
        else {
            return $res;
        }
    }

    /**
     * Añade una cadena al final
     *
     * @param string $cadena cadena a añadir al final
     */
    function append ($cadena)
    {
        $this->cadena .= (string)$cadena;
        return $this;
    }

    /**
     * Añade una cadena al inicio
     * @param string $cadena Cadena a añadir al principio
     */
    function prepend ($cadena)
    {
        $this->cadena = (string)$cadena . $this->cadena;
        return $this;
    }

    /**
     * Inserta una cadena en una posición
     */
    function insert ($posicion, $cadena)
    {
        $this->cadena = (string)($this(0, $posicion) . $cadena . $this ($posicion) );
        return $this;
    }

    /**
     * Rellena una cadena a la izqueirda
     * @param int $longitud Longitud del relleno
     * @param string $relleno Caracter para usar de relleno.
     */
    function pad_left ( $longitud, $relleno = " " )
    {
        return $this->pad ( $longitud, $relleno, STR_PAD_LEFT );
    }

    /**
     * Reemplaza las ocurrencias de una cadena por otra
     * @param string $de Cadena a buscar
     * @param string $a Cadena a reemplazar
     */
    function replace ( $de, $a ) {
        $de_len = mb_strlen ( $de );
        $a_len = mb_strlen ( $a );
        $cadena = $this->cadena;

        $pos = mb_strpos ( $this->cadena, $de );

        while ( $pos !== false ) {

            $cadena = mb_substr ( $cadena, 0, $pos) . $a .
                mb_substr ( $cadena, $pos + $de_len );

            $pos = mb_strpos ( $cadena, $de, $pos + $a_len );
        }

        return new self ( $cadena );
    }

    /**
     * Añade variables {} al texto
     */
    function format() {
        $values = func_get_args();

        // Si el 1er parámetro es una array, lo usamos de origen
        if ( is_array ( $values [0] ))
            $values = $values [0];

        // Obtenemos las variables
        $data = [];
        $count = preg_match_all ( '/{([a-zA-Z0-9:]*?)}/', $this->cadena, 
            $data, PREG_OFFSET_CAPTURE );
        var_dump ( $data );
    }

    /**
     * Ejecuta un substr si hay argumentos
     */
    function  __invoke () {
        return $this->ejecuta_función ( 'mb_substr', func_get_args() );
    }

	/**
	 * Función mágina que retorna la misma cadena
	 * @return string
	 */
	function __toString() {
		return $this->cadena;
	}

    /***** IMPLEMENTACIÓN DEL ARRAYACCESS *****/
    function offsetExists ( $offset ) {
        return $offset >= 0 && $offset < mb_strlen ( $this->cadena );
    }

    function offsetGet ( $offset ) {
        return new self ( mb_substr ( $this->cadena, $offset, 1 ) );
    }

    function offsetSet ( $offset, $value ) {

        // $value debe ser un string
        $value = (string)$value;

        $this->cadena = 
            mb_substr ( $this->cadena, 0, $offset ) .
            $value .
            mb_substr ( $this->cadena, $offset + 1 );

    }
    function offsetUnset ( $offset ) {
        $this->cadena = 
            mb_substr ( $this->cadena, 0, $offset ) .
            mb_substr ( $this->cadena, $offset + 1 );
        
    }
    
    /***** IMPLEMENTACIÓN DEL ITERATOR *****/
    function current () {
        return $this [ $this->_positer ];
    }
    function key () {
        return $this->_positer;
    }
    function next () {
        $this->_positer++;
    }
    function rewind () {
        $this->_positer = 0;
    }
    function valid () {
        return $this->offsetExists ( $this->_positer );
    }

}
