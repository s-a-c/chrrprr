<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\SchemaScopedModel;
use App\Models\Concerns\HasCustomSchema;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Override;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @use HasFactory<Factory>
 */
final class Role extends SpatieRole implements SchemaScopedModel
{
    use HasCustomSchema;
    use HasFactory;

    // Allow mass assignment for the new column
    protected $fillable = ['name', 'guard_name', 'is_key', 'team_id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return string[]
     *
     * @psalm-return array{is_key: 'boolean'}
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_key' => 'boolean',
        ];
    }
}
