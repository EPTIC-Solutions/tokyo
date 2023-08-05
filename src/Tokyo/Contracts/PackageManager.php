<?php

namespace Tokyo\Contracts;

use Illuminate\Support\Collection;

interface PackageManager
{
    /**
     * Get all the installed packages.
     */
    public function packages(): Collection;

    /**
     * Determine if the given package is installed.
     */
    public function installed(string $package): bool;

    /**
     * Ensure that the given package is installed.
     */
    public function ensureInstalled(string $package): void;

    /**
     * Install the given package and throw an exception on failure.
     */
    public function installOrFail(string $package): void;

    /**
     * Uninstall the given package.
     */
    public function uninstall(string $package): void;

    /**
     * Configure package manager on valet install.
     */
    public function setup(): void;

    /**
     * Determine if package manager is available on the system.
     */
    public function isAvailable(): bool;

    /**
     * Get a list of supported PHP versions.
     */
    public function supportedPhpVersions(): Collection;
}
