<?php

namespace Tokyo\Contracts;

use Illuminate\Support\Collection;

interface ServiceManager
{
    /**
     * The supported operating systems.
     *
     * @var array<int, \Tokyo\OperatingSystem>
     */
    public function supportedOperatingSystems(): array;

    /**
     * Start the services.
     */
    public function start(array|string $services);

    /**
     * Stop the services.
     */
    public function stop(array|string $services);

    /**
     * Restart the given services.
     */
    public function restart(array|string $services);

    /**
     * Make the services start on boot.
     */
    public function enable(array|string $services);

    /**
     * Stop the services from starting on boot.
     */
    public function disable(array|string $services);

    /**
     * Check the status of the services.
     */
    public function status(array|string $services): bool;

    /**
     * Determine if the service manager is available on the system.
     */
    public function isAvailable(): bool;

    /**
     * Get the currently running services.
     */
    public function getRunningServices(): Collection;

    /**
     * Get all the currently running services.
     */
    public function getAllRunningServices(): Collection;
}
