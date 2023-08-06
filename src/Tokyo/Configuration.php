<?php

namespace Tokyo;

class Configuration
{
    private string $path = TOKYO_ROOT . '/config.json';

    public function __construct(private readonly Filesystem $fs)
    {
        //
    }

    public function install()
    {
        // Create configuration directory
        $this->fs->ensureDirExists(TOKYO_ROOT, user());
        // Create Sites directory
        $this->fs->ensureDirExists(TOKYO_ROOT . '/sites', user());
        // Create Logs directory
        $this->fs->ensureDirExists(TOKYO_ROOT . '/logs', user());

        $this->writeConfiguration();
    }

    public function writeConfiguration()
    {
        if (!$this->fs->exists($this->path)) {
            $this->fs->putAsUser($this->path, $this->fs->get(__DIR__ . '/../stubs/config.json'));
        }
    }

    public function uninstall()
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

    public function write(string $key, mixed $value)
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

    public function prependPath(string $path)
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
}
