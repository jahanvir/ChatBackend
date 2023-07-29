<?php

require '../vendor/autoload.php';
require '../src/database.php';
require '../src/Functions/functions.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
// use App\Middleware\AuthMiddleware;

$app = AppFactory::create();

// Add error handling middleware
$app->addErrorMiddleware(true, true, true);
// $app->add(new AuthMiddleware());

// Include routes from routes.php
$routes = require '../src/routes.php';
$routes($app);

$app->run();
