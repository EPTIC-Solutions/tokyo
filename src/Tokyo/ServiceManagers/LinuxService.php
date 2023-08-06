<?php

namespace Tokyo\ServiceManagers;

use Illuminate\Support\Collection;
use Symfony\Component\Process\ExecutableFinder;
use Tokyo\CommandLine;
use Tokyo\Contracts\ServiceManager;
use Tokyo\OperatingSystem;

class LinuxService implements ServiceManager
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
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $errorCode = task("[$service] service is being started", function () use ($service) {
                [, $errorCode] = $this->cli->run(['sudo', 'service', $service, 'start']);

                return $errorCode;
            });

            if ($errorCode !== 0) {
                error("[$service] Could not start service");

                exit(1);
            }
        };
    }

    public function stop(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $errorCode = task("[$service] service is being stopped", function () use ($service) {
                [, $errorCode] = $this->cli->run(['sudo', 'service', $service, 'stop']);

                return $errorCode;
            });

            if ($errorCode !== 0) {
                error("[$service] Could not stop service");

                exit(1);
            }
        };
    }

    public function restart(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $errorCode = task("[$service] service is being restarted", function () use ($service) {
                [, $errorCode] = $this->cli->run(['sudo', 'service', $service, 'restart']);

                return $errorCode;
            });

            if ($errorCode !== 0) {
                error("[$service] Could not restart service");

                exit(1);
            }
        };
    }

    public function enable(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $errorCode = task("[$service] service is now enabled", function () use ($service) {
                [, $errorCode] = $this->cli->run(['sudo', 'update-rc.d', $service, 'enable']);

                return $errorCode;
            });

            if ($errorCode !== 0) {
                error("[$service] Could not enable service");

                exit(1);
            }
        };
    }

    public function disable(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $errorCode = task("[$service] service is now disabled", function () use ($service) {
                [, $errorCode] = $this->cli->run(['sudo', 'update-rc.d', $service, 'disable']);

                return $errorCode;
            });

            if ($errorCode !== 0) {
                error("[$service] Could not disable service");

                exit(1);
            }
        };
    }

    public function status(array|string $services): bool
    {
        return true;
    }

    public function isAvailable(): bool
    {
        return resolve(ExecutableFinder::class)->find('service') !== null;
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
