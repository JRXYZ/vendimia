<?php
namespace Vendimia;

use Vendimia;

/**
 * Build URLs from arrays.
 */
class Url
{
    /** Each part of the URL */
    private $parts = [];

    /** Each URL GET arguments */
    private $args = [];

    /** Schema. false for relative urls */
    private $schema = false;

    public function __construct(...$parts)
    {
        // Buscamos la primera parte, para determinar si es relativo o
        // absoluto
        if (is_array($parts[0])) {
            $first_part = &$parts[0][0];
        } else {
            $first_part = &$parts[0];
        }

        $matches = [];
        if (preg_match ('/^(.+):\/\//', $first_part, $matches)) {
            $this->schema = $matches[1];
            $first_part = substr($first_part, strlen($matches[0]));
        }

        $this->processParts(...$parts);
    }

    /**
     * Analize each part, returns a simple array of string parts.
     */
    public function processParts(...$parts)
    {
        // Esto es necesario para poder pasar un array asociativo
        if (count($parts) == 1 && is_array($parts[0])) {
            $parts = $parts[0];
        }
        foreach ($parts as $key => $data) {

            // Si $key no es numérico, es un argumento
            if (!is_numeric($key)) {
                $this->args[$key] = urlencode($data);
                continue;
            }

            $value = null;

            // Lo que queda debe ser partes de la URL
            if (is_object($data)) {
                if ($data instanceof Vendimia\ActiveRecord\Record) {
                    $value = $data->pk();
                } else {
                    $value = (string)$data;
                }
            } elseif (is_array($data)) {
                // Recursión
                $this->processParts($data);
                continue;
            } else {
                // Aqui deberían llegar solo strings
                foreach (explode('/', $data) as $part) {
                    $colonpos = strpos($part, ':');
                    $prepart = null;

                    if ($colonpos !== false) {
                        $app = substr($part, 0, $colonpos);
                        if (!$app) { 
                            $app = Vendimia::$application;
                        }
                        $value = $app;
                        $this->parts[] = urlencode($value);

                        $part = substr($part, ++$colonpos);
                    }

                    $this->parts[] = urlencode($part);
                }
            }
        }
    }

    /**
     * Builds the path using $this->parts[] and $this->args[]
     */
    public function get()
    {
        if ($this->schema) {
            // Absoluto
            //$url = 
            array_unshift($this->parts, $this->schema . ':/');
            
        } else {
            // Relativo
            array_unshift($this->parts, rtrim(Vendimia::$base_url, '/.'));
        }


        $url = join('/', $this->parts);

        if ($this->args) {
            $url .= '?' . http_build_query($this->args);
        }

        return $url;
    }

    /**
     * Static shortcut method
     */
    public static function parse(...$params) 
    {
        return (new self(...$params))->get();
    }

    /**
     * Magic method for conver to string
     */
    public function __toString()
    {
        return $this->get();
    }
}
