<?php
namespace Vendimia\Logger\Formatter;

interface FormatterInterface
{
    public function format($message, array $context);
}