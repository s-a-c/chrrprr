<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Override;
use Spatie\Permission\Models\Role as SpatieRole;

final class Role extends SpatieRole
{
    use HasFactory;

    // Allow mass assignment for the new column
    /** @var list<string> */
    protected $fillable = ['name', 'guard_name', 'is_key', 'team_id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_key' => 'boolean',
        ];
    }
}
