<?php

namespace Tokyo;

use DI\Container;
use Tokyo\Contracts\PackageManager;
use Tokyo\Contracts\ServiceManager;
use Tokyo\PackageManagers\Apt;
use Tokyo\ServiceManagers\LinuxService;
use Tokyo\ServiceManagers\Systemd;

class Tokyo
{
    static public Container $container;

    public function __construct(private readonly System $system)
    {
        //
    }

    public function setup(): void
    {
        if (false === $this->system->isSupportedOperatingSystem()) {
            error("Tokyo is not supported on this operating system.");
            exit(1);
        }

        $this->setupManagers();
    }

    private function setupManagers(): void
    {
        container()->set(PackageManager::class, self::getAvailablePackageManager());
        container()->set(ServiceManager::class, self::getAvailableServiceManager());
    }

    /**
     * Determine the first available package manager
     */
    private function getAvailablePackageManager(): PackageManager
    {
        return collect([
            // Brew::class,
            Apt::class,
        ])
            ->map(fn (string $pm) => resolve($pm))
            ->first(static function (PackageManager $pm) {
                return $pm->isAvailable();
            }, static function () {
                error('Could not find compatible package manager.');
                exit(1);
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
                error('Could not find compatible service manager.');
                exit(1);
            });
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
