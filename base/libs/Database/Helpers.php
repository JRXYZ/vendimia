<?php
namespace Vendimia\Database;

class Helpers
{
    /**
     * Returns a escaped "field = value" string
     */
    public static function fieldValue($field, $value) 
    {
        $connector = Database::getConnector();

        return $connector->escapeIdentifier ($field) . '=' . 
            $connector->escape($value);
    }

    /**
     * Executes a code within a SQL Transaction
     */
    public static function transaction($callback)
    {
        $connector = Database::getConnector();

        $connector->startTransaction();
        $returnvalue = null;
        try {
            $returnvalue = $callback();
        } catch (Exception $e) {
            // Cualquier excepcion genera un rollback
            $connector->rollbackTransaction();
            // Y relanzamos la excepcion
            throw $e;
        }
        $connector->commitTransaction();

        return $returnvalue;
    }
}