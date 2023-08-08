<?php

namespace Tokyo;

use Illuminate\Support\Collection;
use Tokyo\Services\Php;

final class Site
{
    private readonly string $sitesPath;

    public function __construct(
        private readonly Configuration $conf,
        private readonly Filesystem $fs,
        private readonly Php $php,
    ) {
        $this->sitesPath = TOKYO_ROOT . '/sites';
    }

    public function park(string $path): void
    {
        $paths = $this->conf->read('paths');

        if (!in_array($path, $paths)) {
            $paths[] = $path;
        }

        $this->conf->write('paths', $paths);
    }

    public function unpark(string $path): void
    {
        $paths = $this->conf->read('paths');

        if (($key = array_search($path, $paths)) !== false) {
            unset($paths[$key]);
        }

        $this->conf->write('paths', $paths);
    }

    public function link(string $target, string $link): string
    {
        $this->fs->ensureDirExists(
            $linkPath = $this->sitesPath,
            user()
        );

        $this->conf->prependPath($linkPath);

        $this->fs->symlinkAsUser($target, $symlink = $linkPath . '/' . $link);

        return $symlink;
    }

    public function unlink(string $target): void
    {
        if ($this->fs->exists($linkPath = $this->sitesPath . '/' . $target)) {
            $this->fs->rm($linkPath);
        }
    }

    public function parked(): Collection
    {
        $links = $this->getSites($this->sitesPath);

        $paths = $this->conf->read('paths');
        $parkedLinks = collect();
        foreach (array_reverse($paths) as $path) {
            if ($path === $this->sitesPath) {
                continue;
            }

            // Only merge on the parked sites that don't interfere with the linked sites
            $sites = $this->getSites($path)->filter(fn ($_, $key) => !$links->has($key));

            $parkedLinks = $parkedLinks->merge($sites);
        }

        return $parkedLinks;
    }

    public function linked(): Collection
    {
        return $this->getSites($this->sitesPath);
    }

    /**
     * Get list of sites and return them formatted
     * Will work for symlink and normal site paths.
     */
    public function getSites(string $path): Collection
    {
        $domain = $this->conf->read('domain');
        $this->fs->ensureDirExists($path, user());

        return collect($this->fs->scandir($path))
            ->mapWithKeys(function ($site) use ($path) {
                $sitePath = $path . '/' . $site;

                if (is_link($sitePath)) {
                    $realPath = readlink($sitePath);
                } else {
                    $realPath = realpath($sitePath);
                }

                return [$site => $realPath];
            })->filter(fn ($path) => $this->fs->isDir($path))
            ->map(function ($path, $site) use ($domain) {
                $url = 'http://' . $site . '.' . $domain;
                $phpVersion = $this->php->getPhpVersion();

                return [
                    'site' => $site,
                    'secured' => '', // TODO: Add secured check
                    'url' => $url,
                    'path' => $path,
                    'phpVersion' => $phpVersion,
                ];
            });
    }

    /**
     * Remove all broken symbolic links.
     */
    public function pruneLinks(): void
    {
        if (!$this->fs->isDir(TOKYO_ROOT)) {
            return;
        }

        $this->fs->ensureDirExists($this->sitesPath, user());

        $this->fs->removeBrokenLinksAt($this->sitesPath);
    }
}
