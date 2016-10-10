<?php
/**
 * Path utils
 */
namespace Vendimia\Path;

use Vendimia;

/** Exception if the makeTree fails creating a directory */
class MkdirFail extends \Exception {};

class PathExists extends \Exception {};


/**
 * Join several paths
 *
 * @author Oliver Etchebarne
 */
function join()
{
    $args = func_get_args();
    $sep = DIRECTORY_SEPARATOR;

    // Si no hay elelemtnso, retornamos nada
    if ( !$args ) {
        return '';
    }

    $absolute = '';
    $first = true;
    $paths = [];

    foreach ( $args as $path ) {
        
        // Si $path es un array, analizamos recursivamente
        if (is_array($path)) {
            $path = call_user_func_array(__FUNCTION__, $path);
        }
    
        // Si el primer elemento empieza con un /, entonces
        // toda la ruta es absoluta, debe de empezar con un /
        if ( $first && $path && $path{0} == $sep ) {
            $absolute = $sep;
        }

        // Usamos array_filter para que solo devuelva las partes
        // de $path que no están vacías (como en "/home//oliver")
        $first = false;

        $parts = array_filter ( explode ( $sep, $path ) );
        $paths = array_merge ( $paths, $parts );

    }

    return $absolute . \join ($sep, $paths);
}


/**
 * Create a directory.
 *
 * @param string $dirpath full path to create
 * @return string Status of the creation 
 *
 */
function makeDir ($dirpath)
{
    if (file_exists($dirpath)) {
        return 'omit';
    } else {
        $res = mkdir ($dirpath, 0775, true);
        if ($res) {
            return 'ok';
        } else {
            return 'error';
        }
    }
}

/**
 * Makes a directory tree structure inside $base_path, yielding every directory
 *
 * @param string $base_path Base path for all the new directories.
 * @param array $tree New directory listing to create. If the value is an array,
 *      then it's created recursively.
 * @yield array Status of every directory it's being created, and its path.
 */
function makeTree ($base_path, array $tree)
{
    foreach ($tree as $base => $path) {
        if (is_array($path)) {
            $new_base_path = join($base_path, $base);
            foreach (makeTree ($new_base_path, $path) as $path) {
                yield $path;
            };
        } else {
            $path = join($base_path, $path);
            $status = makeDir ($path);
            yield [$status, $path];
       }
    }
}
