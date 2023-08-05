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

        $pm->ensureInstalled('nginx');
        $pm->ensureInstalled('dnsmasq');

        $sm->start('nginx');

        $conf->install();
    });
} else {
    $app->command('uninstall', function (CommandLine $cli, Configuration $conf, PackageManager $pm) {
        $cli->promptSudoPassword();

        output("Removing Tokyo... 🥺");

        $conf->uninstall();
        $pm->uninstall('nginx');
        $pm->uninstall('dnsmasq');

        output('Tokyo has been removed');
    });
}

return $app;
