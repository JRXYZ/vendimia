<?php
namespace Vendimia\Logger;

use Psr;

class Logger implements Psr\Log\LoggerInterface
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    const PRIORITY = [
        'emergency' => 0,
        'alert' => 1, 
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    private $target = [];

    public function emergency($message, array $context = []) 
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []) 
    {
        $this->log(self::ALERT, $message, $context);
    }

    public function critical($message, array $context = []) 
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []) 
    {
        $this->log(self::ERROR, $message, $context);
    }

    public function warning($message, array $context = []) 
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function notice($message, array $context = []) 
    {
        $this->log(self::NOTICE, $message, $context);
    }

    public function info($message, array $context = []) 
    {
        $this->log(self::INFO, $message, $context);
    }

    public function debug($message, array $context = []) 
    {
        $this->log(self::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        $priority = self::PRIORITY[$level];
        foreach ($this->target as $target) {
            list($target_object, $target_priority) = $target;

            if ($priority <= $target_priority ) {
                $target_object->write($message, $context);
            }
        }
    }

    public function addTarget($level, $target)
    {
        $this->target[] = [$target, self::PRIORITY[$level]];

        return $this;
    }
}