<?php

namespace Tokyo\Contracts;

use Illuminate\Support\Collection;

interface ServiceManager
{
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