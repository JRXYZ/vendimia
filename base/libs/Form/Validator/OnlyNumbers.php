<?php
namespace Vendimia\Form\Validator;

class OnlyNumbers
{
    public static function validate ($target, $args = [])
    {
        return RegExp::validate($target, [
            'regexp' => '\-?^\d+\.?\d*',
        ])
    }
}