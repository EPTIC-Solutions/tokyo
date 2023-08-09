<?php

namespace Tokyo\Services;

use Tokyo\CommandLine;
use Tokyo\Configuration;
use Tokyo\Contracts\PackageManager;
use Tokyo\Contracts\Service;
use Tokyo\Contracts\ServiceManager;
use Tokyo\Enums\OperatingSystem;
use Tokyo\Filesystem;
use Tokyo\System;

class DnsMasq implements Service
{
    private string $configPath = '/etc/dnsmasq.d/tokyo';

    public function __construct(
        private readonly Configuration $conf,
        private readonly CommandLine $cli,
        private readonly Filesystem $fs,
        private readonly ServiceManager $sm,
        private readonly PackageManager $pm,
        private readonly System $system,
    ) {
        //
    }

    public function getServiceName(): string
    {
        return 'dnsmasq';
    }

    public function install(): void
    {
        $serviceName = $this->getServiceName();
        $this->pm->ensureInstalled($serviceName);

        if ($this->cli->run(['which', 'systemd-resolve'])[1] === 0) {
            $this->cli->run(['systemctl', 'mask', 'systemd-resolved']);
            $this->cli->run(['systemctl', 'disable', 'systemd-resolved']);
            $this->cli->run(['systemctl', 'stop', 'systemd-resolved']);
        }

        $this->configureDomain();
        $this->fs->putAsUser('/etc/dnsmasq.conf', $this->fs->get(__DIR__ . '/../../stubs/dnsmasq.conf'));
        if($this->system->getOperatingSystem() === OperatingSystem::LINUX) {
            $this->fs->backup('/etc/resolv.conf');
        }

        if ($this->cli->run(['which', 'network-manager'])[1] === 0) {
            $this->sm->restart('network-manager');
        }

        $this->sm->enable($serviceName);
        $this->sm->start($serviceName);

        $this->sm->restart($serviceName);
    }

    public function configureDomain(string $domain = null): void
    {
        $domain = $domain ?? $this->conf->read('domain');

        $this->fs->putAsUser($this->configPath, 'address=/.' . $domain . '/127.0.0.1' . PHP_EOL);
    }

    public function uninstall(): void
    {
        $serviceName = $this->getServiceName();
        $this->fs->rm($this->configPath);

        if($this->system->getOperatingSystem() === OperatingSystem::LINUX) {
            $this->fs->restore('/etc/resolv.conf');
        }

        if ($this->pm->installed($serviceName)) {
            $this->sm->disable($serviceName);
            $this->sm->stop($serviceName);
            $this->pm->uninstall($serviceName);
        }

        if ($this->cli->run(['which', 'systemd-resolve'])[1] === 0) {
            $this->cli->run(['systemctl', 'unmask', 'systemd-resolved']);
            $this->cli->run(['systemctl', 'enable', 'systemd-resolved']);
            $this->cli->run(['systemctl', 'start', 'systemd-resolved']);
        }

        if ($this->cli->run(['which', 'network-manager'])[1] === 0) {
            $this->sm->restart('network-manager');
        }
    }
}
