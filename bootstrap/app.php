<?php

use Illuminate\Container\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

$container = Container::setInstance(new Container());
$eventDispatcher = new EventDispatcher();

$container->bind('config', function () {
    return [
        'app' => require_once __DIR__ . '/../config/app.php',
    ];
});

$app = require_once __DIR__ . '/../src/tokyo.php';

$app->useContainer($container, injectByTypeHint: true);
$app->setDispatcher($eventDispatcher);

return $app;
