<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EnsureEducitySchema extends Command
{
    protected $signature = 'educity:ensure-schema';

    protected $description = 'Create the validated, non-public PostgreSQL schema used by Educity.';

    public function handle(): int
    {
        $schema = (string) config('database.connections.pgsql.search_path');

        if (! preg_match('/^[A-Za-z0-9_]+$/', $schema) || strtolower($schema) === 'public') {
            throw new RuntimeException('DB_SCHEMA must be a non-public PostgreSQL schema identifier containing only letters, digits, and underscores.');
        }

        // The allowlist above makes interpolation safe; PDO creates only this schema.
        DB::connection('pgsql')->getPdo()->exec(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $schema));

        $this->info("Ensured PostgreSQL schema [{$schema}].");

        return self::SUCCESS;
    }
}
