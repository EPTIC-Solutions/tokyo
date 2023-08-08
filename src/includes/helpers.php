<?php

require_once 'constants.php';

use DI\Container;
use Silly\Application;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tokyo\Tokyo;

function container(): Container
{
    return Tokyo::getContainer();
}

function app(): Application
{
    return resolve('app');
}

function config($key, $default = null)
{
    $explode = explode('.', $key);

    $config = resolve('config');

    foreach ($explode as $key) {
        if (!isset($config[$key])) {
            return $default;
        }

        $config = $config[$key];
    }

    return $config;
}

function user()
{
    return $_SERVER['SUDO_USER'] ?? $_SERVER['USER'];
}

function group()
{
    return exec('id -gn ' . user());
}

function writer(OutputInterface $writer = null): OutputInterface
{
    $container = container();

    if (!$writer) {
        if (!$container->has('writer')) {
            $container->set('writer', new ConsoleOutput());
        }

        return $container->make('writer');
    }

    $container->set('writer', $writer);

    return $writer;
}

function reader(InputInterface $reader = null): InputInterface
{
    $container = container();

    if (!$reader) {
        if (!$container->has('reader')) {
            $container->set('reader', new ArgvInput());
        }

        return $container->make('reader');
    }

    $container->set('reader', $reader);

    return $reader;
}

function ask(string $question, string $default = null, callable $validator = null)
{
    $io = new SymfonyStyle(reader(), writer());

    return $io->ask($question, $default, $validator);
}

function info($output): void
{
    output('<info>' . $output . '</info>');
}

function warning(string $output): void
{
    output('<fg=yellow>' . $output . '</>');
}

function error(string $output): void
{
    output('<error>' . $output . '</error>');
}

/**
 * Output a table to the console.
 */
function table(array $headers = [], array $rows = []): void
{
    $table = new Table(writer());

    $table->setHeaders($headers)->setRows($rows);

    $table->render();
}

function task(string $title, Closure $task = null, string $loadingText = '...')
{
    $writer = writer();
    $writer->write("$title: <comment>{$loadingText}</comment>");

    if (null === $task) {
        $errorCode = 0;
    } else {
        $errorCode = $task();
        if (null === $errorCode) {
            $errorCode = 0;
        }
    }

    if ($writer->isDecorated()) { // Determines if we can use escape sequences
        // Move the cursor to the beginning of the line
        $writer->write("\x0D");

        // Erase the line
        $writer->write("\x1B[2K");
    } else {
        output(''); // Make sure we first close the previous line
    }

    output("$title: " . ((0 === $errorCode) ? '<info>✔</info>' : '<error>failed</error>'));

    return $errorCode;
}

/**
 * Output the given text to the console.
 */
function output(?string $output = ''): void
{
    writer()->writeln($output);
}

/**
 * Resolve the given class from the container.
 *
 * @template T
 *
 * @return mixed|T
 */
function resolve(string $class, array $parameters = []): mixed
{
    if (container()->has($class)) {
        try {
            return container()->get($class);
        } catch (\Exception $e) {
            // This should never return but this fixes complains from PHPStorm
            return null;
        }
    }

    try {
        return container()->make($class, $parameters);
    } catch (\Exception $e) {
        return null;
    }
}

function isInstalled(): bool
{
    return is_dir(TOKYO_ROOT);
}

//function isTesting(): bool
//{
//    return false !== strpos($_SERVER['SCRIPT_NAME'], 'phpunit');
//}

function getUID(): int
{
    $t = tmpfile();
    $uid = fstat($t)['uid'];
    fclose($t);

    return $uid;
}

function isDebug(): bool
{
    return 'true' === getenv('TOKYO_DEBUG');
}
