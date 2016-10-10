<?php
namespace Vendimia\Database\Mysql;

use Vendimia\Database;
use Vendimia\Database\Fields;
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

        // Es un autoincrement?
        if (ifset($fielddef['auto_increment'])) {
            $fieldstr .= ' AUTO_INCREMENT';
        }

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

    public function changeColumn($table, $oldfieldname, $fieldname, $fielddef)
    {
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
       // Obtenemos una tabla reversa de campos
        $class = get_class($this->connection);
        $revfield = array_flip($class::Fields);

        $tabledef = [];
        $indexes = [];
        $primary_keys = [];

        // Procesamos el resultado
        try {
            $result = $this->connection->execute('DESCRIBE ' . 
                $this->connection->escapeIdentifier($table));
        } catch (Database\QueryException $e){
            // Es MUY probable que la tabla no exista.
            return null;
        }

        foreach ($result as $row) {
            $fieldname = '';
            $fielddef = [];

            // Nombre
            $fieldname = $row['Field'];

            // Tipo y longitud. Lo convertimos a tipo Vendimia
            $fieldtype = $row['Type'];

            $l1 = null;
            $l2 = null;
            $matches = [];
            if (preg_match ('/\((.*?)\)/', $fieldtype, $matches) == 1) {
                $length = $matches[1];
                $fieldtype = preg_replace('/\((.*?)\)/', '', $fieldtype);

            }
            $fielddef["type"] = $revfield[$fieldtype];

            // Solo nos importa la longitud de algunos campos
            if (in_array ($fieldtype, ['char', 'varchar', 'decimal', 'binary', 'varbinary'])) {
    
                $fielddef["length"] = $length;
            }

            // null o not null, that is the question...

            $fielddef['null'] = $row['Null'] == "YES"?true:false;

            // Default value
            if ($row['Default'] == "NULL") {
                $default_value = null; 
            } else {
                $default_value = $row['Default'];
            }
            
            // Si default_value es numérico, le sacamos los 0s, ya que
            // MySQL padea con 0s los decimales de un "decimal"
            if (is_numeric($default_value)) {
                $default_value = (string)floatval($default_value);
            }

            // Autoincrement?
            if (strpos($row['Extra'], 'auto_increment') !== false) {
                $fielddef['auto_increment'] = true;
            }

            $fielddef['default'] = $default_value;

            $tabledef[$fieldname] = $fielddef;
        }

        // Ahora los índices
        $result = $this->connection->execute('SHOW INDEXES FROM ' . 
            $this->connection->escapeIdentifier($table));

        foreach ($result as $row) {
            $keydef = [];

            $fieldname = $row['Column_name'];
            $keyname = $row['Key_name'];
            $unique = $row['Non_unique'] == 0;

            // Primary key
            if ($keyname == "PRIMARY") {
                $primary_keys[] = $fieldname;

                // move on...
                continue;
            }

            // Si ya está definiedo el índice, sólo le añadimos 
            // el campo
            if (isset($indexes[$keyname])) {
                $indexes[$keyname]['fields'][] = $fieldname;
            } else {
                $indexes[$keyname] = [
                    'unique' => $unique,
                    'fields' => [$fieldname],
                ];
            }
        }

        return [
            'fields' => $tabledef,
            'indexes' => $indexes,
            'primary_keys' => $primary_keys
        ];
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
