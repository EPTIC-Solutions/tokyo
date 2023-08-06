<?php

namespace Tokyo\ServiceManagers;

use Illuminate\Support\Collection;
use Symfony\Component\Process\ExecutableFinder;
use Tokyo\CommandLine;
use Tokyo\Contracts\ServiceManager;
use Tokyo\OperatingSystem;

class Brew implements ServiceManager
{
    public function __construct(private readonly CommandLine $cli)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function supportedOperatingSystems(): array
    {
        return [
            OperatingSystem::DARWIN,
        ];
    }

    public function start(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            task("[$service] service is being started", function () use ($service) {
                // Stop service is started as user and not root
                $this->cli->run(['brew', 'services', 'stop', $service]);

                $this->cli->run(['sudo', 'brew', 'services', 'run', $service]);
            });
        }
    }

    public function stop(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            task("[$service] service is being stopped", function () use ($service) {
                // Stop service as user if accidentally started as not root
                $this->cli->runAsUser(['brew', 'services', 'stop', $service]);

                $this->cli->run(['brew', 'services', 'kill', $service]);
            });
        }
    }

    public function restart(array|string $services): void
    {
    }

    public function enable(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            task("[$service] service is now enabled", function () use ($service) {
                // Stop service is started as user and not root
                $this->cli->runAsUser(['brew', 'services', 'stop', $service]);

                $this->cli->run(['brew', 'services', 'start', $service]);
            });
        }
    }

    public function disable(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            task("[$service] service is now disabled", function () use ($service) {
                // Stop service is started as user and not root
                $this->cli->runAsUser(['brew', 'services', 'stop', $service]);

                $this->cli->run(['brew', 'services', 'stop', $service]);
            });
        }
    }

    public function status(array|string $services): bool
    {
        return true;
    }

    public function isAvailable(): bool
    {
        return resolve(ExecutableFinder::class)->find('brew') !== null;
    }

    public function getRunningServices(): Collection
    {
        return collect();
    }

    public function getAllRunningServices(): Collection
    {
        return collect();
    }
}
