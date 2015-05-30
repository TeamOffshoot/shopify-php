<?php

namespace Shopify\Redirector;

class HeaderRedirector implements \Shopify\Redirector
{

    public function redirect($uri)
    {
        self::go($uri);
    }

    public static function go($uri)
    {
        header('Location: ' . $uri);
        exit(0);

    }



}
