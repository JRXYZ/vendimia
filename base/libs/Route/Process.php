<?php
namespace Vendimia\Route;

use Vendimia\Http\Request;

class Process
{
    /** Rules to process */
    private $rules = [];

    /** Matched rule */
    private $matched_rule = [];

    public function __construct(array $rules) 
    {
        $this->rules = $rules;
    }

    /**
     * Process every rule against a Request
     */
    public function process($request)
    {
        $default_target = [];
        $force_routes = false;
        $empty_route = false;

        $url = $request->getRequestTarget();

        // Si la ruta es vacía, devolvemos la raiz, o Welcome.
        if (!trim($url, '/')) {
            $empty_route = true;
        }

        $route = false;
        foreach ($this->rules as $rule) {
            // Si es una propiedad, simplemente la extraemos
            if ($rule->type == 'property') {
                $vars = $rule->data;
                extract ($vars, EXTR_IF_EXISTS);
                continue;
            }

            // Esto no parece bien... si la ruta está vacía, ni si quiera 
            // deberíamos ejecutar el bucle. Pero tenemos que hacerlo
            // por las propiedades...
            if ($empty_route) {
                continue;
            }

            $route = $rule->match($request);

            if ($route) {
                $this->matched_rule = $rule;
                return $route;
            }

        }
        // No encontramos rutas. Si no las forzamos, sugerimos una ruta
        // usando las partes de la URL. También retornamos la ruta por defecto,
        // si hay, de lo contrario, retornamos la app 'welcome'

        $route = false;
        if ($empty_route) {
            if ($default_target) {
                $route['target'] = $default_target;
            } elseif (!$force_routes) {
                $route['target'] = ['welcome', 'default'];
            }
        }

        // Si no forzamos las rutas, sugerimos un target alterno
        if (!$force_routes && !$empty_route) {
            $parts = explode('/', $request->getRequestTarget());

            $app = array_shift($parts);
            $controller = array_shift($parts);

            if (is_null($controller)) {
                $controller = 'default';
            }

            $route['try_default_controller'] = true;
            $route['alt_target'] = [$app, $controller];
            $route['args'] = $parts;
        }
        $this->matched_rule = $route;
        return $route;
    }

    /**
     * Returns the matched rule, if any
     */
    public function getMatchedRule()
    {
        return $this->matched_rule;
    }
}