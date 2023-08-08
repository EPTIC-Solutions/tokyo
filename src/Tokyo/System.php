<?php

namespace Tokyo;

use Tokyo\Enums\OperatingSystem;

class System
{
    private array $supportedOperatingSystems = [
        OperatingSystem::DARWIN,
        OperatingSystem::LINUX,
    ];

    public function getOperatingSystem(): OperatingSystem
    {
        $os = strtolower(PHP_OS);

        if (str_contains($os, 'darwin')) {
            return OperatingSystem::DARWIN;
        }

        if (str_contains($os, 'linux')) {
            return OperatingSystem::LINUX;
        }

        if (str_contains($os, 'win')) {
            return OperatingSystem::WINDOWS;
        }

        return OperatingSystem::UNKNOWN;
    }

    public function isSupportedOperatingSystem(): bool
    {
        return in_array($this->getOperatingSystem(), $this->supportedOperatingSystems);
    }
}
