<?php
namespace assets;

use Vendimia;
use Vendimia\Phpss;
use Vendimia\Http;

//Si no hay argumentos, 500!
if (!isset ( Vendimia::$args[0] ) || trim( Vendimia::$args[0] ) == "") {
    Http\Response::serverError("You must specify at least one CSS asset filename.");
}


// Unimos todos los argumentos, por si queremos assets de otra app
$args = join ('/', Vendimia::$args->get());

// Luego los separamos por la coma
$files = explode ( ',', $args );

// Hay una app por defecto?
$colon = strpos ($files[0], '::' );
if ( $colon !== false ) {

    // Cambiamos el nombre de la app. No creo que haya problemas...
    Vendimia::$application = strtr(substr ( $files[0], 0, $colon ), '.', '/');
    $files[0] = ( substr ( $files[0], $colon + 2 ) );

}

// El CSS final
$css = '';
$modified = false;

$cache = new services\Compiler('css', $args);

$cache->lock();

$phpss = new Phpss\Phpss( $files );

$css  = $phpss->getCss();

$cache->save($css);

(new Http\Response($css, 'text/css')) 
    -> send();
