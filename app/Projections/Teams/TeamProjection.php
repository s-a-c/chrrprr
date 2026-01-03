<?php

declare(strict_types=1);

namespace App\Projections\Teams;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * Get the related Team model.
     *
     * In the current architecture, the Team model itself is the projection.
     * This method demonstrates how to link a projection to the source model.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
