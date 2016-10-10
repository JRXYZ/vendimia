<?php
use Vendimia\Cli;
use Vendimia\Console;

bin::help('Creates a new controller for an existent app', 
    '[for] app_name controller_name [--without-view]',
    [
        'for' => [
            'optional' => true,
            'description' => 'Syntactic sugar word.'
        ],
        'app_name' => "Name of the app where the controller will be created.",
        'controller_name' => 'New controller name.',
        '--without-view' => [
            'optional' => true,
            'description' => "Don't create a default view with the same name.",
        ],
    ]
);
// Creamos un controlador
Cli\createController (bin::$project_path, bin::$module->app, bin::$module->element);

// Creamos una vista, a menos que no querramos
if ( !isset ( bin::$args['without-view'] ) ) {
    $APPLICATION = bin::$module->app;
    $ELEMENT_NAME = bin::$module->element;

    $content = <<<EOF
<!-- Delete this and the lines below, and write your own view. -->

<h1>Hi! I'm the <em>$ELEMENT_NAME</em> controller from <em>$APPLICATION</em> application!</h1>
<p>Now, edit the view file <strong>apps/$APPLICATION/views/$ELEMENT_NAME.php</strong> for 
changing this text, or the file <strong>apps/$APPLICATION/controllers/$ELEMENT_NAME.php</strong>
for changing the controller behavior.</p>

EOF;

    Cli\createView(bin::$project_path, bin::$module->app, bin::$module->element , $content);

}
