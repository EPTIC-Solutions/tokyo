<?php

namespace Tokyo;

use Symfony\Component\Process\Process;

class CommandLine
{
    /**
     * Run the given command as the non-root user.
     */
    public function run(array|string $command): array
    {
        return $this->runCommand($command);
    }

    /**
     * Run the given command.
     */
    public function runAsUser(array|string $command): array
    {
        $command = is_array($command) ? $command : func_get_args();

        return $this->runCommand(['sudo', '-u', user(), ...$command]);
    }

    /**
     * Run the given command.
     */
    protected function runCommand(array|string $command): array
    {
        $command = is_array($command) ? $command : func_get_args();
        $process = new Process($command);
        $process->run();

        return $process->isSuccessful() ?
            [$process->getOutput(), $process->getExitCode()] :
            [$process->getErrorOutput(), $process->getExitCode()];
    }

    /**
     * Make sure that the current user is sudo.
     * This is required for certain commands that work with the system files.
     */
    public function ensureSudo(): void
    {
        if (0 !== getUID()) {
            error('This command requires Tokyo to be run as sudo!');

            exit(1);
        }
    }
}
