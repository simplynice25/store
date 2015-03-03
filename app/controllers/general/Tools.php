<?php

namespace general;

use Symfony\Component\HttpFoundation\Request;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

class Tools
{
    public static function uuid($string) {
        $uuid5 = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'ostore_'.$string);
        return $uuid5;
    }

    public static function redirect($app, $url, $params = null) {
        $to = $app['url_generator']->generate($url);
        return $app->redirect($to);
    }

    public static function findOneBy($app, $model, $criteria = null, $sort = null) {
        if (is_null($criteria)) {
            $criteria = array('view_status' => 5);
        }

        $object = $app['orm.em']->getRepository('models' . $model)->findOneBy($criteria, $sort);
        return $object;
    }
}