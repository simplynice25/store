<?php

namespace general;

use general\Tools;
use Silex\Application;
use libraries\UserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class Connect
{
    public static function routes($app) {
        $route = $app["controllers_factory"];
        $route->match("/login-auth", "general\Connect::loginAuth")->bind("login-auth");

        return $route;
    }

    public function login(Application $app, Request $req) {

        if ($app["security"]->isGranted("ROLE_ADMIN")) {
            return Tools::redirect($app, "homepage");
        } else if ($app["security"]->isGranted("ROLE_USER")) {
            return Tools::redirect($app, "homepage");
        }

        $view = [
            "title" => "Login",
            "error" => $app["security.last_error"]($req),
            "last_username" => $app["session"]->get("_security.last_username"),
        ];

        return $app["twig"]->render("general/connect/login.twig", $view);
    }

    public function loginAuth(Application $app, Request $req) {
        $id = $req->get("id");
        $type = $req->get("type");
        $firstName = $req->get("firstName");
        $lastName = $req->get("lastName");
        $email = $req->get("email");
        $profile = $req->get("profile");

        $register = Tools::findOneBy($app, "\User", array("email" => $email));
        if (empty($register)) {
            /* Set id as password since ID can't be altered and email can change */
            $password = $app['security.encoder.digest']->encodePassword($id, '');

            $register = new \models\User;
            $register->setEmail($email);
            $register->setPassword($password);
            $register->setAccountType($type);
            $register->setRoles("ROLE_USER");
            $register->setViewStatus(5);
            $register->setCreatedAt("now");
            $register->setModifiedAt("now");
            $app['orm.em']->persist($register);
            $app['orm.em']->flush();

            $updateUser = Tools::findOneBy($app, "\User", array("email" => $email));
            $updateUser->setUuid(Tools::uuid($register->getId()));
            $updateUser->setModifiedAt("now");
            $app['orm.em']->persist($updateUser);
            $app['orm.em']->flush();
        }

        $userProvider = new UserProvider($app);
        $user = $userProvider->loadUserByUsername($email);
        $app['security']->setToken(new UsernamePasswordToken($user, $user->getPassword(), 'default', $user->getRoles()));

        return json_encode(array("message" => "success"));
    }
}