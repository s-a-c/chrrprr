<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('database migrations run successfully', function (): void {
    // RefreshDatabase trait MigrationsTest runs migrations.
    // We explicitly check if specific tables exist to verify.

    $tables = [
        'users',
        'teams',
        'domains',
        'user_enterprise',
        'user_organisation_access',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Table {$table} does not exist");
    }
});
