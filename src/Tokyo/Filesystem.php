<?php

namespace Tokyo;

use FilesystemIterator;

class Filesystem
{
    /**
     * Determine if the given path is a directory.
     */
    public function isDir(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Create a directory.
     */
    public function mkdir(string $path, string $owner = null, int $mode = 0755): void
    {
        mkdir($path, $mode, true);

        if ($owner) {
            $this->chown($path, $owner);
        }
    }

    public function rm(array|string $files): void
    {
        $files = is_array($files) ? $files : func_get_args();

        foreach ($files as $file) {
            if (!file_exists($file) && !is_link($file)) {
                dump($file);
                continue;
            }

            if ($this->isDir($file)) {
                $this->rm(iterator_to_array(new FilesystemIterator($file)));

                if (!@rmdir($file)) {
                    error("Could not delete directory: $file");

                    exit(1);
                }
            } else {
                if (!@unlink($file)) {
                    error("Could not delete file: $file");

                    exit(1);
                }
            }
        }
    }

    public function exists(string $file): bool
    {
        return file_exists($file);
    }

    public function put(string $path, string $contents): void
    {
        file_put_contents($path, $contents);
    }

    public function get(string $path): string
    {
        if (!$this->exists($path)) {
            error("File does not exist: $path");

            exit(1);
        }
        return file_get_contents($path);
    }

    /**
     * Ensure that the given directory exists.
     */
    public function ensureDirExists(string $path, string $owner = null, int $mode = 0755): void
    {
        if (!$this->isDir($path)) {
            $this->mkdir($path, $owner, $mode);
        }
    }

    /**
     * Change the owner of the given path.
     */
    public function chown(string $path, string $user): void
    {
        chown($path, $user);
    }
}
