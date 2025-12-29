<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TeamType;
use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Team;

/**
 * Service for resolving team types using collection lookups.
 */
final readonly class TeamTypeResolutionService
{
    private const array TYPE_MAP = [
        Enterprise::class => 'enterprise',
        Organisation::class => 'organisation',
        Division::class => 'division',
        Department::class => 'department',
        Project::class => 'project',
    ];

    /**
     * Resolve team type using collection pipeline.
     */
    public function resolve(Team $team): ?string
    {
        $type = $team->type ?? $team->getAttribute('type');

        // Use collection pipeline instead of nested if/else
        return collect([
            static fn () => $type instanceof TeamType ? $type->value : null,
            static fn (): ?string => is_string($type) ? $type : null,
            fn (): ?string => $this->getTypeFromModel($team),
        ])
            ->map(static fn (callable $resolver): ?string => $resolver())
            ->filter()
            ->first();
    }

    /**
     * Get type from model class using collection lookup.
     */
    private function getTypeFromModel(Team $team): ?string
    {
        return collect(self::TYPE_MAP)
            ->get($team::class);
    }
}
