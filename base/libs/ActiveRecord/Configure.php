<?php
namespace Vendimia\ActiveRecord;

use Vendimia;
use Vendimia\Database;
use Vendimia\Database\Helpers;

/**
 * Trait for the initial configuration of a model class
 */
trait Configure
{
    /**
     * Sets the default values to this class.
     */
    static public function configure()
    {
        if (static::$configured) {
            return;
        }

        // Obtenemos el nombre y namespace de la clase. Estas variables no 
        // deben ser reusadas, ya que estamos guardandolas en el modelo
        // como referencia (&)

        $parts = explode('\\', strtolower(static::class));
        $class_name = array_pop($parts);
        static::$class_name = &$class_name;

        $class_namespace = join('\\', $parts);
        static::$class_namespace = &$class_namespace;

        // Hack: si encontramos 'models' en las partes, lo eliminamos
        $parts = array_filter($parts, function($value){
            if ($value !== 'models') {
                return true;
            }
        });
        $table_namespace = join('_', $parts);
        static::$table_namespace = &$table_namespace;

 
        // Colocamos el nombre de la tabla
        if (is_null(static::$table)) {
            // El nombre de la tabla depende de si estamos extendiendo una 
            // desde otro activerecord
            $parent_class = get_parent_class(static::class);

            // Estamos extendiendo una clase que no sea de Vendimia\ActiveRecord?
            if (strpos(strtolower($parent_class), 'vendimia\\activerecord') === false) {
                $parent_class::configure();

                // Usamos la tabla del padre

                $table = $parent_class::$table;
                static::$table = &$table;
                static::$extending = &$parent_class;
            } else {
                $namespace = '';
                if (static::$table_namespace) {
                    $namespace = static::$table_namespace . '_';
                }
                $table = $namespace . static::$class_name;
                static::$table = &$table;
            }
        }

        $connection = Database\Database::getConnector(static::$database);
        static::$connection = &$connection;

        // Usaremos esta variable después;
        $this_class = static::class;

        // Construimos las relacions
        $relations = [];
        foreach(['belongs_to', 'has_one', 'has_many'] as $rel_name) {
            if (isset(static::$$rel_name)) {
                $rel_def = static::$$rel_name;
                if (is_string($rel_def)) {
                    $rel_def = [$rel_def];
                }
                foreach ($rel_def as $rel_element) {
                    if (is_string($rel_element)) {
                        $rel_element = [$rel_element];
                    }

                    // El primer elemento contiene el modelo relacionado, con su
                    // alias opcional.
                    $rel_idx = array_keys($rel_element)[0];
                    $rel_class = array_shift($rel_element);

                    // Todo lo que queda son opciones
                    $options = $rel_element;

                    // Si $rel_idx no es numérico, tiene alias.
                    if ($rel_idx !== 0) {
                        $rel_class = $rel_idx;
                    }

                    // Para el $field_name solo usamos el último componente de la 
                    // clase
                    $field_name = get_class_basename($rel_class);

                    // Si hay una opcion 'as', usamos ese nombre de campo
                    if (isset($options['as'])) {
                        $field_name = $options['as'];
                    }

                    // Debe existir el target_class
                    if(!class_exists($rel_class)) {
                        throw new \RuntimeException ( "Model '$rel_class' (required by '$this_class') is undefined.");
                    }

                    // Variables por defecto para cada relación

                    // Modelo donde está la llave foránea
                    $fk_model = null;

                    // Nombre de campo conteniendo la llave foránea
                    $fk_name = null;

                    // Nombre de campo conteniendo la llave primária
                    $pk_field = $this_class::$primary_key;

                    // Referencia a la clase donde está la llave foránea: here o other
                    $fk_location = null;

                    switch ($rel_name) {
                        case 'belongs_to':
                            $fk_location = self::FK_THIS;
                            $fk_model = $this_class;
                            $fk_name = get_class_basename($rel_class) . '_' . $this_class::$primary_key;
                            $rel_type = 'one';
                            break;
                        case 'has_one':
                        case 'has_many':
                            $fk_location = self::FK_REL;
                            $fk_model = $rel_class;
                            $fk_name = get_class_basename($this_class::$class_name) . '_' . $pk_field;


                            // El tipo es "one" o "many", y lo sacamos del mismo 
                            // nombre de la relación
                            $rel_type = substr($rel_name, 4);
                            break;
                    }

                    if (!isset($options['foreing_key'])) {
                        $options['foreing_key'] = $fk_name;
                    }

                    if (!isset($options['primary_key'])) {
                        $options['primary_key'] = $pk_field;
                    }
                    // TODO: Through


                    $options['rel_class'] = $rel_class;
                    $options['rel_name'] = $rel_name;
                    $options['rel_type'] = $rel_type;
                    $options['fk_location'] = $fk_location;
                    //$options['foreign_model'] = $fk_model;

                    // TODO: Through

                    $relations [$field_name] = $options;
                }
            }
        }

        static::$relations = &$relations;

        $configured = true;
        static::$configured = &$configured;
    }

}