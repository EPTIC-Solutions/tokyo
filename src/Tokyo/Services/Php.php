<?php

namespace Tokyo\Services;

use DomainException;
use Tokyo\Contracts\PackageManager;
use Tokyo\Contracts\Service;
use Tokyo\Contracts\ServiceManager;
use Tokyo\Filesystem;

class Php implements Service
{
    public function __construct(
        private readonly Filesystem $fs,
        private readonly ServiceManager $sm,
        private readonly PackageManager $pm,
    ) {
        //
    }

    public function getServiceName(): string
    {
        return 'php' . $this->getPhpVersion() . '-fpm';
    }

    public function install(): void
    {
        $serviceName = $this->getServiceName();
        $this->pm->ensureInstalled($serviceName);

        if ($this->pm->installed('apache2')) {
            $this->sm->disable('apache2');
            $this->sm->stop('apache2');
        }

        $this->sm->enable($serviceName);
        $this->sm->start($serviceName);

        $this->installConfiguration();
        $this->symlinkPrimarySock();

        $this->sm->restart($serviceName);
    }

    private function installConfiguration(): void
    {
        $contents = $this->fs->get(__DIR__ . '/../../stubs/tokyo-fpm.conf');
        $phpVersion = $this->getPhpVersion();

        $this->fs->putAsUser(
            $this->fpmConfigPath() . '/tokyo.conf',
            str_replace([
                'TOKYO_USER',
                'TOKYO_GROUP',
                'TOKYO_SOCKET',
            ], [
                user(),
                group(),
                TOKYO_ROOT . '/' . $this->getSockName($phpVersion),
            ], $contents)
        );
    }

    /**
     * Symlink the given php version's fpm socket file to be the primary tokyo.sock for nginx.
     */
    public function symlinkPrimarySock(string $phpVersion = null): void
    {
        if (!$phpVersion) {
            $phpVersion = $this->getPhpVersion();
        }

        $this->fs->symlinkAsUser(TOKYO_ROOT . '/' . $this->getSockName($phpVersion), TOKYO_ROOT . '/tokyo.sock');
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     */
    public function fpmConfigPath(string $phpVersion = null): string
    {
        $phpVersion = $phpVersion ?: $this->getPhpVersion();

        return collect([
            '/etc/php/' . $phpVersion . '/fpm/pool.d', // Ubuntu
            '/etc/php' . $phpVersion . '/fpm/pool.d', // Ubuntu
            '/etc/php' . $phpVersion . '/php-fpm.d', // Manjaro
            '/etc/php-fpm.d', // Fedora
            '/etc/php/php-fpm.d', // Arch
            '/etc/php7/fpm/php-fpm.d', // openSUSE PHP7
            '/etc/php8/fpm/php-fpm.d', // openSUSE PHP8
        ])->first(
            fn ($path) => is_dir($path),
            function () {
                error('Unable to determine PHP-FPM configuration folder.');
                exit(1);
            }
        );
    }

    private function getSockName($version): string
    {
        return 'tokyof' . $version . '.sock';
    }

    public function getPhpVersion(): string
    {
        return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    }

    public function uninstall(): void
    {
        try {
            $this->fs->rm($this->fpmConfigPath() . '/tokyo.conf');
        } catch (DomainException) {
            // Ignore
        }

        $serviceName = $this->getServiceName();
        if ($this->pm->installed($serviceName)) {
            $this->sm->disable($serviceName);
            $this->sm->stop($serviceName);
            $this->pm->uninstall($serviceName);
        }
    }
}
