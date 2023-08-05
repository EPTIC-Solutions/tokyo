<?php

namespace Tokyo\ServiceManagers;

use Illuminate\Support\Collection;
use Symfony\Component\Process\ExecutableFinder;
use Tokyo\CommandLine;
use Tokyo\Contracts\ServiceManager;

class Systemd implements ServiceManager
{
    public function __construct(private readonly CommandLine $cli)
    {
        //
    }

    public function start(array|string $services): bool
    {
    }

    public function stop(array|string $services): bool
    {
    }

    public function restart(array|string $services): bool
    {
    }

    public function status(array|string $services): bool
    {
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
