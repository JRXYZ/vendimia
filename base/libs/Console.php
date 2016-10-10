<?php
namespace Vendimia;

/**
 * Class for write in console in ANSI colors if available.
 */
class Console {
    const COLORS = [
        'black' => 0,
        'red' => 1,
        'green' => 2,
        'yellow' => 3,
        'blue' => 4,
        'magenta' => 5,
        'cyan' => 6,
        'white' => 7,
    ];

    const MODULES = [
        'app' => 'white',
        'controller' => 'green',
        'form' => 'blue',
        'project' => 'white',
        'path' => 'cyan',
        'param' => 'white',
        'table' => 'green',
        'model' => 'cyan',
        'tabledef' => 'magenta',
    ];

    /**
     * These vars will be filled with \001 and \002 for hinting the readline 
     * functions.
     */
    private static $rl_on = '';
    private static $rl_off = '';

    /** Used for cutting long lines, if not null */
    private static $term_width = null;

    /** Disable ANSI color printing */
    private static $disable_colors = false;

    /** */

    /**
     * Checks terminal capabilities
     */
    public static function init()
    {
        if (!posix_isatty(STDOUT)) {
            self::$disable_colors = true;
        }
        else {
            // TODO: cross-platform support 
        }
    }

    /**
     * Parse string with ANSI colors
     */
    public static function parse($string)
    {
        $result = preg_replace_callback('/{(.+?) +(.+?)}/', function($matches) {
            list($dummy, $color, $text) = $matches;

            if ($color{0} == ':') {
                $tipo = substr($color, 1);
                if (array_key_exists($tipo, self::MODULES)) {
                    $color = self::MODULES[$tipo];

                } else {
                    $color = 'white';
                }
            }

            return self::color($color, $text);
        }, $string);

        return $result;
    }

    /**
     * Returns a string with an ANSI color
     */
    public static function color($color, $text)
    { 
        $result = self::$rl_on;
        $result .= "\x1b[" . (30+self::COLORS[$color]) . ";1m";
        $result .= self::$rl_off;
        $result .= $text;
        $result .= self::$rl_on . "\x1b[0m" . self::$rl_off;

        return $result;
    }

    /**
     * Writes to the console, parse the string first, and adds a \n afterwards
     */
    public static function write($text) 
    {
        echo self::parse($text) . "\n";
    }

    /**
     * Fails with and error, and exit
     */
    public static function fail($message, $exitcode = 1)
    {
        self::write("{red ERROR}: $message");
        exit($exitcode);
    }

    /**
     * Shows a warning
     */
    public static function warning($message)
    {
        self::write("{green Warning}: $message");
    }

    /**
     * Converts $status to ANSI-colored messages
     */
    public static function fromStatus($command, $status, $extra)
    {
        switch ($status) {
            case 'overwrite':
                self::write("{green OVERWRITE $command} $extra");
                break;
            case 'fail':
                self::write("{red FAIL $command} $extra");
                break;
            case 'omit':
                self::write("{black OMITTING $command} $extra");
                break;
            default;
                self::write("{white $command} $extra");
                break;
        }
    }

    /**
     * Activate the readline hinting
     */
    public static function readline_on()
    {
        static::$rl_on = "\001";
        static::$rl_off = "\002";
    }

    /**
     * Deactivate the readline hinting
     */
    public static function readline_off() 
    {
        static::$rl_on = '';
        static::$rl_off = '';
    }

    /**
     * Static magic method for return a ANSI-colored text
     */
    public static function __callStatic($function, $args)
    {
        return self::color($function, $args[0]);
    }
}