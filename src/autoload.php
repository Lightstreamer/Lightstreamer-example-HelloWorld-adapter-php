<?php

function __autoload($classname)
{
    $file = __DIR__ . "/" . str_replace("\\", "/", $classname) . ".php";
    if (is_file($file)) {
        require $file;
        return TRUE;
    } else {
        return FALSE;   
    }
}
