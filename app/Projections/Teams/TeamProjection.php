<?php

declare(strict_types=1);

namespace App\Projections\Teams;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Team Projection Read Model.
 *
 * This class represents a read-optimized projection of team data.
 * Currently, the Team model serves as both the write model and read model.
 *
 * Future implementations might use this class for:
 * - Denormalized read tables optimized for specific queries
 * - Materialized views
 * - Cached snapshots
 *
 * Example usage (if separate projection table is created):
 *
 * ```php
 * // In a migration
 * Schema::create('team_projections', function (Blueprint $table) {
 *     $table->id();
 *     $table->string('ulid')->unique();
 *     $table->json('name');
 *     $table->string('type');
 *     $table->json('hierarchy_path'); // Denormalized parent path
 *     $table->timestamps();
 * });
 * ```
 */
final class TeamProjection extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * Currently unused - Team model serves as the projection.
     * Uncomment if creating a separate projection table.
     *
     * @var string
     */
    // protected $table = 'team_projections';
}
