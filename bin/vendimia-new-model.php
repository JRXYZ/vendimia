<?php
use Vendimia\Cli;

if (!defined ( "VENDIMIA_BASE_INCLUDED")) {
    require 'vendimia/bin/base.php';    
}
bin::help("Creates a model definition.", 
    "[for] app_name model_name", 
    [
        'for' => [
            'optional' => true,
            'description' => 'Syntactic sugar word.'
        ],    
        'app_name' => 'Name of the app where the new model will be created',
        'model_name' => 'New model name. It should match a tabledef.'
    ]
);


// Debe existir el proyecto
if (!bin::$project_exists) {
    Console::fail ( "Vendimia project not found." );
}

$namespace = bin::$module->app;
$classname = bin::$module->element;

Cli\createModel(bin::$project_path, $namespace, $classname);