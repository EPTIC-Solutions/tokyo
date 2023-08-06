<?php

namespace Tokyo\Contracts;

interface Service
{
    public function getServiceName(): string;

    public function install(): void;

    public function uninstall(): void;
}
