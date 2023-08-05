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

    public function start(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            task("[$service] service is being started", function () use ($service) {
                // Stop service is started as user and not root
                $this->cli->run(['brew', 'services', 'stop', $service]);

                [, $errorCode] = $this->cli->run(['sudo', 'brew', 'services', 'start', $service]);

                if ($errorCode !== 0) {
                    error("[$service] Could not start service");
                }
            });
        }
    }

    public function stop(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            task("[$service] service is being stopped", function () use ($service) {
                // Stop service as user if accidentally started as not root
                $this->cli->run(['brew', 'services', 'stop', $service]);

                [, $errorCode] = $this->cli->run(['sudo', 'brew', 'services', 'stop', $service]);

                if ($errorCode !== 0) {
                    error("[$service] Could not stop service");
                }
            });
        }
    }

    public function restart(array|string $services): void
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
