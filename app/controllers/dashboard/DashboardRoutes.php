<?php

namespace dashboard;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class DashboardRoutes
{
    public static function routes($app) {
        $route = $app['controllers_factory'];
        $route->match('/', 'dashboard\DashboardRoutes::index')->bind("dashboard");

        return $route;
    }

    public function index(Application $app) {
        return "Welcome to Dashboard!";
    }
}