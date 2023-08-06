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
            $error = '';
            $errorCode = task("[$service] service is being started", function () use ($service, &$error) {
                [$error, $errorCode] = $this->cli->run(['systemctl', 'start', $service]);

                return $errorCode;
            });

            if (0 !== $errorCode) {
                error("[$service] Could not start service");

                if (isDebug()) {
                    warning($error);
                }

                exit(1);
            }
        }
    }

    public function stop(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $error = '';
            $errorCode = task("[$service] service is being stopped", function () use ($service, &$error) {
                [$error, $errorCode] = $this->cli->run(['systemctl', 'stop', $service]);

                return $errorCode;
            });

            if (0 !== $errorCode) {
                error("[$service] Could not stop service");

                if (isDebug()) {
                    warning($error);
                }

                exit(1);
            }
        }
    }

    public function restart(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $error = '';
            $errorCode = task("[$service] service is being restarted", function () use ($service, &$error) {
                [$error, $errorCode] = $this->cli->run(['systemctl', 'restart', $service]);

                return $errorCode;
            });

            if (0 !== $errorCode) {
                error("[$service] Could not restart service");

                if (isDebug()) {
                    warning($error);
                }

                exit(1);
            }
        }
    }

    public function enable(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $error = '';
            $errorCode = task("[$service] service is now enabled", function () use ($service, &$error) {
                [$error, $errorCode] = $this->cli->run(['systemctl', 'enable', $service]);

                return $errorCode;
            });

            if (0 !== $errorCode) {
                error("[$service] Could not enable service");

                if (isDebug()) {
                    warning($error);
                }

                exit(1);
            }
        }
    }

    public function disable(array|string $services): void
    {
        $services = is_array($services) ? $services : func_get_args();

        foreach ($services as $service) {
            $error = '';
            $errorCode = task("[$service] service is now disabled", function () use ($service, &$error) {
                [$error, $errorCode] = $this->cli->run(['systemctl', 'disable', $service]);

                return $errorCode;
            });

            if (0 !== $errorCode) {
                error("[$service] Could not disable service");

                if (isDebug()) {
                    warning($error);
                }

                exit(1);
            }
        }
    }

    public function status(array|string $services): bool
    {
        return true;
    }

    public function isAvailable(): bool
    {
        return null !== resolve(ExecutableFinder::class)->find('systemctl');
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
