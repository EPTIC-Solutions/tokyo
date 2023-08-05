<?php

namespace Tokyo\PackageManagers;

use Illuminate\Support\Collection;
use Tokyo\CommandLine;
use Tokyo\Contracts\PackageManager;

class Brew implements PackageManager
{
    public function __construct(private readonly CommandLine $cli)
    {
        //
    }

    public function packages(): Collection
    {
        [$output, $errorCode] = $this->cli->run(['brew', 'list']);

        if ($errorCode !== 0) {
            return collect();
        }

        return collect(explode("\n", trim($output)))
            ->map(fn ($package) => [
                'name' => $package,
            ]);
    }

    public function installed(string $package): bool
    {
        $installedPackages = $this->packages();

        return $installedPackages->where('name', $package)->count();
    }

    public function ensureInstalled(string $package): void
    {
        if (!$this->installed($package)) {
            $this->installOrFail($package);
        }
    }

    public function installOrFail(string $package): void
    {
        task("🍺 [$package] is being installed via Brew", function () use ($package) {
            [, $errorCode] = $this->cli->run(['brew', 'install', $package]);

            if ($errorCode !== 0) {
                error("Could not install [$package] via Brew");

                exit(1);
            }
        });
    }

    public function uninstall(string $package): void
    {
        if ($this->installed($package)) {
            task("🍺 [$package] is being uninstalled via Brew", function () use ($package) {
                [, $errorCode] = $this->cli->run(['brew', 'uninstall', $package]);

                if ($errorCode !== 0) {
                    error("Could not uninstall [$package] via Brew");

                    exit(1);
                }
            });
        }
    }

    public function setup(): void
    {
    }

    public function isAvailable(): bool
    {
        [, $errorCode] = $this->cli->run(['which', 'brew']);

        return $errorCode === 0;
    }

    public function supportedPhpVersions(): Collection
    {
    }
}
