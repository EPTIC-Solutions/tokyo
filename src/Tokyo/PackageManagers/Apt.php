<?php

namespace Tokyo\PackageManagers;

use Illuminate\Support\Collection;
use Tokyo\CommandLine;
use Tokyo\Contracts\PackageManager;

class Apt implements PackageManager
{
    public function __construct(private readonly CommandLine $cli)
    {
        //
    }

    public function packages(): Collection
    {
        [$packagesRaw, $errorCode] = $this->cli->run('dpkg-query --list | grep ^ii | sed \'s/\s\+/ /g\'');

        if ($errorCode !== 0) {
            return collect();
        }

        return collect(explode("\n", trim($packagesRaw)))
            ->map(fn ($package) => [
                'name' => explode(' ', $package)[1],
                'version' => explode(' ', $package)[2],
                'arch' => explode(' ', $package)[3],
            ]);
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
        task("🍺 [$package] is being installed via Apt", function () use ($package) {
            [, $errorCode] = $this->cli->run(['sudo', 'apt', 'install', '-y', $package]);

            if ($errorCode !== 0) {
                throw new \DomainException("Could not find [$package] via Apt");
            }
        });
    }

    public function uninstall(string $package): void
    {
        task("🍺 [$package] is being uninstalled via Apt", function () use ($package) {
            [, $errorCode] = $this->cli->run(['sudo', 'apt', 'remove', '-y', $package]);

            if ($errorCode !== 0) {
                throw new \DomainException("Could not find [$package] via Apt");
            }
        });
    }

    public function setup(): void
    {
    }

    public function isAvailable(): bool
    {
        [, $errorCode] = $this->cli->run(['which', 'apt']);
        return $errorCode === 0;
    }

    public function supportedPhpVersions(): Collection
    {
        [$output, $errorCode] = $this->cli->run('apt-cache search php | grep -P "^php\d\.\d \-"');

        if ($errorCode !== 0) {
            return collect();
        }

        return collect(explode("\n", trim($output)))
            ->map(fn ($package) => substr($package, 0, strpos($package, ' -')))
            ->sortDesc()
            ->values();
    }
}