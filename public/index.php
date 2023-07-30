<?php

$basePath = __DIR__;
require '../vendor/autoload.php';
require_once $basePath .'/../src/database.php';
require_once $basePath .'/../src/Functions/functions.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
// use App\Middleware\AuthMiddleware;

$app = AppFactory::create();

// Add error handling middleware
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();
// $app->add(new AuthMiddleware());

// Include routes from routes.php
$routes = require_once $basePath .'/../src/routes.php';
$routes($app);

$app->run();
