<?php

declare(strict_types=1);

namespace MCF\Queue;

use RuntimeException;

final class McfQueueProcess
{
    private function __construct()
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Start
    |--------------------------------------------------------------------------
    */

    public static function start(): bool
    {
        $php = self::resolvePhpBinary();

        $artisan = base_path('artisan');

        if (! is_file($artisan)) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return self::startWindows(
                $php,
                $artisan,
            );
        }

        return self::startUnix(
            $php,
            $artisan,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve PHP CLI
    |--------------------------------------------------------------------------
    |
    | PHP_BINARY is not guaranteed to point to the CLI executable.
    |
    | For example, under Apache on Windows it may point to httpd.exe.
    |
    | Therefore MCF resolves the CLI executable independently.
    |
    | MCF_QUEUE_PHP_BINARY may be used when the hosting environment
    | uses a custom PHP CLI path.
    |
    */

    private static function resolvePhpBinary(): string
    {
        /*
         * Explicit configuration always has priority.
         */

        $configured = getenv(
            'MCF_QUEUE_PHP_BINARY',
        );

        if (
            is_string($configured)
            && trim($configured) !== ''
        ) {
            $configured = trim(
                $configured,
            );

            if (self::isExecutable(
                $configured,
            )) {
                return $configured;
            }
        }

        /*
         * CLI
         *
         * When MCF itself is already running through PHP CLI,
         * PHP_BINARY is the correct executable.
         */

        if (PHP_SAPI === 'cli') {
            return PHP_BINARY;
        }

        /*
         * Windows
         *
         * PHP_BINDIR normally points to the PHP installation
         * directory. Resolve php.exe from that directory.
         */

        if (PHP_OS_FAMILY === 'Windows') {
            $php = PHP_BINDIR
                . DIRECTORY_SEPARATOR
                . 'php.exe';

            if (self::isExecutable(
                $php,
            )) {
                return $php;
            }
        }

        /*
         * Unix / Linux / macOS
         *
         * PHP_BINDIR may contain the CLI binary directory.
         */

        $php = PHP_BINDIR
            . DIRECTORY_SEPARATOR
            . 'php';

        if (self::isExecutable(
            $php,
        )) {
            return $php;
        }

        /*
         * PATH fallback.
         *
         * This is useful on VPS/shared hosting where PHP CLI
         * is available through the system PATH but PHP_BINDIR
         * does not resolve correctly.
         */

        $php = self::findPhpFromPath();

        if ($php !== null) {
            return $php;
        }

        /*
         * Final fallback.
         *
         * This preserves the normal PHP behavior when no better
         * CLI executable can be resolved.
         */

        return PHP_BINARY;
    }

    /*
    |--------------------------------------------------------------------------
    | Windows
    |--------------------------------------------------------------------------
    */

    private static function startWindows(
        string $php,
        string $artisan,
    ): bool {
        $command = sprintf(
            'start "" /B "%s" "%s" queue:work --once',
            $php,
            $artisan,
        );

        $process = proc_open(
            $command,
            [
                0 => [
                    'file',
                    'NUL',
                    'r',
                ],

                1 => [
                    'file',
                    'NUL',
                    'w',
                ],

                2 => [
                    'file',
                    'NUL',
                    'w',
                ],
            ],
            $pipes,
        );

        if ($process === false) {
            return false;
        }

        proc_close(
            $process,
        );

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Unix / Linux / macOS
    |--------------------------------------------------------------------------
    */

    private static function startUnix(
        string $php,
        string $artisan,
    ): bool {
        $command = sprintf(
            'nohup %s %s queue:work --once > /dev/null 2>&1 &',
            escapeshellarg($php),
            escapeshellarg($artisan),
        );

        exec(
            $command,
            $output,
            $status,
        );

        return $status === 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Executable Check
    |--------------------------------------------------------------------------
    */

    private static function isExecutable(
        string $path,
    ): bool {
        return is_file($path)
            && is_executable($path);
    }

    /*
    |--------------------------------------------------------------------------
    | PATH Resolver
    |--------------------------------------------------------------------------
    */

    private static function findPhpFromPath(): ?string
    {
        $command = PHP_OS_FAMILY === 'Windows'
            ? 'where php'
            : 'command -v php';

        $output = [];
        $status = 1;

        exec(
            $command,
            $output,
            $status,
        );

        if (
            $status !== 0
            || $output === []
        ) {
            return null;
        }

        foreach ($output as $path) {
            $path = trim($path);

            if (
                $path !== ''
                && self::isExecutable($path)
            ) {
                return $path;
            }
        }

        return null;
    }
}