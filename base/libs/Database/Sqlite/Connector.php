<?php
namespace Vendimia\Database\Sqlite;

use Vendimia\Database;
use Vendimia\Database\Fields;
use SQLite3;

class Connector // implements Database\ConnectionInterface
{
    /** Field names used in this type of db */
    const Fields = [
        Fields::Bool => 'integer',
        Fields::Byte => 'integer',
        Fields::SmallInt => 'integer',
        Fields::Integer => 'integer',
        Fields::BigInt => 'integer',

        Fields::Float => 'real',
        Fields::Double => 'real',
        Fields::Decimal => 'numeric',

        Fields::Char => 'text',
        Fields::FixChar => 'text',
        Fields::Text => 'text',
        Fields::Blob => 'blob',

        Fields::Date => 'numeric',
        Fields::Time => 'numeric',
        Fields::DateTime => 'numeric',
    ];

    public function __construct($def)
    {
        $filename = 'database.sqlite';
        $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        $encryption_key = null;
        extract ($def, EXTR_IF_EXISTS);

        $this->connection = new SQLite3($filename, $flags, $encryption_key);
    }

    public function getFieldString($id)
    {
        return self::Fields[$id];
    }

    public function escape($string, $quotation = '\'')
    {
        if (is_string($string)) {
            return $quotation . 
                $this->connection->escapeString($string) .
                $quotation;
        } elseif (is_array($string)) {
            $that = $this;
            array_map(function($str) use ($that, $quotation)  {
                return $that->escape($str, $quotation);
            }, $string);

            return $string;
        }
    }

    public function escapeIdentifier($string)
    {
        return $this->escape($string, '"');
    }

}