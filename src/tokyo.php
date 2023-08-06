<?php

use Psy\Shell;
use Silly\Application;
use Tokyo\CommandLine;
use Tokyo\Configuration;
use Tokyo\Contracts\PackageManager;
use Tokyo\Contracts\ServiceManager;
use Tokyo\Services\Nginx;
use Tokyo\Services\Php;
use Tokyo\Tokyo;

$app = new Application(config('app.name'), config('app.version'));

resolve(Tokyo::class)->setup();

if (!isInstalled()) {
    $app->command('install', function (CommandLine $cli, Configuration $conf, PackageManager $pm, ServiceManager $sm) {
        $cli->ensureSudo();

        output("Installing Tokyo...\n");

        // Install all configuration
        $conf->install();

        resolve(Nginx::class)->install();
        resolve(Php::class)->install();

        // Install all packages
        // $pm->ensureInstalled('dnsmasq');

        output("\nTokyo is now installed");
    });
} else {
    $app->command('uninstall', function (CommandLine $cli, Configuration $conf, PackageManager $pm, ServiceManager $sm) {
        $answer = ask('Are you sure you want to uninstall Tokyo? (yes/no)', 'no');
        if (!str_starts_with(strtolower($answer), 'y')) {
            return;
        }

        $cli->ensureSudo();

        output("Removing Tokyo... 🥺\n");

        resolve(Nginx::class)->uninstall();
        resolve(Php::class)->uninstall();

        $conf->uninstall();

        output("\nTokyo has been removed");
    });
}

$app->command('sudo-cmds', function () {
    $sudoCommands = [
        'install',
        'uninstall',
    ];

    writer()->write(implode(' ', $sudoCommands));
})->setHidden(true);

if (isDebug()) {
    $app->command('tinker', function () {
        $shell = new Shell();

        $shell->run();
    })->setDescription('Interact with the Tokyo application.');
}

return $app;
