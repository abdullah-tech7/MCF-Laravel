<?php

declare (strict_types = 1);

namespace MCF\Generators;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final class InstallGenerator
{
    public function __construct(
        protected Filesystem $files,
    ) {
    }

    /**
     * Install the MCF framework structure
     * into the Laravel application.
     *
     * MCF installation is intended to be performed once
     * on a Laravel application.
     */
    public function install(
        string $basePath,
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Prevent Reinstallation
        |--------------------------------------------------------------------------
        */

        if ($this->isInstalled($basePath)) {
            throw new RuntimeException(
                'MCF is already installed in this Laravel application.',
            );
        }

        $sourcePath = $this->sourcePath();

        $this->validateSource(
            $sourcePath,
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Backup Directory
        |--------------------------------------------------------------------------
        |
        | z_backup is created only for the affected Laravel files and
        | directories. It is not a complete project backup.
        |
        */

        $backupPath = $this->backupPath(
            $basePath,
        );

        if ($this->files->exists($backupPath)) {
            throw new RuntimeException(
                'The z_backup directory already exists. '
                . 'MCF will not overwrite an existing backup. '
                . 'Review or rename the existing z_backup directory '
                . 'before running the installation again.',
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Backup
        |--------------------------------------------------------------------------
        |
        | Everything that MCF will remove or replace is backed up first.
        |
        */

        $this->createBackup(
            basePath: $basePath,
            sourcePath: $sourcePath,
            backupPath: $backupPath,
        );

        /*
        |--------------------------------------------------------------------------
        | app/MCF
        |--------------------------------------------------------------------------
        |
        | MCF is added to the application.
        |
        */

        $this->copyDirectory(
            source: $sourcePath . '/app/MCF',
            destination: $basePath . '/app/MCF',
        );

        /*
        |--------------------------------------------------------------------------
        | app/Models
        |--------------------------------------------------------------------------
        |
        | MCF owns the Models directory.
        | The existing Laravel Models directory is replaced completely.
        |
        */

        $this->replaceDirectory(
            source: $sourcePath . '/app/Models',
            destination: $basePath . '/app/Models',
        );

        /*
        |--------------------------------------------------------------------------
        | database
        |--------------------------------------------------------------------------
        |
        | MCF provides its own complete database structure.
        |
        */

        $this->replaceDirectory(
            source: $sourcePath . '/database',
            destination: $basePath . '/database',
        );

        /*
        |--------------------------------------------------------------------------
        | config/mcf.php
        |--------------------------------------------------------------------------
        |
        | Add the MCF configuration.
        |
        */

        $this->copyFile(
            source: $sourcePath . '/config/mcf.php',
            destination: $basePath . '/config/mcf.php',
        );

        /*
        |--------------------------------------------------------------------------
        | config/filesystems.php
        |--------------------------------------------------------------------------
        |
        | MCF provides the filesystems configuration used by its storage system.
        |
        */

        $this->copyFile(
            source: $sourcePath . '/config/filesystems.php',
            destination: $basePath . '/config/filesystems.php',
        );

          /*
        |--------------------------------------------------------------------------
        | config/queue.php
        |--------------------------------------------------------------------------
        |
        | MCF provides the queue configuration
        |
        */

        $this->copyFile(
            source: $sourcePath . '/config/queue.php',
            destination: $basePath . '/config/queue.php',
        );

         /*
        |--------------------------------------------------------------------------
        | config/mail.php
        |--------------------------------------------------------------------------
        |
        | MCF provides the mail configuration used by its mail system.
        |
        */

        $this->copyFile(
            source: $sourcePath . '/config/mail.php',
            destination: $basePath . '/config/mail.php',
        );

        /*
        |--------------------------------------------------------------------------
        | resources/views
        |--------------------------------------------------------------------------
        |
        | MCF views are added to Laravel's views directory.
        |
        | Only files supplied by MCF are overwritten.
        | Other application views remain untouched.
        |
        */

        $this->copyDirectory(
            source: $sourcePath . '/resources/views',
            destination: $basePath . '/resources/views',
        );

        /*
        |--------------------------------------------------------------------------
        | bootstrap/app.php
        |--------------------------------------------------------------------------
        |
        | MCF provides its own bootstrap configuration.
        |
        */

        $this->copyFile(
            source: $sourcePath . '/bootstrap/app.php',
            destination: $basePath . '/bootstrap/app.php',
        );

        /*
        |--------------------------------------------------------------------------
        | Remove Laravel Structures Replaced by MCF
        |--------------------------------------------------------------------------
        */

        $this->removeLaravelDirectories(
            $basePath,
        );

        /*
        |--------------------------------------------------------------------------
        | Installation Marker
        |--------------------------------------------------------------------------
        |
        | The marker is created only after all installation operations
        | have completed successfully.
        |
        */

        $this->createInstallationMarker(
            $basePath,
        );
    }

    /**
     * Create a backup of all files and directories affected by MCF.
     *
     * The backup contains only affected Laravel structures.
     * It is not a complete project backup and is not an automatic rollback.
     */
    protected function createBackup(
        string $basePath,
        string $sourcePath,
        string $backupPath,
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Directories completely replaced or removed
        |--------------------------------------------------------------------------
        */

        $directories = [
            'app/Models',
            'app/Http/Controllers',
            'app/Http/Requests',
            'app/Http/Middleware',
            'app/Notifications',
            'database',
            'routes',
        ];

        foreach ($directories as $directory) {
            $this->backupDirectoryIfExists(
                basePath: $basePath,
                backupPath: $backupPath,
                relativePath: $directory,
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Files replaced by MCF
        |--------------------------------------------------------------------------
        */

        $files = [
            'bootstrap/app.php',
            'config/filesystems.php',
            'config/mail.php',
            'config/queue.php',
        ];

        foreach ($files as $file) {
            $this->backupFileIfExists(
                basePath: $basePath,
                backupPath: $backupPath,
                relativePath: $file,
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MCF Views
        |--------------------------------------------------------------------------
        |
        | Only existing files that MCF will overwrite are backed up.
        |
        */

        $this->backupOverwrittenViewFiles(
            basePath: $basePath,
            sourcePath: $sourcePath,
            backupPath: $backupPath,
        );
    }

    /**
     * Backup an existing directory.
     */
    protected function backupDirectoryIfExists(
        string $basePath,
        string $backupPath,
        string $relativePath,
    ): void {
        $source = $basePath
            . DIRECTORY_SEPARATOR
            . $relativePath;

        if (! $this->files->isDirectory($source)) {
            return;
        }

        $destination = $backupPath
            . DIRECTORY_SEPARATOR
            . $relativePath;

        $this->files->ensureDirectoryExists(
            dirname($destination),
        );

        $this->files->copyDirectory(
            $source,
            $destination,
        );
    }

    /**
     * Backup an existing file.
     */
    protected function backupFileIfExists(
        string $basePath,
        string $backupPath,
        string $relativePath,
    ): void {
        $source = $basePath
            . DIRECTORY_SEPARATOR
            . $relativePath;

        if (! $this->files->isFile($source)) {
            return;
        }

        $destination = $backupPath
            . DIRECTORY_SEPARATOR
            . $relativePath;

        $this->files->ensureDirectoryExists(
            dirname($destination),
        );

        $this->files->copy(
            $source,
            $destination,
        );
    }
/**
 * Backup only existing application view files that MCF will overwrite.
 */
    protected function backupOverwrittenViewFiles(
        string $basePath,
        string $sourcePath,
        string $backupPath,
    ): void {
        $sourceViews = $sourcePath
            . DIRECTORY_SEPARATOR
            . 'resources'
            . DIRECTORY_SEPARATOR
            . 'views';

        if (! $this->files->isDirectory($sourceViews)) {
            throw new RuntimeException(
                'Required MCF views directory was not found: '
                . $sourceViews,
            );
        }

        $destinationViews = $basePath
            . DIRECTORY_SEPARATOR
            . 'resources'
            . DIRECTORY_SEPARATOR
            . 'views';

        foreach ($this->files->allFiles($sourceViews) as $file) {
            $relativePath = $file->getRelativePathname();

            $existingFile = $destinationViews
                . DIRECTORY_SEPARATOR
                . $relativePath;

            if (! $this->files->isFile($existingFile)) {
                continue;
            }

            $backupFile = $backupPath
                . DIRECTORY_SEPARATOR
                . 'resources'
                . DIRECTORY_SEPARATOR
                . 'views'
                . DIRECTORY_SEPARATOR
                . $relativePath;

            $this->files->ensureDirectoryExists(
                dirname($backupFile),
            );

            $this->files->copy(
                $existingFile,
                $backupFile,
            );
        }
    }

    /**
     * Get the MCF Laravel source directory.
     */
    protected function sourcePath(): string
    {
        return dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'mcf_laravel';
    }

    /**
     * Validate that the MCF Laravel source exists.
     */
    protected function validateSource(
        string $sourcePath,
    ): void {
        if (! $this->files->isDirectory($sourcePath)) {
            throw new RuntimeException(
                'The MCF Laravel source directory was not found: '
                . $sourcePath,
            );
        }
    }

    /**
     * Copy a directory recursively.
     *
     * Existing files outside the MCF source structure remain untouched.
     */
    protected function copyDirectory(
        string $source,
        string $destination,
    ): void {
        if (! $this->files->isDirectory($source)) {
            throw new RuntimeException(
                'Required MCF directory was not found: '
                . $source,
            );
        }

        $this->files->ensureDirectoryExists(
            $destination,
        );

        $this->files->copyDirectory(
            $source,
            $destination,
        );
    }

    /**
     * Replace a directory completely.
     */
    protected function replaceDirectory(
        string $source,
        string $destination,
    ): void {
        if (! $this->files->isDirectory($source)) {
            throw new RuntimeException(
                'Required MCF directory was not found: '
                . $source,
            );
        }

        if ($this->files->isDirectory($destination)) {
            $this->files->deleteDirectory(
                $destination,
            );
        }

        $this->files->copyDirectory(
            $source,
            $destination,
        );
    }

    /**
     * Copy a single file.
     */
    protected function copyFile(
        string $source,
        string $destination,
    ): void {
        if (! $this->files->isFile($source)) {
            throw new RuntimeException(
                'Required MCF file was not found: '
                . $source,
            );
        }

        $directory = dirname(
            $destination,
        );

        $this->files->ensureDirectoryExists(
            $directory,
        );

        $this->files->copy(
            $source,
            $destination,
        );
    }

    /**
     * Remove Laravel directories replaced by MCF.
     *
     * Missing directories are ignored.
     */
    protected function removeLaravelDirectories(
        string $basePath,
    ): void {
        $directories = [
            'routes',
            'app/Http/Controllers',
            'app/Http/Requests',
            'app/Http/Middleware',
            'app/Notifications',
        ];

        foreach ($directories as $directory) {
            $path = $basePath
                . DIRECTORY_SEPARATOR
                . $directory;

            if (! $this->files->isDirectory($path)) {
                continue;
            }

            $this->files->deleteDirectory(
                $path,
            );
        }
    }

    /**
     * Get the installation backup directory.
     */
    protected function backupPath(
        string $basePath,
    ): string {
        return $basePath
            . DIRECTORY_SEPARATOR
            . 'z_backup';
    }

    /**
     * Get the installation marker path.
     */
    protected function installationMarkerPath(
        string $basePath,
    ): string {
        return $basePath
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'MCF'
            . DIRECTORY_SEPARATOR
            . '.mcf-installed';
    }

    /**
     * Determine whether MCF has already been installed.
     */
    protected function isInstalled(
        string $basePath,
    ): bool {
        return $this->files->isFile(
            $this->installationMarkerPath(
                $basePath,
            ),
        );
    }

    /**
     * Create the MCF installation marker.
     */
    protected function createInstallationMarker(
        string $basePath,
    ): void {
        $markerPath = $this->installationMarkerPath(
            $basePath,
        );

        $this->files->put(
            $markerPath,
            'MCF installed successfully.'
            . PHP_EOL,
        );
    }
}
