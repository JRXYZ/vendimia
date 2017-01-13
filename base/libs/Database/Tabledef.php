<?php
namespace Vendimia\Database;
use Vendimia\Database\Field;

/**
 * Database table definition
 */
abstract class Tabledef
{
    // Processed fields definition
    private $tabledef = [];

    // Index of this table
    private $indexes = [];

    // Primery keys of this table
    private $primary_keys = [];

    // Renamed fields
    private $renamed = [];

    /**
     * Parse the table definition.
     */
    public function __construct()
    {
        $ref = new \ReflectionClass(static::class);
        $props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            // Ignoramos las estáticas. Pero no deberían estar acá...
            if ($prop->isStatic()) {
                continue;
            }
            $fieldname = $prop->name;
            $fielddef = $this->$fieldname;
 
            if (!is_array($fielddef)) {
                $fielddef = [$fielddef];
            }

            // Tipo
            if (!isset($fielddef[0])) {
                throw new \RuntimeException("Field type missing for '{$prop->getName()}' field." );
            }
            $fieldtype = $fielddef[0];
            unset ($fielddef[0]);

            // Longitud, si hay
            $fieldlength = [];
            foreach ([1,2] as $i ) {
                if (isset($fielddef[$i])) {
                    $fieldlength[] = (string)$fielddef[$i];
                    unset($fielddef[$i]);
                }
            }
            $fieldlength = join(',', $fieldlength);

            // Algunos campos _necesita_ una longitud
            if (!$fieldlength && Field::needLength($fieldtype)) {
                throw new \RuntimeException("Field '$fieldname' needs a length.");
            }

            // Indices!
            if (isset($fielddef['index'])) {
                $indexdef = $fielddef['index'];
                $indexname = isset($indexdef['name'])
                    ? $indexdef['name']
                    : $fieldname;

                // Nos aseguramos que siempre exista un array
                if (!isset($this->indexes[$indexname])) {
                    $this->indexes[$indexname] = [
                        'unique' => false,
                        'fields' => [$fieldname],
                    ];
                }
                
                if (is_array($fielddef['index'])) {
                    // Si la definición es un array, entonces mezclamos!

                    /*$indexname = isset($indexdef['name'])
                        ? $indexdef['name']
                        : $fieldname;*/

                    // No nos sirve el nombre aquí
                    unset($indexdef['name']);

                    // Añadimos el campo a fields, si no existe
                    if (!in_array($fieldname, $this->indexes[$indexname]['fields'])) {
                        $this->indexes[$indexname]['fields'][] = $fieldname;
                    }

                    $this->indexes[$indexname] = 
                        array_replace(
                            $this->indexes[$indexname], 
                            $indexdef
                        );

                }
                unset ($fielddef['index']);
            }

            // Primary keys!
            if (isset($fielddef['primary_key']) && $fielddef['primary_key']) {
                $this->primary_keys[] = $fieldname;
                unset ($fielddef['primary_key']);
            }

            // Renombrados!
            if (isset($fielddef['renamed_from'])) {
                $this->renamed[$fielddef['renamed_from']] = $fieldname;
                unset ($fielddef['renamed_from']);
            }

            // Todo lo que queda, es la definición de la tabla

            // Verificamos que existan unos campos
            foreach (['null' => false, 'default' => null] as $var => $val) {
                if(!isset($fielddef[$var])) {
                    $fielddef[$var] = $val;
                }
            }

            $fielddef['type'] = $fieldtype;
            if ($fieldlength) {
                $fielddef['length'] = $fieldlength;
            }
            $this->tabledef[$fieldname] = $fielddef;
        }

        // Si no hay un primary key, creamos uno
        if (!$this->primary_keys) {
            $this->tabledef = array_merge([
                'id' => [
                    'type' => Field::Integer,
                    'auto_increment' => true,
                    'null' => false,
                    'default' => null,
                ]
            ], $this->tabledef);

            $this->primary_keys[] = 'id';
        }
    }


    /**
     * Returns the table name.
     *
     * If static::$tablename isn't defined, builds the name using the class 
     * full name, replaceing "\"" with "_". Also, the namespace segment called
     * "db" is remove
     */
    public function getTableName()
    {
        if (isset(static::$table_name)) {
            return static::$table_name;
        }

        $parts = explode('\\', get_called_class());

        $parts = array_filter($parts, function($element) {
            if (strtolower($element) == 'db') {
                return false;
            }
            return true;
        });

        return join('_', $parts);
    }

    /**
     * Returns this table definition.
     */
    public function getTableDef($field = null)
    {
        if (is_null($field)) {
            return $this->tabledef;
        }
        return $this->tabledef[$field];

    }

    /**
     * Returns the indexes.
     */
    public function getIndexes($index = null)
    {
        if (is_null($index)) {
            return $this->indexes;
        }
        return $this->indexes[$index];
    }

    /**
     * Returns the primary keys.
     */
    public function getPrimaryKeys()
    {
        return $this->primary_keys;
    }

    /**
     * Returns the renamed fields.
     */
    public function getRenamedFields()
    {
        return $this->renamed;
    }
}
