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
        Schema::create('user_organisation_access', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('organisation_id');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->foreign('organisation_id')->references('id')->on('teams')->cascadeOnDelete();
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
