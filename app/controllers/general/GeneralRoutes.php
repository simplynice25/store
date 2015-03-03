<?php

namespace general;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class GeneralRoutes
{
    public static function routes(Application $app) {
        $route = $app['controllers_factory'];

        /* Tools.php routes */
        $route->match('/', 'general\Tools::uuid');

        return $route;
    }
}