<?php
namespace Vendimia\Database\Mysql;

use Vendimia\Database;
use Vendimia\Database\ValueInterface;
use Vendimia\Database\Fields;
use mysqli;

class Connector implements Database\ConnectorInterface
{
    const Fields = [
        Fields::Bool => 'tinyint',
        Fields::Byte => 'tinyint',
        Fields::SmallInt => 'smallint',
        Fields::Integer => 'int',
        Fields::BigInt => 'bigint',

        Fields::Float => 'float',
        Fields::Double => 'double',
        Fields::Decimal => 'decimal',

        Fields::Char => 'varchar',
        Fields::FixChar => 'char',
        Fields::Text => 'text',
        Fields::Blob => 'blob',

        Fields::Date => 'date',
        Fields::Time => 'time',
        Fields::DateTime => 'datetime',

        Fields::ForeignKey => 'int',

    ];

    public function __construct($def)
    {
        $host = 'localhost';
        $username = null;
        $password = null;
        $database = null;
        $charset = 'utf8';
        extract ($def, EXTR_IF_EXISTS);

        if (is_null($database)) {
            throw new \RuntimeException('Database name is missing.');
        }

        $this->connection = mysqli_init();

        @$connected = $this->connection->real_connect(
            'p:' . $host, // Conexión persistente por defecto.
            $username, 
            $password, 
            $database, 
            null,
            null, 
            MYSQLI_CLIENT_FOUND_ROWS
        );

        if(!$connected) {
            throw new \RuntimeException("Error connection to MySQL database: " . $this->connection->connect_error);
        }

        $this->connection->set_charset($charset);
    }

    public function getFieldString($id)
    {
        return self::Fields[$id];
    }

    public function escape($string, $quotation = '\'')
    {
        if (is_array($string)) {
            $that = $this;
            array_map(function($str) use ($that, $quotation){
                return $that->escape($str, $quotation);
            }, $string);

            return $string;
        } elseif (is_numeric($string)) {
            // Si comillas
            return $string;
        } elseif (is_null($string)) {
            return 'NULL';
        } else {
            return $quotation . 
                $this->connection->real_escape_string($string) .
                $quotation;
        }
    }

    public function escapeIdentifier($string)
    {
        return $this->escape($string, '`');
    }

    public function valueFromPHP($value)
    {
        if (is_null($value)) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value?1:0;
        } elseif (is_numeric($value)) {
            return $value;
        } elseif (is_object($value)) {
             if ($value instanceof ValueInterface) {
                return $value->getDatabaseValue($this);
             } else {
                throw new \RuntimeException("Object of type '" . 
                    get_class($value) . "' can't be directly converted to a database value.") ;
             }
        } else {
            return $this->escape($value);
        }
    }

    /**
     * Builds a WHERE from an array
     *
     * @return string Stringified where
     */
    public function generateWhere(array $where)
    {
        $result = [];
        $primera_condicion = true;

        /*
        NO SE PARA QUÉ LE PUSE $not_list = false !!!! :-|
        if ($not_list) {
            $where = [$where];
        }
        */

        foreach ($where as $part) {

            if (!$primera_condicion) {
                $result[] = $part[0];
            }

            // Estamos negando?
            if ($part[1]) {
                $result[] = 'NOT';
            }

            // Procesamos el where
            if (is_string($part[2])) {
                $result[] = $part[2];
            } else {
                $result[] = $this->generateWhere($part[2]);
            }

            $primera_condicion = false;
        }

        return '(' . join(' ', $result) . ')';
    }

    /**
     * Builds and exceutes a SQL SELECT query from an array.
     */
    public function buildAndExecuteQuery($query)
    {
        $table = $this->escapeIdentifier($query['table']);

        if ($query['fields']) {
            // Los campos ya deben estar escapados
            $fields = [];
            foreach($query['fields'] as $field => $alias) {
                if (is_numeric($field)) {
                    $fields[] = $alias;
                } else {
                    $fields[] = "$field AS $alias";
                }
            }
        } else {
            $fields[] = $table. '.*';
        }

        $sql = 'SELECT ' . join(', ' , $fields) . ' FROM ' . $table;

        if ($query['where']) {
            $sql .= ' WHERE ' . $this->generateWhere($query['where']);
        }

        // ORDER BY
        if ($query['order']) {
            $order = [];
            foreach ($query['order'] as $o) {
                $desc = '';
                if ($o{0} == '-') {
                    $desc = ' DESC';
                    $o = substr($o, 1);
                }
                $order[] = $this->escapeIdentifier($o) . $desc;
            }
            $sql .= ' ORDER BY ' . join(', ', $order);
        }

        // LIMIT
        if ($query['limit']) {
            $sql .= ' LIMIT ' . intval($query['limit']);

            // no hay OFFSET sin LIMIT
            if ($query['offset']) {
                $sql .= ' OFFSET ' . intval($query['offset']);
            }
        }

        // GROUP BY
        if ($query['group']) {
            $sql .= 'GROUP BY ';
            $fields = $this->escape($query['group']);
            if (is_string($fields)) {
                $sql .= $fields;
            } else {
                $sql .= join(', ', $fields);
            }
        }

        return $this->execute($sql);
    }

    public function execute($query)
    {
        //var_dump($query);exit;
        $result = $this->connection->query($query);
        if ($result === false) {
            throw new Database\QueryException($this->connection->error, [
                'Query' => $query,
            ]);
        }
        return $result;
    }

    public function fetchOne($cursor) {
        return $cursor->fetch_assoc();
    }

    public function insert($table, array $data)
    {
        // No insertamos nada.
        if (!$data) {
            return null;
        }

        $fields = [];
        $values = [];

        foreach ($data as $field => $value) {
            $fields[] = $this->escapeIdentifier($field);
            $values[] = $this->valueFromPHP($value);
        }

        $sql = 'INSERT INTO ' . $this->escapeIdentifier($table). ' (';
        $sql .= join(', ', $fields) . ') VALUES (' . join(', ', $values) . ')';

        $this->execute($sql);

        return $this->connection->insert_id;
    }

    public function update($table, array $data, $where = null)
    {
        $values = [];
        foreach ($data as $field => $value) {

            $values[] = $this->escapeIdentifier($field) . '=' .
                $this->valueFromPHP($value);
        }

        $sql = 'UPDATE ' . $this->escapeIdentifier($table) . ' SET ' .
            join (', ', $values);

        if (!is_null($where)) {
            $sql .= ' WHERE ' . $where;
        }

        $result = $this->execute($sql);
        return $this->connection->affected_rows;
    }

    public function delete($table, $where) 
    {
        $sql = "DELETE FROM " . $this->escapeIdentifier($table);

        if ($where) {

            if (is_array($where)) {
                $where = $this->generateWhere($where);
            }

            $sql .= ' WHERE ' . $where;
        }

        $result = $this->execute($sql);
        return $this->connection->affected_rows;
    }

    public function startTransaction()
    {
        $this->execute ('START TRANSACTION');
    }

    public function commitTransaction()
    {
        $this->execute('COMMIT');
    }

    public function rollbackTransaction()
    {
        $this->execute('ROLLBACK');
    }

}
