<?php

use DI\ContainerBuilder;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tokyo\Tokyo;

$containerBuilder = new ContainerBuilder();
$eventDispatcher = new EventDispatcher();
$containerBuilder->addDefinitions([
    'config' => function () {
        return [
            'app' => require __DIR__ . '/../config/app.php',
        ];
    },
    'eventDispatcher' => fn () => $eventDispatcher
]);

$eventDispatcher->addListener(
    ConsoleEvents::COMMAND,
    function (ConsoleCommandEvent $event) {
        writer($event->getOutput());
    }
);

$container = $containerBuilder->build();

Tokyo::setContainer($container);

/** @var Silly\Application */
$app = require __DIR__ . '/../src/tokyo.php';

$container->set('app', $app);

$app->useContainer($container, injectByTypeHint: true);
$app->setDispatcher($eventDispatcher);

return $app;
