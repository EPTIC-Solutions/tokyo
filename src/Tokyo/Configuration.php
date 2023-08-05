<?php

namespace Tokyo;

class Configuration
{
    private string $path = TOKYO_HOME . '/config.json';

    public function __construct(private readonly Filesystem $fs)
    {
        //
    }

    public function install()
    {
        // Create configuration directory
        $this->fs->ensureDirExists(TOKYO_HOME, user());
        // Create Sites directory
        $this->fs->ensureDirExists(TOKYO_HOME . '/Sites', user());
        // Create Logs directory
        $this->fs->ensureDirExists(TOKYO_HOME . '/Logs', user());

        $this->writeConfiguration();
    }

    public function writeConfiguration()
    {
        if (!$this->fs->exists($this->path)) {
            $this->fs->put($this->path, $this->fs->get(__DIR__ . '/../stubs/config.json'));
        }
    }

    public function uninstall()
    {
        $this->fs->rm(TOKYO_HOME);
    }

    public function read(string $key): mixed
    {
        $config = json_decode($this->fs->get($this->path), true);

        return $config[$key] ?? null;
    }
}
