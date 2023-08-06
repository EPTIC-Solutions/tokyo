<?php

namespace Tokyo;

final class Site
{
    private readonly string $sitesPath;

    public function __construct(
        private readonly Configuration $conf,
        private readonly Filesystem $fs,
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
}
