<?php
namespace Vendimia\ActiveRecord;

use Vendimia;
use Vendimia\Database;
use Vendimia\Database\Helpers;
use Vendimia\AsArrayInterface;

class NonIterable extends Vendimia\Exception {}

/**
 * Class for interact with one record in the database. Also, it's the base class
 * for model definition.
 *
 * The 'abstract' keyword is set for avoid direct instancing of this class.
 * This class must be extended into a Model class.
 */
abstract class Record extends Base implements AsArrayInterface, \Iterator
{
    use QueryBuilder, Relations, Configure;

    const FK_THIS = 'this';
    const FK_REL = 'rel';

    /** Primary key of this record table */
    public static $primary_key = 'id';

    /** Table name for this object. Defaults to {self::$namespace}_{self::$name} */
    public static $table = null;

    /** Database name for this model, */
    public static $database = 'default';




    /** This model relations */
    protected static $relations = [];

    /**
     * Table namespace, Defaults to this class namespace without 
     * the 'models' portion.
     */
    protected static $table_namespace = '';

    /** This class namespace */
    protected static $class_namespace = '';

    /** This class name. Defaults to the class name */
    protected static $class_name = '';

    /** Field containing this object type, for inheritance */
    protected static $type_field = 'type';

    /** Field value for this particular table. Defaults to the inherit class name */
    protected static $type_value = null;

    /** Parent class which are extending */
    protected static $extending = null;

    /** Is this class configured yet? */
    protected static $configured = false;

    /** Extra conditions for query this table, like in a SQL WHERE */
    // AHORA SON 'CONSTRAINS', y van en Recordset // protected static $extra_conditions = [];

    /** Fields details obtained from the database */ // TODO //
    protected static $fieldinfo = [];



    /** This record values */
    protected $fields = [];

    /** Objects generated from relations */
    protected $object_fields = [];

    /** Modified fields. Only the keys are used */
    protected $modified_fields = [];
    
    /** Is this record new? */
    protected $is_new = true;

    /** Is this query record empty */
    protected $is_empty = null;

    /* Fields and object_field combined for iterator */
    private $iterator_container;

    /**
     * Creates a new record and submit to the database
     */
    public static function create($fields = null)
    {
        static::configure();
        return (new static($fields))->save();
    }

    /**
     * Construct a record.
     */
    public function __construct($fields = null, $not_new = false)
    {
        static::configure();
        $this->base_class = static::class;

        if ($fields instanceof AsArrayInterface) {
            $fields = $fields->asArray();
        }

        if ($fields) {
            foreach($fields as $field => $value) {
                $this->$field = $value;
            }
        }

        if ($not_new) {
            $this->is_new = false;
            $this->query_executed = true;
            $this->buildRelations();
        }
    }

    /**
     * Sets a field value. Triggers relationships update, doesn't change
     * the 'modified_fields'
     */
    protected function setFieldValue($field, $value)
    {
        // Es un campo relacion?
        if (isset(static::$relations[$field])) {

            $rel = static::$relations[$field];

            if (is_object($value)) {
                if ($value instanceof Base) {
                    // No podemos asignar un objeto a una relacion tipo 'many'
                    if ($rel['rel_type'] == 'many') {
                        throw new \RuntimeException("Cannot overwrite field '$field' with {$rel['rel_name']} relationship.");
                    }
                    // Añadimos el objeto a esta relación, si la clase del objeto es
                    // target_class
                    if ($value->base_class != $rel['rel_class']) {
                        throw new \RuntimeException("Field '$field' from model '{$this->base_class}' only accepts '{$rel['rel_class']}' instances, tried to assing a '{$value->base_class}' instance.");
                    }

                    // Ok, asignamos el objeto, y cambiamos la llave primaria
                    $this->object_fields[$field] = $value;

                    if ($rel['fk_location'] == self::FK_THIS) {
                        $this->fields[$rel['foreign_key']] = $value->pk();
                        $this->modified_fields[$rel['foreign_key']] = true;
                    } else {
                        // Para FK_OTHER, necesitamos que 
                        // this->{option[primary_key]} exista
                        if (!$this->{$rek['primary_key']}) {
                            throw new \RuntimeException("Can't assign a 'has_one' field on a unsaved model.");
                        }

                        $value->{$rel['foreign_key']} = $this->pk();
                        $value->save();
                    }
                    return;
                }
            } else {
                throw new \RuntimeException("Field '$field' from model '{$this->base_class}' requires an instance of '{$rel['rel_class']}'.");
            }
        }


        $this->fields[$field] = $value;
    }

    /**
     * Gets or sets the primary key
     */
    public function pk($value = null)
    {
        if (is_null($value)) {
            if (key_exists(static::$primary_key, $this->fields)) {
                return $this->fields[static::$primary_key];
            } else {
                return null;
            }
        } else {
            $field =static::$primary_key;
            $this->$field = $value;
        }
    }

    /**
     * Returns if this record is a new one (i.e. not obtained from the
     * database)
     */
    public function isNew()
    {
        return $this->is_new;
    }

    /**
     * Sugar syntax for !isNew()
     */
    public function notNew()
    {
        return !$this->is_new;
    }

    /**
     * Returns if this query record has empty result.
     */
    public function isEmpty() 
    {
        $this -> retrieveRecord();
        return $this->is_empty;
    }

    /**
     * Sugar syntax for !isEmpty()
     */
    public function notEmpty() 
    {
        return !$this->isEmpty();
    }

    /**
     * Returns the specified non-object fields of this record
     * as an associative array.
     */
    public function asArray($fields = null)
    {
        $this -> retrieveRecord();

        if (is_null($fields)) {
            return $this->fields;
        }

        $fields = array_flip($fields);
        return array_intersect_key($this->fields, $fields);
    }

    /**
     * Saves a record to the database
     */
    public function save($options = null)
    {

        // Ejecutamos el beforeSave()
        if (method_exists($this, 'beforeSave')) {
            $this->beforeSave();
        }

        $fields = [];

        // Obtenemos los campos a grabar
        if (!$fields) {
            // Si no hay campos modificados, realmente no hacemos nada
            if ($this->modified_fields) {
                $fields = array_keys($this->modified_fields);
            }
        }

        if (!$fields) {
            return $this;
        }

        // Determinamos la acción a realizar dependiendo del estado
        // de la llave primaria
        $id = $this->pk();

        if (is_null($id)) {
            $action = 'INSERT';
        } else {
            $action = 'UPDATE';
        }

        // Si es un modelo extendido, añadimos el tipo
        if (static::$extending) {
            $this->fields[static::$type_field] = static::$type_value;
        }

        $data = $this->asArray($fields);

        // Este loop es por si intentamos actualizar un registro que no 
        // existe. En ese caso, insertamos
        //$connector = Database\Database::getConnector();
        while (true) {
            if ($action == 'INSERT') {
                $id = static::$connection->insert(static::$table, $data);
                $this->pk($id);
                break;
            } else {
                $where = Helpers::fieldValue(static::$primary_key, $id);

                $affected = static::$connection->update(
                    static::$table,
                    $data,
                    $where
                );

                if ($affected == 0) {
                    // El query no actualizó ningún registro. Entonces
                    // lo insertamos
                    $action = 'INSERT';
                } else {
                    break;
                }
            }
        }

        // Ejecutamos algunos métodos, si existen
        if (method_exists($this, 'afterSave')) {
            $this->afterSave();
        }

        // Ejecutamos las relaciones. Esto más es útil cuando creas un 
        // objeto nuevo, para que sus relaciones foráneas tengan un objeto
        $this->buildRelations();
        return $this;
    }

    /**
     * Retrives a record from the database
     */
    private function retrieveRecord() {
        if ($this->query_executed) {
            return;
        }

        // No ejecutamos si es nuevo
        if ($this->is_new) {
            return;
        }

        $c = $this->executeQuery();
        $data = static::$connection->fetchOne($c);

        if (!$data) {
            $this->is_empty = true;
            return false;
        }

        foreach ($data as $variable => $value) {
            $this->setFieldValue($variable, $value);
        }

        // Construimos las relaciones
        $this->buildRelations();

        $this->query_executed = true;
    }

    /**
     * Sets this record fields from an array or array-like object
     */
    public function set($data) 
    {
        if ($data instanceof Vendimia\AsArrayInterface) {
            $data = $data->asArray();
        }

        // Inicializamos $this->fields
        if (is_null($this->fields)) {
            $this->fields = [];
        }

        // Insertamos elemento por elemento
        foreach ($data as $field => $value) {
            /*
            // Esto será escapado en Database\Connector, no aquí.

            if ($value instanceof Vendimia\Database\ValueInterface) {
                $value = $value->getDatabaseValue(Database\Database::getConnector(static::$database));
            }/**/
            $this->$field = $value;
        }
    }

    /**
     * Updates and saves this record
     */
    public function update($params)
    {
        $this -> retrieveRecord();
        
        $this->set($params);
        return $this->save();
    }

    /**
     * Deletes a record and its relations
     */
    public function delete()
    {
        if ($this->isEmpty()) {
            return false;
        }
        if ($this->isNew()) {
            return false;
        }

        // Ejecutamos el beforeDelete()
        if (method_exists($this, 'beforeDelete')) {
            $this->beforeDelete();
        }

        // Modificamos o borramos los registros relacionados
        foreach (static::$relations as $field => $rel) {
            // Solo trabajamos con has_one o has_many
            if ($rel['fk_location'] == static::FK_REL) {
                continue;
            }

            if (!isset($rel['on_delete'])) {
                $on_delete = 'cascade';
            } else {
                $on_delete = $rel['on_delete'];
            }
            switch ($on_delete) {
                case 'cascade':
                    // Simplemente borramos el objeto
                    $this->$field->delete();
                    break;
                case 'null';
                    // Actualizamos el campo 
                    $this->field->update([
                        $rel['foreign_key'] => null,
                    ]);
            }
        }
        $where = $this->buildWhere([
            static::$primary_key => $this->pk()
        ]);

        return static::$connection->delete(
            static::$table,
            $where
        );
    }

    /**
     * Magic method for store a field value
     */
    public function __set($field, $value)
    {
        // Antes de colocar un valor, nos fijamos que tengamos los valores
        // de la base de datos, de ser necesario. De lo contrario, si 
        // ejecutamos luego un __get, puede sobreescribirlos.
        $this->retrieveRecord();

        $this->setFieldValue($field, $value);

        // Guardamos el campo en una lista, para el save()
        $this->modified_fields[$field] = true;
    }

    /**
     * Magic method for retrieve a field value
     */
    public function __get($field)
    {
        if (!$this->isNew()) {
            $this->retrieveRecord();
        }

        if (key_exists($field, $this->fields)) {
            return $this->fields[$field];
        }

        if (key_exists($field, $this->object_fields)) {
            return $this->object_fields[$field];
        }

        return null;
    }

    /* Iterator implementation */
    public function current()
    {
        return current($this->iterator_container);
    }

    public function key() 
    {
        return key($this->iterator_container);
    }

    public function next()
    {
        next($this->iterator_container);
    }

    public function rewind()
    {
        // Esto es medio chusco, sólo lo podemos iterar cuando lo
        // ejecutamos por la CLI
        if (Vendimia::$execution_type != 'cli') {
            throw new NonIterable("You can't iterate over a record object. Are you using get() instead of find()?");
        }

        if (!$this->isNew()) {
            $this->retrieveRecord();
        }

        $this->iterator_container = array_merge($this->fields, $this->object_fields);

        reset ($this->iterator_container);
    }

    public function valid()
    {
        return $this->iterator_container && current($this->iterator_container) !== false;
    }
}
