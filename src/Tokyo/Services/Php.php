<?php

namespace Tokyo\Services;

use DomainException;
use Tokyo\CommandLine;
use Tokyo\Configuration;
use Tokyo\Contracts\PackageManager;
use Tokyo\Contracts\Service;
use Tokyo\Contracts\ServiceManager;
use Tokyo\Filesystem;

class Php implements Service
{
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

    private function installConfiguration()
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
     *
     * @param  string  $phpVersion
     * @return void
     */
    public function symlinkPrimarySock($phpVersion = null)
    {
        if (!$phpVersion) {
            $phpVersion = $this->getPhpVersion();
        }

        $this->fs->symlinkAsUser(TOKYO_ROOT . '/' . $this->getSockName($phpVersion), TOKYO_ROOT . '/tokyo.sock');
    }

    /**
     * Get the path to the FPM configuration file for the current PHP version.
     *
     * @return string
     */
    public function fpmConfigPath($phpVersion = null)
    {
        $phpVersion = $phpVersion ?: $this->getPhpVersion();

        $path = collect([
            '/etc/php/' . $phpVersion . '/fpm/pool.d', // Ubuntu
            '/etc/php' . $phpVersion . '/fpm/pool.d', // Ubuntu
            '/etc/php' . $phpVersion . '/php-fpm.d', // Manjaro
            '/etc/php-fpm.d', // Fedora
            '/etc/php/php-fpm.d', // Arch
            '/etc/php7/fpm/php-fpm.d', // openSUSE PHP7
            '/etc/php8/fpm/php-fpm.d', // openSUSE PHP8
        ])->first(function ($path) {
            return is_dir($path);
        }, function () {
            throw new DomainException('Unable to determine PHP-FPM configuration folder.');
        });

        return $path;
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
