<?php
$messages = Vendimia::$session->messages;

if ($messages) {
    $html_messages = '';
    foreach ($messages as $message) {
        switch ($message['type']) {
        case 'message':
            $ico = 'message'; break;
        case 'warning':
            $ico = 'warning'; break;
        case 'error':
            $ico = 'error'; break;
        default:
            $ico = 'unknow'; break;
        }

        $html_messages .= '<div class="vendimia_message ' . $ico . '">' . $message['message'] . "</div>\n";
    }

    echo Vendimia\Html\Tag::div(['class'=>'vendimia_message_container'], $html_messages)
        ->noEscapeContent();

    // Clear the messages from the session
    Vendimia::$session->messages = [];
}