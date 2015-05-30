<?php

spl_autoload_register(function ($class) {
    if (
           file_exists($fn = __DIR__.'/../'.str_replace('\\','/',$class).'.php')
    ) {
        require $fn;
    }
});
