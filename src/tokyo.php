<?php

use Silly\Application;
use Tokyo\CommandLine;
use Tokyo\Configuration;
use Tokyo\Contracts\PackageManager;
use Tokyo\Contracts\ServiceManager;
use Tokyo\Tokyo;


$app = new Application(config('app.name'), config('app.version'));

resolve(Tokyo::class)->setup();

if (!isInstalled()) {
    $app->command('install', function (CommandLine $cli, Configuration $conf, PackageManager $pm, ServiceManager $sm) {
        $cli->promptSudoPassword();

        output('Installing Tokyo...');

        // Install all packages
        $pm->ensureInstalled('nginx');
        $pm->ensureInstalled('dnsmasq');

        // Start all services
        $sm->start('nginx');

        // Install all configuration
        $conf->install();
    });
} else {
    $app->command('uninstall', function (CommandLine $cli, Configuration $conf, PackageManager $pm, ServiceManager $sm) {
        $cli->promptSudoPassword();

        output("Removing Tokyo... 🥺");

        // Stop all services
        $sm->stop('nginx');

        // Uninstall all packages
        $pm->uninstall('nginx');
        $pm->uninstall('dnsmasq');

        // Remove all configuration
        $conf->uninstall();

        output('Tokyo has been removed');
    });
}

return $app;
