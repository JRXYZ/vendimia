<?php
namespace Vendimia\Form;

/**
 * Processing filters for data input.
 */
class Filter extends FunctionHelper
{
    public static function toLowerFilter($value) {
        return mb_strtolower($value);
    }

    public static function toUpperFilter($value) {
        return mb_strtoupper($value);
    }

    public static function htmlEntitiesFilter($value) {
        return htmlspecialchars($value);
    }
}