<?php

namespace Tokyo\Contracts;

interface Service
{
    public function install(): void;

    public function uninstall(): void;
}
