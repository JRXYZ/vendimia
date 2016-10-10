<?php
namespace assets;

use Vendimia;
use Vendimia\Http;

$asset = services\Asset::getNamesFromArgs();

// Cambiamos la aplicación. No creo que pase nada
Vendimia::$application = $asset[0];

$files = $asset[1];

// Simplemente unimos todos los js en un solo fichero
$js = '';
foreach ($files as $file) {
    $fp = new Vendimia\Path\FileSearch($file, 'assets/js', 'js');

    // Si no existe, 404!
    if ($fp->notFound()) {
        Http\Response::notFound ( "Javascript asset file '$file' not found", [
            'Search paths' => $fp->searched_paths,
        ]);
    }
   
    $file = $fp->get();
    $js .= file_get_contents($file) . "\n";
}

(new Http\Response($js, 'application/javascript'))
    ->send();


// Si no hay argumentos, 500!
if (!isset (Vendimia::$args[0]) || trim(Vendimia::$args[0]) == "") {
    Http\Response::serverError("You must specify at least one Javascript asset filename.");
}
$path = join_paths(Vendimia::$ARGS->get() );

// Hay una app por defecto?
$colon = strpos ($path, '::' );
if ( $colon !== false ) {
    // Cambiamos el nombre de la app. No creo que haya problemas...
    Vendimia::$APPLICATION = strtr(substr ( $path, 0, $colon ), '.', '/');
    $path = ( substr ( $path, $colon + 2 ));
}


$names = explode (',', $path );

// Verificamos los etags de todos los ficheros que pide. Si ninguno
// ha variado, entonces 304.
$files = [];
$modified = true;
foreach ( $names as $name ){

    // Si no tienen extensión .css, la añadimos
    /*if ( substr ( $name, -3 ) != ".js" )
        $name .= '.js';*/

    $fp = new Vendimia\Path\FileSearch($name, 'assets/js', 'js');

    // Si no existe, 404!
    if ($fp->notFound()) {
        http\file_not_found ( "Javascript asset file '$name' not found", [
            'Search paths' => $fp->searched_paths,
        ]);
    }
    
    $file = $fp->get();
    $S = stat ($file);
    
    $files[] = $file;
}

if ( $modified ) {
    // Enviamos todos los ficheros
    header ( 'Content-Type: text/javascript');
    foreach ( $files as $f ) {
        echo file_get_contents( $f ) . "\n";
    }
}

exit;
