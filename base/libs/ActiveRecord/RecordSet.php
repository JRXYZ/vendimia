<?php
namespace Vendimia\ActiveRecord;

/**
 * Class for interact with a set of Records.
 */
class RecordSet extends Base implements \Iterator
{
    use QueryBuilder;

    /** Relationship recordset status */
    private $is_relationship = false;

    /** For relationship recordsets, name of the foreign key field */
    private $fk_name;

    /** For relationship recordsets, value of the foreign key */
    private $fk_value;

    /** For relationship recordsets, model where the foreign key is stored.
        Used for 'through' relationships. If it is null, then this is a 
        simple one-to-many relaption. */
    private $fk_model = null;

    /** Cursor returned from the database */
    private $cursor = null;

    /* Last record retrieved */
    private $last_record;

    /* Index for the iterator */
    private $iterator_index;

    public function __construct($base_class)
    {
        $this->base_class = $base_class;
    }

    /**
     * Sets this recordset into 'Relationship' mode.
     */
    public function setRelationship($fk_name, $fk_value, $fk_model = null) 
    {
        $this->fk_name = $fk_name;
        $this->fk_value = $fk_value;
        $this->fk_model = $fk_model;

        if (is_null($fk_model)) {
            // Relación one-to-many
            $this->where([
                $fk_name => $fk_value
            ]);
        } else {
            // Relación through
            
        }
    }

    /**
     * Executes the query, and returns a cursor
     */
    private function retrieveRecordset($force = false)
    {
        if ($this->query_executed && !$force) {
            return;
        }

        $this->cursor = $this->executeQuery();

        $this->query_executed = true;
    }

    /**
     * Returns the next record in this recordset
     */
    public function fetch()
    {
        $this->retrieveRecordset();
        
        $class = $this->base_class;
        $data = $class::$connection->fetchOne($this->cursor);
        if (!$data) {
            $this->is_empty = true;
            return null;
        }
        return new $class($data, true);
    }

    /**
     * Adds a record to this recordset
     */
    public function add(...$records) 
    {
        if (is_null($this->fk_model)) {
            // one-to-many. A cada registro le añadimos el foreign key
            foreach ($records as $record) {
                $record->update([
                    $this->fk_name => $this->fk_value
                ]);
            }
        } else {
                // Through: TODO
        }
    }

    /**
     * Iterates over this recordset, executing the callback
     */
    public function each(\Closure $callback)
    {
        foreach ($this as $record) {
            $callback($record);
        }
    }

    /**
     * Executes a single SQL function on a field.
     *
     * 
     *
     * @param string $function SQL function
     * @param string $field Field name where to execute the function
     */
    private function executeFunction($function, $field = '*') 
    {
        $target = clone $this;
        $class = $target->base_class;

        $target->query['fields'] = ["{$function}({$field})" => "__vendimia_function_result"];
        $cursor = $target->executeQuery();
        
        $data = $class::$connection->fetchOne($cursor);

        return $data['__vendimia_function_result'];

    }

    /**
     * Returns the max value for a field in this RecordSet
     *
     * @param string $field Field name for finding the max value.
     */
    public function max($field) 
    {
        return $this->executeFunction('max', $field);
    }

    /**
     * Returns the min value for a field in this RecordSet
     *
     * @param string $field Field name for finding the min value.
     */
    public function min($field) 
    {
        return $this->executeFunction('min', $field);
    }

    /**
     * Returns the average value for a field in this RecordSet
     *
     * @param string $field Field name for finding the average value.
     */
    public function avg($field) 
    {
        return $this->executeFunction('avg', $field);
    }


    /**
     * Counts the total records from this recordset using the database
     *
     * @return int Record count.
     */
    public function count()
    {
        return intval($this->executeFunction('count'));
    }

    /**
     * Deletes all the records whithin this recordset
     */
    public function delete()
    {
        $class = $this->base_class;

        if ($this->constrains) {
            // TODO: Delete using constranis
        } else {
            // Usamos el where
            $class::$connection->delete($class::$table, $this->query['where']);
        }
    }

    public function current()
    {
        return $this->last_record;
    }

    public function key() 
    {
        return $this->iterator_index;
    }

    public function next()
    {

    }

    public function rewind()
    {
        $this->retrieveRecordset(true);
        $this->iterator_index = 0;
    }

    public function valid()
    {
        $record = $this->fetch();
        
        if (is_null($record)) {
            $this->is_empty = true;
            return false;
        }

        $this->last_record = $record;
        $this->iterator_index++;
        return true;
    }
}
