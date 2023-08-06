<?php

namespace Tokyo\ServiceManagers;

use Illuminate\Support\Collection;
use Symfony\Component\Process\ExecutableFinder;
use Tokyo\CommandLine;
use Tokyo\Contracts\ServiceManager;
use Tokyo\OperatingSystem;

class Systemd implements ServiceManager
{
    public function __construct(private readonly CommandLine $cli)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function supportedOperatingSystems(): array
    {
        return [
            OperatingSystem::LINUX,
        ];
    }

    public function start(array|string $services): void
    {
    }

    public function stop(array|string $services): void
    {
    }

    public function restart(array|string $services): void
    {
    }

    public function enable(array|string $services): void
    {
    }

    public function disable(array|string $services): void
    {
    }

    public function status(array|string $services): bool
    {
        return true;
    }

    public function isAvailable(): bool
    {
        return resolve(ExecutableFinder::class)->find('systemctl') !== null;
    }

    public function getRunningServices(): Collection
    {
    }

    public function getAllRunningServices(): Collection
    {
    }
}
