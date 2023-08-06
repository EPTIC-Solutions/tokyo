<?php

namespace Tokyo\Contracts;

interface Service
{
    /**
     * Get the service name.
     */
    public function getServiceName(): string;

    /**
     * Install the service.
     */
    public function install(): void;

    /**
     * Uninstall the service.
     */
    public function uninstall(): void;
}
