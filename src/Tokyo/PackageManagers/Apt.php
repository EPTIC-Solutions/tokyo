<?php

namespace Tokyo\PackageManagers;

use Illuminate\Support\Collection;
use Symfony\Component\Process\ExecutableFinder;
use Tokyo\CommandLine;
use Tokyo\Contracts\PackageManager;
use Tokyo\OperatingSystem;

class Apt implements PackageManager
{
    public function __construct(private readonly CommandLine $cli)
    {
    }

    public function supportedOperatingSystems(): array
    {
        return [
            OperatingSystem::LINUX,
        ];
    }

    public function packages(): Collection
    {
        [$packagesRaw, $errorCode] = $this->cli->run(['dpkg-query', '--list']);

        if (0 !== $errorCode) {
            return collect();
        }

        return collect(explode("\n", trim($packagesRaw)))
            ->filter(fn ($package) => strpos($package, 'ii') === 0)
            ->map(function ($package) {
                $package = explode(' ', preg_replace('/\s+/', ' ', $package));

                return [
                    'name' => $package[1],
                    'version' => $package[2],
                    'arch' => $package[3],
                ];
            });
    }

    public function installed(string $package): bool
    {
        return $this->packages()->where('name', $package)->count();
    }

    public function ensureInstalled(string $package): void
    {
        if (!$this->installed($package)) {
            $this->installOrFail($package);
        }
    }

    public function installOrFail(string $package): void
    {
        $errorCode = task("🍺 [$package] is being installed via Apt", function () use ($package) {
            [, $errorCode] = $this->cli->run(['apt', 'install', '-y', $package]);

            return $errorCode;
        });

        if (0 !== $errorCode) {
            error("Could not install [$package] via Apt");

            exit(1);
        }
    }

    public function uninstall(string $package): void
    {
        $errorCode = task("🍺 [$package] is being uninstalled via Apt", function () use ($package) {
            [, $errorCode] = $this->cli->run(['apt', 'purge', '-y', $package]);

            return $errorCode;
        });

        if (0 !== $errorCode) {
            error("Could not uninstall [$package] via Apt");

            exit(1);
        }
    }

    public function setup(): void
    {
    }

    public function isAvailable(): bool
    {
        return null !== resolve(ExecutableFinder::class)->find('apt');
    }

    public function supportedPhpVersions(): Collection
    {
        [$output, $errorCode] = $this->cli->runAsUser('apt-cache search php | grep -P "^php\d\.\d \-"');

        if (0 !== $errorCode) {
            return collect();
        }

        return collect(explode("\n", trim($output)))
            ->map(fn ($package) => substr($package, 0, strpos($package, ' -')))
            ->sortDesc()
            ->values();
    }
}
