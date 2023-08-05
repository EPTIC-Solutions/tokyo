<?php

namespace Tokyo\ServiceManagers;

use Illuminate\Support\Collection;
use Tokyo\CommandLine;
use Tokyo\Contracts\ServiceManager;

class Brew implements ServiceManager
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
        [, $errorCode] = $this->cli->run(['which', 'brew']);

        return $errorCode === 0;
    }

    public function getRunningServices(): Collection
    {
    }

    public function getAllRunningServices(): Collection
    {
    }
}
