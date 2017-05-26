<?php
namespace Vendimia\ActiveRecord;

use Vendimia\Database;

/**
 * Utility trait for building SQL queries.
 */
trait QueryBuilder
{
    /**
     * Vendimia Query terminator to field format map,
     */
    /*static $query_endings = [
        '_is' => '{f:f} = {v}',
        '_are' => '{f:f} = {v}',
        '_is_not' => '{f:f} != {v}',
        '_are_not' => '{f:f} != {v}',
        '_begin_with' => '{f:f} LIKE "{p:v}%"',
        '_begins_with' => '{f:f} LIKE "{p:v}%"',
        '_end_with' => '{f:f} LIKE "%{p:v}"',
        '_ends_with' => '{f:f} LIKE "%{p:v}"',
        '_contains' => '{f:f} LIKE "%{p:v}%"',
        '_gt' => '{f:f} > {v}',
        '_lt' => '{f:f} < {v}',
        '_gte' => '{f:f} >= {v}',
        '_lte' => '{f:f} <= {v}',
    ];*/

    /** The query parameters for this object */
    protected $query = [
        'fields' => [],
        'table' => null,
        'where' => [],
        'limit' => null,
        'offset' => null,
        'order' => [],
        'group' => [],
        'having' => []
    ];

    // Boolean join for the next WHERE
    protected $query_boolean_join = 'AND';

    // Boolean negation for the next WHERE
    protected $query_boolean_not = false;

    /**
     * Obtains the field format finding the query ending.
     *
     * @param string Query where to look for. This function chomps the found
     *      terminator out of this string.
     */
    /*protected static function getFormatByEnding(&$query) {
        foreach (self::$query_endings as $cue => $format) {
            $l = strlen($cue);
            if (substr($string, -$l) == $e) {
                $string = substr($string, 0, -$l);
                return $format;
            }
        }
        return null;
    }*/

    /**
     * Returns an array of WHEREs built from the arguments
     *
     * The query is build using this rules:
     * - If $params is an integer value, search for this primary key.
     * - If it's an array, and only have numeric indexes, the primary key
     *   will be search with an IN.
     * - If it's an associative array, multiple WHERE will be created with
     *   each key EQUALed to its value, joined with ANDs.
     * - 
     *
     * @param mixed $parms
     */
    protected function buildWhere($args)
    {
        $where = [];
        $class = $this->base_class;

        $class::configure();
        $connector = $class::$connection;

        // Cada uno de los pedazos, en formato [glue, not, where]
        // glue = and/or, not = true if not, where = string or more where

        if (is_array($args)) {
            if ($args === array_values($args)) {
                // Es una lista. Buscamos el PK con un IN
                $w = $connector->escapeIdentifier($class::$primary_key);
                $w .= ' IN (' . join(', ', $connector->escape($args)) . ')';

                $where[] = ['AND', false, $w];
            } else {
                // Es un array asociativo
                foreach ($args as $key => $value) {
                    $w = $connector->escapeIdentifier($key);

                    if (is_array($value)) {
                        $w .= ' IN (' . join(', ', $connector->escape($value));
                        $w .= ')';
                    } else {
                        if (is_object($value)) {
                            if ($value instanceof Comparison) {
                                $w .= $value->getValue($connector);
                            } elseif ($value instanceof Database\ValueInterface) {
                                $w .= '=' . $value->getDatabaseValue($connector);
                            } else {
                                throw new \RuntimeException("'$key' value object (".get_class($value) . ") must be an instance of Vendimia\\ActiveRecord\\Comparison or implements interface Vendimia\\Database\\ValueInterface, to be used here.");
                            }
                        } else {
                            $w .= '=' . $connector->valueFromPHP($value);
                        }
                    }
                    $where[] = ['AND', false, $w];
                }
            }
        } elseif (is_numeric($args)) {
            // Buscamos por su PK
            $w = $connector->escapeIdentifier($class::$primary_key);
            $w .= '=' . intval($args);
            $where[] = ['AND', false, $w];
        } elseif ($args instanceof Database\ValueInterfase) {
            // Igual que is_numeric(), pero obtenemos el valor del model

            // TODO

        } elseif (is_string($args)) {
            // Simplemente lo añadimos. Ya debe estar escapado bonito...
            $where[] = ['AND', false, $args];
        }


        return $where;
    }

    /**
     * Creates a WHERE using replace variables from an associative array
     *
     * It will search for the $args keys surrounded by brakets inside $where,
     * and replace it for its escape value.
     */
    protected function buildAssociativeRawWhere($where, $args)
    {
        $class = $this->base_class;
        $connector = $class::$connection;

        $parsed_args = [];
        foreach ($args as $key => $arg) {
            $parsed_args['{' . $connector->escape($key, '') . '}'] = 
                $connector->escape($arg);
        }

        return strtr($where, $parsed_args);
    }

    /**
     * Creates a raw WHERE replacing variables from an non-associative array
     *
     * This function will replace every '{}' character sequence in $where for
     * each value in $args.
     */
    protected function buildRawWhere($where, $args)
    {
        $class = $this->base_class;
        $connector = $class::$connection;
        
        while (($pos = strpos($where, '{}')) !== false) {
            $value = current($args);

            if ($value === false) {
                throw new \RuntimeException('Not enough parameters for raw WHERE.');
            }
            $value = $connector->escape($value);

            $where = substr($where, 0, $pos) . $value . 
                substr($where, $pos + 2);

            next($args);
        }

        return $where;
    }

    /**
     * Changes this record into a search record.
     */
     protected function setQueryState($where) {
        $this->query['where'] = $where;
        $this->is_new = false;
        $this->query_executed = false;
    }

    /**
     * Executes this record query. Returns a raw database cursor
     */
    protected function executeQuery() 
    {
        $class = $this->base_class;

        // Just in case4
        $class::configure();

        $connection = $class::$connection;

        if (!$this->query['table']) {
            $this->query['table'] = $class::$table;
        }
        return $connection->buildAndExecuteQuery($this->query);
    }



    
    /**
     * Retrieves a single record
     */
    public static function get($where = null)
    {
        self::configure();

        $object = new static;
        $object->setQueryState($object->buildWhere($where));
        return $object;
    }

    /**
     * Creates a recordset
     */
    public static function find($where = null)
    {
        self::configure();

        $recordset = new RecordSet(static::class);
        if ($where) {
            $recordset->where($where);
        }
        
        return $recordset;
    }

    /**
     * Alias of self::find();
     */
    public static function all()
    {
        return static::find();
    }

    /**
     * Changes the next WHERE to an OR WHERE
     */
    public function or() {
        $this->query_boolean_join ='OR';
        return $this;
    }

    /**
     * Changes the next WHERE to an NOT WHERE
     */
    public function not() {
        $this->query_boolean_not ='NOT';
        return $this;
    }


    /**
     * Adds a condition block to the query
     *
     * @see self::buildWhere()
     */
    public function where($where)
    {
        $this->query['where'][] = [
            $this->query_boolean_join,
            $this->query_boolean_not,
            $this->buildWhere($where)
        ];

        // Reseteamos los boolean joins
        $this->query_boolean_join = 'AND';
        $this->query_boolean_not = false;

        return $this;
    }


    /**
     * Adds a raw SQL query
     */
    public function rawWhere($where, ...$args)
    {
        if (isset($args[0]) && is_array($args[0])) {
            $where = $this->buildAssociativeRawWhere($where, $args[0]);
        } else {
            $where = $this->buildRawWhere($where, $args);
        }

        $this->query['where'][] = [
            $this->query_boolean_join,
            $this->query_boolean_not,
            $where
        ];

        $this->query_boolean_join = 'AND';
        $this->query_boolean_not = false;

        return $this;
    }

    /**
     * Adds a field order
     */
    public function order(...$params)
    {
        $this->query['order'] = array_merge($this->query['order'], $params);

        return $this;
    }

    /**
     * Adds a LIMIT clausule
     */
    public function limit($limit)
    {
        $this->query['limit'] = $limit;
        return $this;
    }

    /**
     * Adds an LIMIT offset portion
     */
    public function offset($offset)
    {
        $this->query['offset'] = $offset;
        return $this;
    }
}