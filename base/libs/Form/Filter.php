<?php
namespace Vendimia\Form;

/**
 * Filters for control input.
 *
 * You can use any PHP internal function who accepts one parameter
 * and returns a string, as a filter. Just write its name as a 
 * string. You can also use these constants as a syntax-highlighted 
 * version of those.
 */
class Filter
{
    const toUpper = 'mb_strtoupper';
    const toLower = 'mb_strtolower';
    const htmlEntities = 'htmlspecialchars';
}