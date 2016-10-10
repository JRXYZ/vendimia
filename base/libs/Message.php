<?php
namespace Vendimia;

use Vendimia;

class Message
{
    private static function addMessage($message, $type = 'message')
    {
        $m = Vendimia::$session->messages;
        $m[] = [
            'type' => $type,
            'message' => $message,
        ];
        
        Vendimia::$session->messages = $m;
    }

    public static function message($message)
    {
        self::addMessage($message, 'message');
    }

    public static function warning($message)
    {
        self::addMessage($message, 'warning');
    }

    public static function error($message)
    {
        self::addMessage($message, 'error');
    }
}