<?php
namespace Vendimia\Logger\Target;

class PhpErrorLog extends TargetBase implements TargetInterface
{
    public function write($message, array $context)
    {
        error_log($this->formatter->format($message, $context));
    }
}