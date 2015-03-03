<?php

use Silex\Application;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use DerAlex\Silex\YamlConfigServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

require_once __DIR__ . "/../vendor/autoload.php";

$app = new Application();
require_once __DIR__ . "/settings.php";

$app["debug"] = $developmentMode;

$app->register(new FormServiceProvider());
$app->register(new UrlGeneratorServiceProvider());
$app->register(new TwigServiceProvider(), $configurations["twig_config"]);

/**
 * Translation files
 **/
$app->register(new TranslationServiceProvider(), $configurations["translation_config"]);
$app["translator"] = $app->share($app->extend("translator", function($translator, $app) {
    $translator->addLoader("yaml", new YamlFileLoader());
    $translator->addResource("yaml", CONFIG_FILES . "languages/messages.yml", "en");
    $translator->addResource("yaml", CONFIG_FILES . "languages/warnings.yml", "en");
    $translator->addResource("yaml", CONFIG_FILES . "languages/errors.yml", "en");

    return $translator;
}));

$app["locale"] = "en";

/**
 * Database configuration
 **/
$models = Setup::createAnnotationMetadataConfiguration([__DIR__ . "/models"], $developmentMode);
$app->register(new YamlConfigServiceProvider($configurations["database_config"]));
$app->register(new DoctrineServiceProvider(), [
    "db.orm.cache" => LOG_FILES,
    "db.options" => $app["config"]["database"]
]);

$app["orm.em"] = EntityManager::create($app["db"], $models);

/**
 * Log errors
 **/
$app->register(new MonologServiceProvider(), $configurations["development_log_config"]);

/**
 * Session and Security
 **/
$app->register(new SessionServiceProvider());
$app->register(new SecurityServiceProvider(), [
    "security.firewalls" => [
        "registered" => [
            "pattern"   => "^.*$",
            "anonymous" => true,
            "form"      => ["login_path" => "/login", "check_path" => "/login_check"],
            "logout"    => ["logout_path" => "/logout"],
            "users"     => $app->share(function() use($app) {
                return new libraries\UserProvider($app);
            })
        ]
    ],
    "security.access_rules" => [
        ["^/dashboard/", "ROLE_ADMIN"],
    ],
    "security.role_hierarchy" => [
        "ROLE_ADMIN" => ["ROLE_USER", "ROLE_MODERATOR"],
        "ROLE_MODERATOR" => ["ROLE_USER"],
    ],
    "security.encoder.digest" => $app->share(function($app) {
       return new MessageDigestPasswordEncoder("sha1", false, 1);
    })
]);

/**
 * Mail confugirations
 **/
$app->register(new SwiftmailerServiceProvider());
$app["swiftmailer.options"] = $configurations["email_config"];

/**
 * Social Networks configurations
 **/
$app["social_networks"] = $configurations["social_networks"];

/**
 * Routes
 **/

$before = function() use($app) {
};

$app->before($before);

$after = function() use($app) {
    //$password = $app['security.encoder.digest']->encodePassword('password', '');
    //echo $password;
};

$app->after($after);


$app->mount("/", front\FrontRoutes::routes($app));
$app->mount("/connect", general\Connect::routes($app));
$app->mount("/dashboard", dashboard\DashboardRoutes::routes($app));

/* Login & Registration */
$app->match("/login", "general\Connect::login")->bind("login");

/* End of bootstrap.php file */