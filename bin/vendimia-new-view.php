<?php
use Vendimia\Cli;

bin::help("Creates an empty view file.",
    "[for] app_name view_name", 
    [
        'for' => [
            'optional' => true,
            'description' => 'Syntactic sugar word.'
        ],    
        'app_name' => 'Name of the app where the new view will be created',
        'model_name' => 'New view name.'
    ]
);

// Debe existir la app

if ( ! bin::$module->app_exists ) {
    Console::fail ( "Application '{:app " . bin::$new__app . "}' doesn't exists. " );

}
// Creamos una vista. Simple.
Cli\createView (
    bin::$project_path, 
    bin::$module->app, 
    bin::$module->element
);