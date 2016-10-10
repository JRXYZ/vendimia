<?php 
use Vendimia\Cli;
use Vendimia\Console;

bin::help('Starts a Vendimia evaluative PHP shell.', '', []);

class _ {
    static $levels = [ 
        '"' => [
            'end' => '"',
            'hard' => true,
        ],
        '\'' => [
            'end' => '\'',
            'hard' => true,
        ],
        '(' => [
            'end' => ')',
        ],
        '[' => [
            'end' => ']',
        ],
        '{' => [
            'end' => '}',
        ],
       
    ];

    static $level_stack = [];
    static $level = false;    
    static $prompt = 'app';
   
    // El prompt para readline
    static $rl_prompt = '';

    // El caracter que está siendo analizado
    static $char;

    // Un contador genérico
    static $i;
    
    // La linea que se lee del teclado
    static $line;
    
    // La o las líneas del comando PHP
    static $execute_line;
    
    // El siguiente caracter debe ser escapado?
    static $escaping;
    
    // Por si falta un ;
    static $LEVEL_SEMICOLON = false;

    // Configuración
    static $C = [
        'history_file' => '~/.Vendimia_shell_history',
        'pager' => 'less -XFR',
        'colors' => true,
        'multiline' => false,
        'max_depth' => 3,
    ];

    static function exception_handler ($e) {
        
        Vendimia\Console\ExceptionHandler::handler($e);
        // Igual, añadimos el comando a la historia.
        self::save_line_and_reset();
    }

    static function shutdown_handler() {
        if ( !error_get_last() ) {
            echo "\nExiting.\n";
        }
        else {
            echo "\nExiting with error.\n";
        }

    }

    static function save_line_and_reset( $just_save = false ) {
        
        // SOlo grabamos líneas que no son vacías
        if ( _::$line ) {
            readline_add_history ( _::$line );
            readline_write_history( _::$C['history_file'] );
        }

        if ( $just_save ) {
            return;
        }

        // Reseteamos todo
        _::$execute_line = '';
        _::$level = false;
        _::$LEVEL_SEMICOLON = false;
    }

    static function get_data_info( $data, $indent = 0, $no_traverse = false )  {

        $type = gettype ( $data );
        $length = false;
        
        // Algunos tipos tienen una longitud
        if (is_string($data)) {
            $length = strlen($data);
        }
        if (is_array($data)) {
            $length = count($data);
        }

        // Usamos la longitud para mostrar la clase
        // de un objeto
        if (is_object ($data)) {
            $length = get_class($data);
        }

        $line = "{cyan $type}";
        if ($length !== false) {
            $line .= "({white $length})";
        }
        if (!is_null($data)) {
            $line .= ': ';
        }

        // Si el objeto es transversable, lo... transversamos...
        if (is_array($data) || is_object($data)) {

            // Vamos procesamos la linea
            $line = Console::parse ( $line) . PHP_EOL;

            // Si no queremos que traversemos, no traversamos...
            if ( $no_traverse ) {
                $line .= str_repeat ( " ", $indent ) . "..." . PHP_EOL;
            }
            else {
                $count = 0;
                foreach ( $data as $key => $value ) {

                    // Solo permitimos cierto nivel de indent
                    $no_traverse = _::$C['max_depth'] == 0 || $indent > _::$C['max_depth'];

                    $tl = str_repeat ( " ", $indent ) . "$key: ";
                    $tl .= self::get_data_info ( $value, $indent + 1, $no_traverse );

                    $line .= Console::parse ( $tl) . PHP_EOL;
                    $count++;
                }

                // Si no hay nada, mostramos ...
                if ( $count == 0 ) {
                    $line .= "(empty)" . PHP_EOL;
                }
            }


            return $line;
        }
        elseif ( is_bool ( $data ) ) {
            // Es un booleano
            $line .= $data === true ? 'true' : 'false';
            return Console::parse ( $line);
            
        }
        else {
            // No es transversable. Solo lo mostramos
            $line .= $data;
            return Console::parse ( $line);
        }
    }

    static function process_result ( $result ) {
        $vd =  _::get_data_info ( $result );


        if ( _::$C['pager'] ) {
            $p = proc_open ( _::$C['pager'], [
                0 => ['pipe', 'r']
            ], $pipes);
            fwrite ( $pipes[0], $vd );
            proc_close ( $p );
        }
        else {
            echo $vd;
        }

        self::save_line_and_reset();
    }
}

if ( ! defined ( "VENDIMIA_BASE_INCLUDED" ) ) {
    require 'vendimia/bin/base.php';    
}

if ( in_array ( "--help", bin::$args )  ) {
    return "Launch a limited PHP shell with access to project models and libraries.";
}

if (!bin::$project_exists) {
    Console::fail ( 'Vendimia project directory not found or not specified.' ); 
    exit;
}

chdir ( bin::$project_path );
Console::write ( "Launching Vendimia shell for {:project " . bin::$project_name . "} project..." );

Console::readline_on();
_::$prompt = Console::parse("{white " . bin::$project_name . "}", true);
Console::readline_off();

//require 'vendimia/start.php';
//load_lib ( 'exception_handler/cli' );

// Leemos la historia. Reemplazamos ~ por el home del usuario, de ser necesario
_::$C['history_file'] = str_replace ( '~', $_SERVER['HOME'], _::$C['history_file'] );

if ( is_file( _::$C['history_file'] ) ) {
  readline_read_history( _::$C['history_file'] );
}

register_shutdown_function( '_::shutdown_handler' );

while ("hey there :)") {

    _::$rl_prompt = _::$prompt . ' ';
    if ( _::$level ) {
        _::$rl_prompt .= _::$level['prompt'];
    }
    elseif ( _::$LEVEL_SEMICOLON ) {
        _::$rl_prompt .= ';';    
    }
    else {
        _::$rl_prompt .= '>';
    }
    _::$rl_prompt .= ' ';

    _::$line = readline( _::$rl_prompt );

    if ( _::$line === false || trim ( _::$line ) == 'exit' ) {
        // Ctrl + D
        exit;
    }

    // Algunos comandos especiales
    if ( _::$line && substr ( _::$line, 0, 2) == '##' ) {
        $l = substr ( _::$line, 2 );
        $p = array_filter ( explode( ' ' , $l ) );

        switch ( array_shift ( $p ) ) {
            case 'c':
            case 'clear':
                echo "\x1b[1;1H\x1b[0J";

                _::save_line_and_reset();
                continue;

            case 's':
            case 'set':
                $variable = array_shift ( $p );

                if ( !$variable ) {
                    Console::write ("{red ERROR:} missing configuration variable.");
                }
                else {
                    $value = join ( ' ' , $p);

                    if ( array_key_exists ( $variable, _::$C ) ) {
                        if ( is_bool( _::$C [ $variable ] ) && !$value ) {
                            // Lo negamos
                            _::$C [ $variable ] = ! _::$C [ $variable ];

                            $strval = ( _::$C [ $variable ] == true) ? 'true' : 'false';
                            Console::write ("{yellow $variable:} is now {white $strval}.");
                        }
                    }
                    else {
                        Console::write ("{red ERROR:} Configuration variable '{$variable}' not found.");
                    }

                }

                _::save_line_and_reset();
                continue;
        }
        // Borramos la línea. No seguimos procesandola;
        _::$line = '';
    }
    
    for ( _::$i = 0; _::$i < strlen ( _::$line ); _::$i++ ) {
        _::$char = _::$line{_::$i};


        // Escapamos el siguiente caracter?
        if ( _::$char == '\\' ) {
            _::$escaping = true;
        }
        elseif ( _::$escaping ) {
            // Si estamos escapando, no procesamos nada
            _::$escaping = false;
        }
        else {

            // Cualquier caracter que no sea un espacio, activa el nivel ;
            if ( strpos ( ' ', _::$char ) === false && ! _::$LEVEL_SEMICOLON ) {
                _::$LEVEL_SEMICOLON = true;
            }

            // Ejecutamos?
            if ( _::$char == ';' ) {
            
                // No debemos de estar dentro de ningun nivel
                if ( _::$level === false ) {
                    // Añadimos el :;
                    _::$execute_line .= _::$char;
                    
                    try {
                        $_ = eval ( 'return ' . trim ( _::$execute_line ) ) ;
                        _::process_result ( $_ );
                    }           
                    catch ( Exception $e ) {
                        _::exception_handler( $e );
                    }

                    continue;
                }
                
            }
            
            // Este caracter es el caracter de cerrado del último nivel?
            if ( _::$char == _::$level['end'] ) {
                // Ok, cerramos un nivel
                array_pop ( _::$level_stack );
               
                // Nos quedamos en el último nivel del stack
                _::$level = end ( _::$level_stack );
                
                
            }
            elseif ( isset ( _::$levels[_::$char] ) ) {

                // Solo cambiamos de nivel si, o no hay nivel, o el nivel actual
                // no es hard

                if ( _::$level === false || ! isset ( _::$level['hard'] )  ) {
                
                    // Nuevo nivel
                    _::$level = _::$levels[_::$char];

                    // Añadimos el caracter para el prompt
                    _::$level['prompt'] = _::$char;
                    
                    // Guardamos en el stack
                    _::$level_stack[] = _::$level;
                    
                }
            }
        }
        
        // Vamos añadiendo la data 
        _::$execute_line .= _::$char;
    }
    
    // Si no hay un nivel, y la configuración quiere, procesamos
    if ( !_::$C['multiline'] && _::$level === false && trim ( _::$execute_line ) != '' ) {
        // Añadimos el :;
        _::$execute_line .= ';';
        try {
            $_ = eval ( 'return ' . trim ( _::$execute_line ) ) ;
            _::process_result ( $_ );
        }           
        catch ( Exception $e ) {
            _::exception_handler( $e );
        }

        continue;
    }

    
    // Cada línea añade un \n al execute_line
    _::$execute_line .= "\n";
    
    // añadimos a la historia
     _::save_line_and_reset( true );
}