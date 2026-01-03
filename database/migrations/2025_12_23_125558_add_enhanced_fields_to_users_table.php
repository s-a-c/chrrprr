<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->char('ulid', 26)->nullable()->unique()->after('id');
            $table->string('state')->nullable()->after('ulid');
            $table->string('status')->nullable()->after('state');
            $table->jsonb('slug')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropColumn(['ulid', 'state', 'status', 'slug']);
        });
    }
};
