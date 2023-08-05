<?php

use Silly\Application;
use Tokyo\Configuration;
use Tokyo\Contracts\PackageManager;
use Tokyo\Contracts\ServiceManager;
use Tokyo\Tokyo;


$app = new Application(config('app.name'), config('app.version'));

Tokyo::setup();

if (!isInstalled()) {
    $app->command('install', function (Configuration $conf, PackageManager $pm, ServiceManager $sm) {
        output('Installing Tokyo...');

        $pm->ensureInstalled('nginx');
        $pm->ensureInstalled('dnsmasq');

        $sm->start('nginx');

        $conf->install();
    });
} else {
    $app->command('uninstall', function (Configuration $conf, PackageManager $pm) {
        output("Removing Tokyo... 🥺");

        $conf->uninstall();
        $pm->uninstall('nginx');
        $pm->uninstall('dnsmasq');

        output('Tokyo has been removed');
    });
}

return $app;
