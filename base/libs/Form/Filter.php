<?php
namespace Vendimia\Form;

/**
 * Filters for control input.
 *
 * You can use any PHP internal function as a filter. This function
 * must accept one string param and returns a string.
 *
 * You can also use these constants as a syntax-highlighted version of 
 * those.
 */
class Filter
{
    const toUpper = 'mb_strtoupper';
    const toLower = 'mb_strtolower';
    const htmlEntities = 'htmlspecialchars';
}