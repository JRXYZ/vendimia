<?php
namespace Vendimia\Database;

/**
 * Class for alter the database structure
 *
 * A 'tabledef' is an associative array 'field name' => 'field options'.
 * 
 * Field options has two parts: numeric indexes and string indexes.
 *
 * The numeric indexes are:
 *      index 0: Vendimia field type, a constant from Vendimia\Databases\Fields
 *      index 1: Field length, required for some types
 *      index 2: Decimal places, required for some numeric types
 *
 * The string indexes are:
 *      default:        String, default value for the field
 *      index:          Bool or array, creates an index for this field.
 *      primary_key:    Bool, use this field as Primary Key.
 *      null:           Bool, true if this field accepts null values.
 *
 * A 'index' option with 'true' will create a non-unique index for this field.
 
 * If the 'index' option is an array, these options are available:
 *      name:           Name of the index, used for multiple field indexes.
 *      unique:         true if this index must have the 'unique' constrain.
 *
 *
 */
interface ManagerInterface
{
    /**
     * Constructs this object linked to a connection
     */
    public function __construct($connection);

    /**
     * Creates a new table
     */
    public function createTable($table, $tabledef);

    /**
     * Alter the structure of a table
     */
    public function alterTable($table, $tabledef);

    /**
     * Parse a Vendimia table def, normalize its values, and obtains its indexes 
     * and primary keys.
     */
    public function parseTabledef($tabledef);

    /**
 |    * Reads and process a table structure and indexes from the database, 
     *
     * @return array Array with [Vendimia table structure, indexes, primary keys]
     */
    public function getTableStructure($table);

    /**
     * Sync a db table with a tabledef
     */
    public function sync($table, $tabledef);
}
