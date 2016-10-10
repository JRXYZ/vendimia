<?php
namespace assets\services;

use Vendimia;

/**
 * Static class for various assets utilities.
 */
class Asset 
{
    /**
     * Build a single file name for all the assets, plus the controller name.
     */
    static public function buildUri(array $assets)
    {
        $uri = '';

        if (Vendimia::$application) {
            $uri = Vendimia::$application . '::';
        }

        $uri .= join(',', $assets);
        
        return $uri;
    }

    /**
     * Return all the files associated to an asset, from Vendimia::$Args.
     *
     * @return array [application, asset_names]
     */
    public static function getNamesFromArgs()
    {
        if (!isset (Vendimia::$args[0]) || trim(Vendimia::$args[0]) == "") {
            Http\Response::serverError("You must specify at least one Javascript asset filename.");
        }

        $application = Vendimia::$application;
        $names = explode (',', Vendimia::$args[0]);

        $colon = strpos($names[0], '::');
        if ($colon !== false) {
            $application = substr($names[0], 0, $colon);
            $names[0] = substr($names[0], $colon + 2);
        }

        return [$application, $names];
    }
}
