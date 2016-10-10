<?php
namespace Vendimia\ActiveRecord;

/**
 * Class for interact with a set of Records.
 */
class RecordSet extends Base implements \Iterator
{
    use QueryBuilder;

    /** Fields and values for each new record added to this RecordSet */
    private $constrains = [];

    /** Cursor returned from the database */
    private $cursor = null;

    /* Last record retrieved */
    private $last_record;

    /* Index for the iterator */
    private $iterator_index;

    public function  __construct($class, $constrains = null)
    {
        $this->base_class = $class;
        $this->constrains = $constrains;

        // Los constrains se usan también para el where
        if ($constrains) {
            $this->where($constrains);
        }
    }

    /**
     * Executes the query, and returns a cursor
     */
    private function retrieveRecordset()
    {
        if ($this->query_executed) {
            return;
        }

        $this->cursor = $this->executeQuery();

        $this->query_executed = true;
    }

    /**
     * Returns the next record in this recordset
     */
    public function fetch() {
        $this->retrieveRecordset();
        
        $class = $this->base_class;
        $data = $class::$connection->fetchOne($this->cursor);
        if (!$data) {
            $this->is_empty = true;
            return null;
        }

        return new $class($data);
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
     * Counts the total records from this recordset using the database
     *
     * @return int Record count.
     */
    public function count()
    {
        $target = clone $this;
        $class = $target->base_class;

        $target->query['fields'] = ['COUNT(*)' => "__Vendimia_count"];
        $cursor = $target->executeQuery();
        
        $data = $class::$connection->fetchOne($cursor);

        return intval($data['__Vendimia_count']);
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
        $this->retrieveRecordset();
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
