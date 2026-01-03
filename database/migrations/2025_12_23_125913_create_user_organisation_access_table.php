<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get schema name for foreign key references
        $schema = Config::get('database.connections.pgsql.schema');
        if ($schema && str_contains((string) $schema, '${APP_ID}')) {
            $appId = env('APP_ID', 'chrrprr');
            $schema = str_replace('${APP_ID}', $appId, $schema);
        }
        if (empty($schema) || $schema === '${APP_ID}') {
            $schema = env('APP_ID', 'chrrprr') ?: 'public';
        }

        $usersTable = ($schema && $schema !== 'public') ? "{$schema}.users" : 'users';
        $teamsTable = ($schema && $schema !== 'public') ? "{$schema}.teams" : 'teams';

        Schema::create('user_organisation_access', static function (Blueprint $table) use ($usersTable, $teamsTable): void {
            $table->id();
            $table->foreignId('user_id')->constrained($usersTable)->cascadeOnDelete();
            $table->unsignedBigInteger('organisation_id');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->foreign('organisation_id')->references('id')->on($teamsTable)->cascadeOnDelete();
            $table->unique(['user_id', 'organisation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_organisation_access');
    }
};
