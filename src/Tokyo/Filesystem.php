<?php

namespace Tokyo;

use FilesystemIterator;

class Filesystem
{
    public function __construct(private readonly CommandLine $cli)
    {
        //
    }

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

    /**
     * Create a symlink to the given target.
     */
    public function symlink(string $target, string $link): void
    {
        if ($this->exists($link)) {
            $this->rm($link);
        }

        symlink($target, $link);
    }

    /**
     * Create a symlink to the given target for the non-root user.
     *
     * This uses the command line as PHP can't change symlink permissions.
     */
    public function symlinkAsUser(string $target, string $link): void
    {
        if (is_link($link)) {
            $this->rm($link);
        }

        $this->cli->runAsUser(['ln', '-s', $target, $link]);
    }

    public function rm(array|string $files): void
    {
        $files = is_array($files) ? $files : func_get_args();

        foreach ($files as $file) {
            if (!file_exists($file) && !is_link($file)) {
                continue;
            }

            if (is_link($file)) {
                if (!@unlink($file)) {
                    error("Could not delete symlink: $file");

                    exit(1);
                }
            } elseif ($this->isDir($file)) {
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

    public function put(string $path, string $contents, string $owner = null): bool
    {
        $return = file_put_contents($path, $contents);

        if ($owner) {
            $this->chown($path, $owner);
        }

        return boolval($return);
    }

    public function putAsUser(string $path, string $contents, string $owner = null): bool
    {
        return $this->put($path, $contents, user());
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

    /**
     * Backup the given file.
     */
    public function backup(string $file): bool
    {
        $to = $file . '.bak';

        if (!$this->exists($to)) {
            if ($this->exists($file)) {
                [, $errorCode] = $this->cli->run(['sudo', 'mv', $file, $to]);

                return 0 === $errorCode;
            }
        }

        return false;
    }

    /**
     * Restore a backed up file.
     */
    public function restore(string $file): bool
    {
        $from = $file . '.bak';

        if ($this->exists($from)) {
            [, $errorCode] = $this->cli->run(['sudo', 'mv', $from, $file]);

            return 0 === $errorCode;
        }

        return false;
    }

    /**
     * Scan the given directory path.
     */
    public function scandir(string $path): array
    {
        return collect(scandir($path))
            ->reject(fn ($file) => in_array($file, ['.', '..']))
            ->values()
            ->all();
    }

    /**
     * Remove all the broken symbolic links at the given path.
     */
    public function removeBrokenLinksAt(string $path): void
    {
        collect($this->scandir($path))
            ->filter(fn ($file) => $this->isBrokenLink($path . '/' . $file))
            ->each(fn ($file) => $this->rm($path . '/' . $file));
    }

    /**
     * Determine if the given path is a broken symbolic link.
     */
    public function isBrokenLink(string $path): bool
    {
        return is_link($path) && !file_exists($path);
    }
}
