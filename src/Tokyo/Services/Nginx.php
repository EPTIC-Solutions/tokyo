<?php

namespace Tokyo\Services;

use Tokyo\CommandLine;
use Tokyo\Configuration;
use Tokyo\Contracts\PackageManager;
use Tokyo\Contracts\Service;
use Tokyo\Contracts\ServiceManager;
use Tokyo\Filesystem;
use Tokyo\PackageManagers\Brew;

class Nginx implements Service
{
    private const NGINX_CONF = '/etc/nginx/nginx.conf';

    private const TOKYO_CONF = '/etc/nginx/sites-available/tokyo.conf';

    private const TOKYO_CONF_ENABLED = '/etc/nginx/sites-enabled/tokyo.conf';

    public function __construct(
        private readonly Configuration $conf,
        private readonly CommandLine $cli,
        private readonly Filesystem $fs,
        private readonly ServiceManager $sm,
        private readonly PackageManager $pm,
    ) {
        //
    }

    public function getServiceName(): string
    {
        return 'nginx';
    }

    public function install(): void
    {
        $serviceName = $this->getServiceName();
        $this->pm->ensureInstalled($serviceName);

        if ($this->pm instanceof Brew) {
        } else {
            $this->fs->ensureDirExists('/etc/nginx/sites-available');
            $this->fs->ensureDirExists('/etc/nginx/sites-enabled');
        }

        $this->installConfiguration();
        $this->installTokyoConfiguration();

        $this->sm->start($serviceName);
        $this->sm->enable($serviceName);
    }

    private function installConfiguration(): void
    {
        $config = $this->fs->get(__DIR__ . '/../../stubs/nginx.conf');

        $this->fs->backup(self::NGINX_CONF);

        $newConfig = str_replace([
            'TOKYO_USER',
            'TOKYO_GROUP',
            'TOKYO_PID',
            'TOKYO_ROOT',
        ], [
            user(),
            group(),
            'pid /run/nginx.pid',
            TOKYO_ROOT,
        ], $config);

        $this->fs->put(self::NGINX_CONF, $newConfig);
    }

    private function installTokyoConfiguration()
    {
        if ($this->fs->exists('/etc/nginx/sites-enabled/default')) {
            $this->fs->rm('/etc/nginx/sites-enabled/default');
        }

        $this->fs->putAsUser(
            self::TOKYO_CONF,
            str_replace(
                ['TOKYO_ROOT', 'TOKYO_SERVER', 'TOKYO_STATIC_PREFIX', 'TOKYO_PORT'],
                [TOKYO_ROOT, TOKYO_SERVER, '123', $this->conf->read('port')],
                $this->fs->get(__DIR__ . '/../../stubs/tokyo.conf')
            )
        );

        $this->cli->run(['ln', '-snf', self::TOKYO_CONF, self::TOKYO_CONF_ENABLED]);
    }

    public function uninstall(): void
    {
        $serviceName = $this->getServiceName();

        if ($this->pm->installed($serviceName)) {
            $this->sm->disable($serviceName);
            $this->sm->stop($serviceName);
            $this->pm->uninstall($serviceName);
        }
    }
}
