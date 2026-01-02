<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teams', static function (Blueprint $table): void {
            // Add approval settings columns (only applicable to Enterprise type)
            // These are stored on the teams table but only used when type = 'enterprise'
            if (! Schema::hasColumn('teams', 'move_approval_descendant_threshold')) {
                $table->integer('move_approval_descendant_threshold')->default(10)->after('lock_version');
            }

            if (! Schema::hasColumn('teams', 'move_approval_depth_change_threshold')) {
                $table
                    ->integer('move_approval_depth_change_threshold')
                    ->default(2)
                    ->after('move_approval_descendant_threshold');
            }

            if (! Schema::hasColumn('teams', 'move_approval_require_cross_org')) {
                $table
                    ->boolean('move_approval_require_cross_org')
                    ->default(true)
                    ->after('move_approval_depth_change_threshold');
            }

            if (! Schema::hasColumn('teams', 'bulk_operation_batch_size')) {
                $table->integer('bulk_operation_batch_size')->default(500)->after('move_approval_require_cross_org');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', static function (Blueprint $table): void {
            $table->dropColumn([
                'move_approval_descendant_threshold',
                'move_approval_depth_change_threshold',
                'move_approval_require_cross_org',
                'bulk_operation_batch_size',
            ]);
        });
    }
};
