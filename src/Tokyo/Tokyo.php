<?php

namespace Tokyo;

use DI\Container;
use Tokyo\Contracts\PackageManager;
use Tokyo\Contracts\ServiceManager;

class Tokyo
{
    public static Container $container;

    public function __construct(private readonly System $system)
    {
        //
    }

    public function setup(): void
    {
        if (false === $this->system->isSupportedOperatingSystem()) {
            error('Tokyo is not supported on this operating system.');
            exit(1);
        }

        $this->setupManagers();
    }

    private function setupManagers(): void
    {
        container()->set(PackageManager::class, $this->getAvailablePackageManager());
        container()->set(ServiceManager::class, $this->getAvailableServiceManager());
    }

    /**
     * Determine the first available package manager.
     */
    private function getAvailablePackageManager(): PackageManager
    {
        return collect([
            \Tokyo\PackageManagers\Brew::class,
            \Tokyo\PackageManagers\Apt::class,
        ])
            ->map(fn (string $pm) => resolve($pm))
            ->filter(fn (PackageManager $pm) => in_array($this->system->getOperatingSystem(), $pm->supportedOperatingSystems()))
            ->first(
                fn (PackageManager $pm) => $pm->isAvailable(),
                function () {
                    error('Could not find compatible package manager.');

                    exit(1);
                }
            );
    }

    /**
     * Determine the first available service manager.
     */
    private function getAvailableServiceManager(): ServiceManager
    {
        return collect([
            \Tokyo\ServiceManagers\Brew::class,
            \Tokyo\ServiceManagers\LinuxService::class,
            \Tokyo\ServiceManagers\Systemd::class,
        ])
            ->map(fn (string $sm) => resolve($sm))
            ->filter(fn (ServiceManager $sm) => in_array($this->system->getOperatingSystem(), $sm->supportedOperatingSystems()))
            ->first(static function (ServiceManager $sm) {
                return $sm->isAvailable();
            }, static function () {
                error('Could not find compatible service manager.');
                exit(1);
            });
    }

    public static function setContainer(Container $container): void
    {
        static::$container = $container;
    }

    public static function getContainer(): Container
    {
        return static::$container;
    }
}
