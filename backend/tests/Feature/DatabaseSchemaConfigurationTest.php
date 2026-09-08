<?php

namespace Tests\Feature;

use App\Console\Commands\EnsureEducitySchema;
use RuntimeException;
use Tests\TestCase;

class DatabaseSchemaConfigurationTest extends TestCase
{
    public function test_postgresql_search_path_uses_the_schema_environment_variable(): void
    {
        putenv('DB_SCHEMA=educity_test');

        try {
            $databaseConfig = require config_path('database.php');

            $this->assertSame('educity_test', $databaseConfig['connections']['pgsql']['search_path']);
        } finally {
            putenv('DB_SCHEMA');
        }
    }

    public function test_schema_command_rejects_the_public_schema_before_connecting(): void
    {
        config()->set('database.connections.pgsql.search_path', 'public');

        $this->expectException(RuntimeException::class);
        app(EnsureEducitySchema::class)->handle();
    }
}
