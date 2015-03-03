<?php

namespace front;

use general\Tools;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class FrontRoutes
{
    public static function routes($app) {
        $route = $app["controllers_factory"];
        $route->match("/", "front\FrontRoutes::index")->bind("homepage");

        return $route;
    }

    public function index(Application $app) {
        return "Generate UUID: " . Tools::uuid(5);
    }
}