<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

require_once dirname(__DIR__) . '/src/Config/middlewares.php';

require_once dirname(__DIR__) . '/src/Config/routes.php';

$app->run();