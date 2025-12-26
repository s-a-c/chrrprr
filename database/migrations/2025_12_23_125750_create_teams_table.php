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
        Schema::create('teams', static function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->json('name');
            $table->json('slug');
            $table->string('type')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('state')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('teams')->onDelete('cascade');

            // $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
