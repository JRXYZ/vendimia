<?php
namespace Vendimia\Phpss;

class Colors {
    /**
     * Colores básicos CSS
     */
    static $named_colors = [
        'aqua' => [0,255,255],
        'black' => [0,0,0],
        'blue' => [0,0,255],
        'fuchsia' => [255,0,255],
        'gray' => [128,128,128],
        'green' => [0,128,0],
        'lime' => [0,255,0],
        'maroon' => [128,0,0],
        'navy' => [0,0,128],
        'olive' => [128,128,0],
        'orange' => [255,165,0],
        'purple' => [128,0,128],
        'red' => [255,0,0],
        'silver' => [192,192,192],
        'teal' => [0,128,128],
        'white' => [255,255,255],
        'yellow' => [255,255,0],
    ];

    /**
     * Gets a [r, g, b] array from hexadecimal color or named colors.
     *
     * @return array Array with RGB byte components,
     */
    public static function fromHex($color) 
    {

        // Version larga
        if ( preg_match ( '/^\#[0-9a-f]{6,7}$/i', $color) ) {
            $array_color = [
                hexdec ( substr ( $color, 1, 2 ) ),
                hexdec ( substr ( $color, 3, 2 ) ),
                hexdec ( substr ( $color, 5, 2 ) ),
            ];

            // SI tiene un 4to par, es el alfa.
            if ( strlen ( $color ) == 8 ) {
                $array_color[] = hexdec ( substr ( $color, 5, 2 ) );
            }

            return $array_color;
        }
        // Version corta
        elseif ( preg_match ( '/^\#[0-9a-f]{3,4}$/i', $color) ) {
            $array_color = [
                hexdec ( $color{1} . $color{1} ),
                hexdec ( $color{2} . $color{2} ),
                hexdec ( $color{3} . $color{3} ),
            ];

            // SI tiene un 4to caracter, es el alfa.
            if ( strlen ( $color ) == 5 ) {
                $array_color[] = hexdec ( $color{4} . $color{4} );
            }

            return $array_color;
        } elseif (isset(self::$named_colors[$color])) {
            // Version CSS2
            return self::$named_colors[$color];
        } else {
            return null;
        }
   }

   /**
    * Creates a CSS hexadecimal color from RGB array.
    *
    * @param array $color Array of RGB color.
    */
   public static function toHex(array $color) 
   {
        // Si hay un alpha, usamos rgba
        if (isset($color[3])) {
            return 'rgba(' . 
                    join(',' , array_slice ( $color, 0, 3 ) ) . 
                    ',' . $color[3]/100 . ')';
        }
        else {
            // Retornamos hex
            $hex = '#';
            foreach ( $color as $c ) {
                $hex .= str_pad ( dechex($c), 2, 0, STR_PAD_LEFT );
            }
            return $hex;
        }
    }
}