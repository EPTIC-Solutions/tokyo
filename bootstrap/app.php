<?php

use DI\ContainerBuilder;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tokyo\Tokyo;

define('TOKYO_START', microtime(true));

if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
} elseif (file_exists(__DIR__.'/../../../autoload.php')) {
    require_once __DIR__.'/../../../autoload.php';
} else {
    require_once getenv('HOME').'/.composer/vendor/autoload.php';
}

$containerBuilder = new ContainerBuilder();
$eventDispatcher = new EventDispatcher();
$containerBuilder->addDefinitions([
    'config' => function () {
        return [
            'app' => require __DIR__.'/../config/app.php',
        ];
    },
    'eventDispatcher' => fn () => $eventDispatcher,
    \Symfony\Component\Process\ExecutableFinder::class => new \Symfony\Component\Process\ExecutableFinder(),
]);

$eventDispatcher->addListener(
    ConsoleEvents::COMMAND,
    function (ConsoleCommandEvent $event) {
        reader($event->getInput());
        writer($event->getOutput());
    }
);

$container = $containerBuilder->build();

Tokyo::setContainer($container);

/** @var Silly\Application */
$app = require __DIR__.'/../src/tokyo.php';

$container->set('app', $app);

$app->useContainer($container, injectByTypeHint: true);
$app->setDispatcher($eventDispatcher);

$app->run();
