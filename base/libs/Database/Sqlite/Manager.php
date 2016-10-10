<?php
namespace Vendimia\Database\Sqlite;

use Vendimia\Database;
use Vendimia\Database\Fields;
/**
 * Class for modify the database structure
 */
class Manager //implements Database\ManagerInterface
{
    private $connection = null;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Returns a field statement line, used for ALTER and CREATE
     */
    private function fieldDefinition($name, $fielddef) 
    {
        $fieldstr = $this->connection->escapeIdentifier($name);

        // Tipo
        $field = ' ' . $this->connection->getFieldString($fielddef[0]);
        $fieldstr .= $field;

        // Unico?
        if (ifset($fielddef['unique'])) {
            $fieldstr .= ' UNIQUE';
        }
        
        // Permite nulos?
        if (!ifset($fielddef['null'])) {
            $fieldstr .= ' NOT';
        }
        $fieldstr .= ' NULL';

        // Valor por defecto?
        if (isset($fielddef['default'])) {
            $fieldstr .= ' DEFAULT ' . $this->connection->escape($fielddef['default']);
        }

        return $fieldstr;
    }

    public function createTable($tablename, array $tabledef)
    {
        // Buscamos llaves primarias en la definición. Si no hay, creamos una.
        $pks = [];
        foreach ($tabledef as $name => $fielddef) {
            if (ifset($fielddef['primary_key'])) {
                $pks[] = $name;
            }
        }

        if (!$pks) {
            $tabledef = array_merge([
                'id' => [Fields::Integer,
                ],
            ], $tabledef);
            $pks[] = 'id';
        }

        $sql = 'CREATE TABLE ' . $this->connection->escapeIdentifier($tablename) . " (\n";

        $fields = [];
        foreach ($tabledef as $name => $fielddef) {
            $fields[] = $this->fieldDefinition($name, $fielddef);
        }

        // Creamos los primary keys
        $fields[] = 'PRIMARY KEY (' . 
            join(',', $this->connection->escape($pks)) .
            ')';

        $sql .= join(",\n", $fields) . "\n);";

        return $sql;
    }

    /**
     * {@inherit}
     * Sqlite does not support regular ALTER TABLE
     */
    public function alterTable($tablename, array $tabledef)
    {
        throw new \RuntimeException("SQLite doesn't support regular ALTER TABLE.");
    }

    public function getTableStructure($table)
    {

    }
}
