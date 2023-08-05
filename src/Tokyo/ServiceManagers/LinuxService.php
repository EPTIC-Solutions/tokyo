<?php

namespace Tokyo\ServiceManagers;

use Illuminate\Support\Collection;
use Tokyo\CommandLine;
use Tokyo\Contracts\ServiceManager;

class LinuxService implements ServiceManager
{
    public function __construct(private readonly CommandLine $cli)
    {
        //
    }

    public function start(array|string $services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            task("[$service] service is being started", function () use ($service) {
                [, $errorCode] = $this->cli->run(['sudo', 'service', $service, 'start']);

                if ($errorCode !== 0) {
                    error("[$service] Could not start service");
                }
            });
        };
    }

    public function stop(array|string $services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            task("[$service] service is being stopped", function () use ($service) {
                [, $errorCode] = $this->cli->run(['sudo', 'service', $service, 'stop']);

                if ($errorCode !== 0) {
                    error("[$service] Could not stop service");
                }
            });
        };
    }

    public function restart(array|string $services)
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            task("[$service] service is being restarted", function () use ($service) {
                [, $errorCode] = $this->cli->run(['sudo', 'service', $service, 'restart']);

                if ($errorCode !== 0) {
                    error("[$service] Could not restart service");
                }
            });
        };
    }

    public function status(array|string $services): bool
    {
        return true;
    }

    public function isAvailable(): bool
    {
        [, $errorCode] = $this->cli->run(['which', 'service']);

        return $errorCode === 0;
    }

    public function getRunningServices(): Collection
    {
    }

    public function getAllRunningServices(): Collection
    {
    }
}
