<?php
namespace Vendimia\ActiveRecord;

/**
 * Base methods and properties for ActiveRecord objects,
 */
abstract class Base 
{
    /** Original FQCN name who originate this object */ 
    protected $base_class;

    /** Flag for query execution */
    protected $query_executed = false;

    /** Is this record/recordset empty? */
    protected $is_empty = false;

    // Database connection
    public static $connection = null;

    /**
     * Sets this class creation name.
     */
    protected function setClassName($class_name)
    {
        $this->class_name = $class_name;
    }

    public function go() {
        $this->executeQuery();
    }

}
