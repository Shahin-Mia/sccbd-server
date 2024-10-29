<?php

declare(strict_types=1);


use App\Middleware\AddJsonResponseHeader;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Handlers\Strategies\RequestResponseArgs;

define("APP_ROOT", dirname(__DIR__));
// define("IMAGE_FOLDER", APP_ROOT . "/../public/images/"); // production server image path
define("IMAGE_FOLDER", APP_ROOT . "/../sccbd/public/images/"); // development server image path

require APP_ROOT . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->load();

$builder = new ContainerBuilder();

$container = $builder->addDefinitions(APP_ROOT . "/config/definition.php")
    ->build();

AppFactory::setContainer($container);

$app = AppFactory::create();

$collector = $app->getRouteCollector();

$collector->setDefaultInvocationStrategy(new RequestResponseArgs());

$app->addBodyParsingMiddleware();

$error_middleware = $app->addErrorMiddleware(true, true, true);

$error_handler = $error_middleware->getDefaultErrorHandler();

$error_handler->forceContentType("application/json");

$app->add(new AddJsonResponseHeader);

$app->setBasePath("/slim");

require APP_ROOT . "/config/routes.php";

$app->run();
