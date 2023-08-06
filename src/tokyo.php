<?php

use Psy\Shell;
use Silly\Application;
use Tokyo\CommandLine;
use Tokyo\Configuration;
use Tokyo\Contracts\PackageManager;
use Tokyo\Contracts\ServiceManager;
use Tokyo\Services\Nginx;
use Tokyo\Services\Php;
use Tokyo\Services\DnsMasq;
use Tokyo\Site;
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
        resolve(DnsMasq::class)->install();

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
        resolve(DnsMasq::class)->uninstall();

        $conf->uninstall();

        output("\nTokyo has been removed");
    });

    $app->command('park [path]', function (?string $path, Site $site) {
        $path = $path ?? getcwd();
        $site->park($path);

        info("The [{$path}] directory is now parked for Tokyo");
    })->setDescription('Register the current working (or specified) directory to Tokyo');

    $app->command('unpark [path]', function (?string $path, Site $site) {
        $path = $path ?? getcwd();
        $site->unpark($path);

        info("The [{$path}] is no longer parked for Tokyo");
    })->setDescription('Register the current working (or specified) directory to Tokyo');

    $app->command('link [name]', function (?string $name, Site $site) {
        $name = $name ?? basename(getcwd());
        $site->link(getcwd(), $name);

        info('Site [' . $name . '] has been linked to Tokyo');
    })->setDescription('Link the current working directory to Tokyo');

    $app->command('unlink [name]', function (?string $name, Site $site) {
        $name = $name ?? basename(getcwd());
        $site->unlink($name);

        info('Site [' . $name . '] has been unlinked from Tokyo');
    })->setDescription('Unlink the current working directory form Tokyo');
}

$app->command('sudo-cmds', function () {
    $sudoCommands = [
        'install',
        'uninstall',
        'reinstall'
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
