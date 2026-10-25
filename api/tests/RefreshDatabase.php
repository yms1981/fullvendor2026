<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase as RefreshDatabaseBase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;

trait RefreshDatabase
{
    use RefreshDatabaseBase;

    /**
     *  Define if test will make cache of database migrations. This is an
     * 'switch' button to enable or disable the cache.
     *
     * @var bool
     */
    protected $useDatabaseSchemaCache = true;

    /**
     * Refresh a conventional test database.
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        if (!RefreshDatabaseState::$migrated && !$this->isDatabaseSchemaAlreadyMigrated()) {
            $this->artisan('migrate:fresh', [
                '--drop-views' => $this->shouldDropViews(),
                '--drop-types' => $this->shouldDropTypes(),
            ]);

            $this->app[Kernel::class]->setArtisan(null);

            $this->setDatabaseSchemaAsAlreadyMigrated();
            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    /**
     * Verify if local database is already migrated according migrations folder
     *
     * @return bool
     */
    private function isDatabaseSchemaAlreadyMigrated(): bool
    {
        if (!$this->useDatabaseSchemaCache) {
            return false;
        }

        $cache = app('cache')->driver('file');

        $migrationsFolderHash = $this->getMigrationsFolderHash();
        return ($cache->get('migrations-folder-last-hash') === $migrationsFolderHash);
    }

    /**
     * Define database as already migration according migrations folder
     *
     * @return void
     */
    private function setDatabaseSchemaAsAlreadyMigrated(): void
    {
        $cache = app('cache')->driver('file');

        $migrationsFolderHash = $this->getMigrationsFolderHash();
        $cache->put('migrations-folder-last-hash', $migrationsFolderHash);
    }

    /**
     * Generates a hash of database migrations folder.
     *
     * @return string
     */
    private function getMigrationsFolderHash()
    {
        static $migrationsFolderHash;

        if (!$migrationsFolderHash) {
            $migrationsFolder = base_path('/database/migrations');
            $migrationsFolderHash = $this->hashDirectory($migrationsFolder);
        }

        return $migrationsFolderHash;
    }

    /**
     * Generate a hash from directory
     *
     * @param  string  $directory
     * @return string
     */
    private function hashDirectory(string $directory): string
    {
        if (!is_dir($directory)) {
            return false;
        }

        $hash = '';
        $dir = dir($directory);

        while (false !== ($file = $dir->read())) {
            if (!($file != '.' && $file != '..')) {
                continue;
            }

            $localHash = is_dir($directory.'/'.$file)
                ? static::hashDirectory($directory.'/'.$file)
                : md5_file($directory.'/'.$file);

            $hash = md5($localHash.$hash);
        }

        $dir->close();

        return $hash;
    }
}
