<?php
namespace Vendimia\Database;

use Vendimia;

class QueryException extends Vendimia\Exception {}

/**
 * Class for administrating the configured databases.
 */
class Database 
{
    /** Connection to various databases */
    private static $connections = [];

    private static $default_connection = 'default';

    /** Initialize a connection */
    public static function initialize($config)
    {
        foreach ($config as $name => $def) {
            // El tipo de base de datos está en el índice 0
            $type = ucfirst(strtolower($def[0]));
            
            // Creamos el objeto
            $class = __NAMESPACE__ . '\\' . $type . "\\Connector";

            self::$connections[$name] = [
                'type' => $type,
                'object' => new $class($def),
            ];
        }
    }

    /**
     * Returns a database connector
     */
    public static function getConnector($connection = null)
    {
        if (!self::$connections) {
            throw new \RuntimeException("This project has no database configured.");
        }

        if (is_null($connection)) {
            $connection = self::$default_connection;
        }
        return self::$connections[$connection]['object'];
    }

    /**
     * Creates an Vendimia\{Driver}\Manager
     */
    public static function getManager($connection = null) {

        if (!self::$connections) {
            throw new \RuntimeException("This project has no database configured.");
        }

        if (is_null($connection)) {
            $connection = self::$default_connection;
        }
        $class = __NAMESPACE__ . '\\' . self::$connections[$connection]['type'] . "\\Manager";
        return new $class(self::$connections[$connection]['object']);
    }
}