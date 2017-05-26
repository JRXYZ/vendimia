<?php
namespace Vendimia\Database\Sqlite;

use Vendimia\Database;
use Vendimia\Database\Field;
use Vendimia\Database\Tabledef;

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

    public function fieldDefinition($name, array $fielddef)
    {

        // Nombre
        $fieldstr = $this->connection->escapeIdentifier($name);

        // Tipo
        $fieldstr .= ' ' . $this->connection->getFieldString($fielddef['type']);

        // Probamos los campos de longitud y decimales, de haber
        if (isset($fielddef['length'])) {
            $fieldstr .= '(' . $fielddef['length'] . ')';
        }

        // Permite nulos?
        if (!ifset($fielddef['null'])) {
            $fieldstr .= ' NOT';
        }
        $fieldstr .= ' NULL';

        // En SQlite no es necesario el AUTOINCREMENT, los campos
        // primary key son un alias de ROWID 

        // Valor por defecto?
        if (isset($fielddef['default'])) {
            $fieldstr .= ' DEFAULT ' . $this->connection->valueFromPHP($fielddef['default']);
        } 
        return $fieldstr;
    }

    public function createTable(Tabledef $tabledef)
    {
        $tablename = $tabledef->getTableName();
        $sql = 'CREATE TABLE ' . $this->connection->escapeIdentifier($tablename) . " (\n";

        $fields = [];
        foreach ($tabledef -> getTableDef() as $name => $fielddef) {
            $fields[] = $this->fieldDefinition($name, $fielddef);
        }

        // Creamos los primary keys
        $fields[] = 'PRIMARY KEY (' . 
            join(',', $this->connection->escape($tabledef->getPrimaryKeys())) .
            ')';

        $sql .= join(",\n", $fields) . "\n)";

        $this->connection->execute($sql);

        // Ahora creamos los indexes
        foreach ($tabledef->getIndexes() as $indexname => $indexdef) {
            $this->createIndex($tablename, $indexname, $indexdef);
        }
    }

    public function addColumn($table, $fieldname, $fielddef)
    {
        $sql = 'ALTER TABLE ' . $this->connection->escapeIdentifier($table) .
            ' ADD COLUMN ' . $this->fieldDefinition($fieldname, $fielddef);

        return $this->connection->execute($sql);
    }

    private function alterTable($table,  )

    
    public function changeColumn($table, $oldfieldname, $fieldname, $fielddef)
    {
        // SQLITE no soporta alter table,
        $sql = 'ALTER TABLE ' . $this->connection->escapeIdentifier($table) .
            ' CHANGE COLUMN ' . $this->connection->escapeIdentifier($oldfieldname) .
            ' ' . $this->fieldDefinition($fieldname, $fielddef);

        return $this->connection->execute($sql);
    }

    public function dropColumn($table, $fieldname)
    {
        $sql = 'ALTER TABLE ' . $this->connection->escapeIdentifier($table) .
            ' DROP COLUMN ' . $this->connection->escapeIdentifier($fieldname);

        return $this->connection->execute($sql);
    }


    public function createIndex($tablename, $indexname, $indexdef) 
    {
        $sql = 'ALTER TABLE ' . $this->connection->escapeIdentifier($tablename);
        $sql .= ' ADD ';
        if ($indexdef['unique']) {
            $sql .= 'UNIQUE ';
        }
        $sql .= 'INDEX ' . $this->connection->escapeIdentifier($indexname);

        $sql .= ' (' . join(', ', $this->connection->escape($indexdef['fields']));
        $sql .= ')';

        return $this->connection->execute($sql);
    }

    public function dropIndex($tablename, $indexname)
    {
        $sql = 'ALTER TABLE ' . $this->connection->escapeIdentifier($tablename);
        $sql .= ' DROP INDEX ' . $this->connection->escapeIdentifier($indexname);

        return $this->connection->execute($sql);
    }

    public function updateIndex($tablename, $indexname, $indexdef) 
    {
        // No existe alter index...
        $this->dropIndex($tablename, $indexname);
        $this->createIndex($tablename, $indexname, $indexdef);
    }

    public function getTableStructure($table)
    {
        $tabledef = [];
        $indexes = [];
        $primary_keys = [];

        $result = $this->connection->execute('PRAGMA table_info(' .
            $this->connection->escapeidentifier($table) . ')');

        // Si no hay columnas en el result, la tabla no existe
        if ($result->numColumns() == 0) {
            return null;
        }

        var_dump($result->numColumns());exit;
    }

    public function sync(Tabledef $tabledef)
    {
        // Información de la DB
        $dbdef = $this->getTableStructure($tabledef->getTableName());

        if (is_null($dbdef)) {

            // La tabla no existe. La creamos
            $this->createTable($tabledef);

            yield ['CREATE', 'ok', $tabledef->getTableName()];
            return;
        }


        // ***
        // FIELDDEFS 
        // ***

        $dbfields = $dbdef['fields'];
        $arrayfields = $tabledef->getTableDef();
        $renamedfields = $tabledef->getRenamedFields();

        $new = array_keys($arrayfields);
        $rename = [];
        $update = [];
        $delete = [];

        // Revisamos la base de datos
        foreach ($dbfields as $dbfieldname => $dbfielddef ) {
            // Existe este campo de la db en el array?
            if (isset($arrayfields[$dbfieldname])) {

                // Ese campo ya no es nuevo. Lo removemos. Usamos este
                // hack que parece ser el más eficiente para remover elementos
                // de un array por su valor
                foreach (array_keys($new, $dbfieldname, true) as $k) {
                    unset ($new[$k]);
                }

                // Ordenamos los arrays para hacer una comparación con ===
                $left_data = $arrayfields[$dbfieldname];
                $right_data = $dbfielddef;

                ksort ($left_data);
                ksort ($right_data);

                if ($left_data !== $right_data) {
                    $update[] = $dbfieldname;
                    continue;
                }
            } else {
                // Si no existe, quizas lo estemos renombrando
                //var_dUMP($arraydef['renamed_fields']);
                if ($renamedfields && 
                    isset($renamedfields[$dbfieldname])) {

                    $new_name = $renamedfields[$dbfieldname];

                    $rename[$dbfieldname] = $new_name;

                    // Borramos el campo de $NEW
                    foreach (array_keys($new, $new_name, true) as $k) {
                        unset ($new[$k]);
                    }

                } else {
                    // NO existe. Lo borramos
                    $delete[] = $dbfieldname;
                }
            }
        }

        // Campos nuevos
        foreach ($new as $field) {
            $this->addColumn(
                $tabledef->getTableName(),
                $field, 
                $tabledef->getTableDef($field)
            );
            yield ['ADD COLUMN', 'ok', $field];
        }

        foreach ($rename as $old => $field) {
            $this->changeColumn(
                $tabledef->getTableName(),
                $old,
                $field, 
                $tabledef->getTableDef($field)
            );
            yield ['RENAME COLUMN', 'ok', $field];
        }
        foreach ($update as $field) {
            $this->changeColumn(
                $tabledef->getTableName(),
                $field,
                $field, 
                $tabledef->getTableDef($field)
            );
            yield ['UPDATE COLUMN', 'ok', $field];
        }
        foreach ($delete as $field) {
            $this->dropColumn(
                $tabledef->getTableName(),
                $field
            );
            yield ['DROP COLUMN', 'ok', $field];
        }

        // Índices!
        $dbindexes = $dbdef['indexes'];
        $arrayindexes = $tabledef->getIndexes();

        $new = array_keys($arrayindexes);
        $update = [];
        $delete = [];

        foreach ($dbindexes as $dbindexname => $dbindexdef) {
            // Existe este índice de la DB en la definición?
            if (isset($arrayindexes[$dbindexname])) {
                // Si existe. No es nuevo.

                foreach (array_keys($new, $dbindexname, true) as $k) {
                    unset ($new[$k]);
                }

                // Ordenamos los arrays para hacer una comparación con ===
                $left_data = $arrayindexes[$dbindexname];
                $right_data = $dbindexdef;

                ksort ($left_data);
                ksort ($right_data);

                if ($left_data !== $right_data) {
                    $update[] = $dbindexname;
                    continue;
                }
            } else {
                // No existe. Lo borramos
                $delete[] = $dbindexname;
            }
        }

        foreach ($new as $index) {
            $this->createIndex(
                $tabledef->getTableName(),
                $index, 
                $tabledef->getIndexes($index)
            );
            yield ['ADD INDEX', 'ok', $index];

        }
        foreach ($update as $index) {
            $this->updateIndex(
                $tabledef->getTableName(),
                $index, 
                $tabledef->getIndexes($index)
            );
            yield ['UPDATE INDEX', 'ok', $index];

        }
        
        foreach ($delete as $index) {
            $this->dropIndex(
                $tabledef->getTableName(),
                $index
            );
            yield ['DROP INDEX', 'ok', $index];

        }
    }
}
