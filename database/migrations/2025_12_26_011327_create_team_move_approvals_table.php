<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('team_move_approvals')) {
            return;
        }

        // Get schema name for foreign key references
        $schema = Config::get('database.connections.pgsql.schema');
        if ($schema && str_contains((string) $schema, '${APP_ID}')) {
            $appId = env('APP_ID', 'chrrprr');
            $schema = str_replace('${APP_ID}', $appId, $schema);
        }
        if (empty($schema) || $schema === '${APP_ID}') {
            $schema = env('APP_ID', 'chrrprr') ?: 'public';
        }

        $teamsTable = ($schema && $schema !== 'public') ? "{$schema}.teams" : 'teams';
        $usersTable = ($schema && $schema !== 'public') ? "{$schema}.users" : 'users';

        Schema::create('team_move_approvals', static function (Blueprint $table) use ($teamsTable, $usersTable): void {
            $table->id();
            $table->foreignId('team_id')->constrained($teamsTable)->cascadeOnDelete();
            $table->foreignId('from_parent_id')->nullable()->constrained($teamsTable)->nullOnDelete();
            $table->foreignId('to_parent_id')->nullable()->constrained($teamsTable)->nullOnDelete();
            $table->foreignId('requested_by_id')->constrained($usersTable)->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->json('required_approvers')->nullable(); // Array of user IDs who must approve
            $table->json('approvals')->nullable(); // Array of approval records: {user_id, approved_at, status: 'approved'|'rejected'}
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by_id')->nullable()->constrained($usersTable)->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['requested_by_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_move_approvals');
    }
};
