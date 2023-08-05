<?php

namespace Tokyo;

use DI\Container;
use DomainException;
use Tokyo\Contracts\PackageManager;
use Tokyo\Contracts\ServiceManager;
use Tokyo\PackageManagers\Apt;
use Tokyo\PackageManagers\Brew;
use Tokyo\ServiceManagers\Brew as ServiceManagersBrew;
use Tokyo\ServiceManagers\LinuxService;
use Tokyo\ServiceManagers\Systemd;

class Tokyo
{
    static public Container $container;

    static public function setup()
    {
        self::setupPackageMangers();
        self::setupServiceManagers();
    }

    static private function setupPackageMangers()
    {
        container()->set(PackageManager::class, self::getAvailablePackageManager());
        container()->set(ServiceManager::class, self::getAvailableServiceManager());
    }

    /**
     * Determine the first available package manager
     */
    static private function getAvailablePackageManager(): PackageManager
    {
        return collect([
            // Brew::class,
            Apt::class,
        ])
            ->map(fn (string $pm) => resolve($pm))
            ->first(static function (PackageManager $pm) {
                return $pm->isAvailable();
            }, static function () {
                throw new DomainException("Could not find compatible package manager.");
            });
    }

    /**
     * Determine the first available service manager
     */
    static private function getAvailableServiceManager(): ServiceManager
    {
        return collect([
            // ServiceManagersBrew::class,
            LinuxService::class,
            Systemd::class,
        ])
            ->map(fn (string $sm) => resolve($sm))
            ->first(static function (ServiceManager $sm) {
                return $sm->isAvailable();
            }, static function () {
                throw new DomainException("Could not find compatible service manager.");
            });
    }

    static private function setupServiceManagers()
    {
    }

    static public function setContainer(Container $container): void
    {
        static::$container = $container;
    }

    static public function getContainer(): Container
    {
        return static::$container;
    }
}
