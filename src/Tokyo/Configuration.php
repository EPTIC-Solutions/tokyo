<?php

namespace Tokyo;

class Configuration
{
    private string $path = TOKYO_ROOT . '/config.json';

    public function __construct(private readonly Filesystem $fs)
    {
        //
    }

    public function install(): void
    {
        // Create configuration directory
        $this->fs->ensureDirExists(TOKYO_ROOT, user());
        // Create Sites directory
        $this->fs->ensureDirExists(TOKYO_ROOT . '/sites', user());
        // Create Logs directory
        $this->fs->ensureDirExists(TOKYO_ROOT . '/logs', user());

        $this->writeConfiguration();
    }

    public function writeConfiguration(): void
    {
        if (!$this->fs->exists($this->path)) {
            $this->fs->putAsUser($this->path, $this->fs->get(__DIR__ . '/../stubs/config.json'));
        }
    }

    public function uninstall(): void
    {
        $this->fs->rm(TOKYO_ROOT);
    }

    public function read(string $key, mixed $default = null): mixed
    {
        $explode = explode('.', $key);

        $config = json_decode($this->fs->get($this->path), true);

        foreach ($explode as $key) {
            if (!isset($config[$key])) {
                return $default;
            }

            $config = $config[$key];
        }

        return $config;
    }

    public function write(string $key, mixed $value): void
    {
        $config = json_decode($this->fs->get($this->path), true);

        $explode = explode('.', $key);

        foreach ($explode as $index => $k) {
            if (!isset($config[$k])) {
                continue;
            }

            if ($index === count($explode) - 1) {
                $config[$k] = $value;
            }
        }

        $this->fs->putAsUser($this->path, json_encode($config, JSON_PRETTY_PRINT));
    }

    public function prependPath(string $path): void
    {
        $paths = $this->read('paths');

        if (!in_array($path, $paths)) {
            $paths = [
                $path,
                ...$paths,
            ];
        }

        $this->write('paths', $paths);
    }

    public function prune(): void
    {
        if (!$this->fs->exists($this->path)) {
            return;
        }

        $paths = $this->read('paths');

        $this->write(
            'paths',
            collect($paths)
                ->filter(fn ($path) => $this->fs->isDir($path))
                ->values()
                ->all()
        );
    }
}
