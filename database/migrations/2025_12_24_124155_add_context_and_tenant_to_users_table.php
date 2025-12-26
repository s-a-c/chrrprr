<?php

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
            $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('remember_token');
            $table->unsignedBigInteger('current_context_id')->nullable()->index()->after('tenant_id');

            $table->foreign('tenant_id')->references('id')->on('teams')->nullOnDelete();
            $table->foreign('current_context_id')->references('id')->on('teams')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['current_context_id']);
            $table->dropColumn(['tenant_id', 'current_context_id']);
        });
    }
};
