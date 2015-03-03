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
        $route->match("/validate-account", "general\Connect::validateAccount");
        $route->match("/validate-email", "general\Connect::validateEmail");

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

    public function validateAccount(Application $app, Request $req, $data = null) {
        $return["message"] = "invalid_account";
        $id = ( ! is_null($data)) ? $data[0] : $req->get("id");
        $type = ( ! is_null($data)) ? $data[1] : $req->get("type");
        $password = $app['security.encoder.digest']->encodePassword($id, '');

        $account = Tools::findOneBy($app, "\User", array("password" => $password, "account_type" => $type));
        if ( ! empty($account)) {
            $email = $account->getEmail();
            if ( ! empty($email)) {
                $return["message"] = $email;
            }
        }

        return json_encode($return);
    }

    public function validateEmail(Application $app, Request $req) {
        $email = $req->get("email");
        $return["message"] = "invalid_email";
        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json_encode($return);
        } else {
            $check = Tools::findOneBy($app, "\User", array("email" => $email));
            if ( ! empty($check)) {
                $return["message"] = "email_in_use";
            } else {
                $return["message"] = "email_is_available";
            }
        }

        return json_encode($return);
    }

    public function loginAuth(Application $app, Request $req) {
        $id = $req->get("id");
        $type = $req->get("type");
        $firstName = $req->get("firstName");
        $lastName = $req->get("lastName");
        $fullName = trim($firstName . " " . $lastName);
        $email = $req->get("email");
        $profile = $req->get("profile");
        $msg = ["success"];

        $validate = self::validateAccount($app, $req, $data = [$id, $type]);
        $decoded = json_decode($validate);
        if ($decoded->message != "invalid_account" && $email != $decoded->message) {
            $email = $decoded->message;
        }

        $register = Tools::findOneBy($app, "\User", array("email" => $email));
        if (empty($register)) {
            /* Set id as password since ID can't be altered and email can change */
            $password = $app['security.encoder.digest']->encodePassword($id, '');

            /* Register the user */
            $register = new \models\User;
            $register->setEmail($email);
            $register->setPassword($password);
            $register->setAccountType($type);
            $register->setRoles("ROLE_USER");
            $register->setViewStatus(5);
            $register->setCreatedAt("now");
            $app['orm.em']->persist($register);
            $app['orm.em']->flush();

            /* Update the user UUID */
            $updateUser = Tools::findOneBy($app, "\User", array("email" => $email));
            $updateUser->setUuid(Tools::uuid($register->getId()));
            $updateUser->setModifiedAt("now");
            $app['orm.em']->persist($updateUser);
            $app['orm.em']->flush();

            /* Create user profile */
            $profile = new \models\UserProfile;
            $profile->setUser($register);
            $profile->setFirstname($firstName);
            $profile->setLastname($lastName);
            $profile->setFullname($fullName);
            $profile->setViewStatus(5);
            $profile->setCreatedAt("now");
            $app['orm.em']->persist($profile);
            $app['orm.em']->flush();
        }

        $userProvider = new UserProvider($app);
        $user = $userProvider->loadUserByUsername($email);
        $app['security']->setToken(new UsernamePasswordToken($user, $user->getPassword(), 'default', $user->getRoles()));

        return json_encode(array("message" => $msg[0]));
    }
}