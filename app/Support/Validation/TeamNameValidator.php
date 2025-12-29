<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Models\Team;
use App\Services\TeamNameNormalizationService;
use App\Services\TeamTypeResolutionService;
use App\Support\Validation\TeamName\ArrayNameQueryBuilder;
use App\Support\Validation\TeamName\NameQueryBuilderInterface;
use App\Support\Validation\TeamName\StringNameQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final readonly class TeamNameValidator
{
    public function __construct(
        private TeamNameNormalizationService $normalizationService,
        private TeamTypeResolutionService $typeResolutionService,
    ) {}

    /**
     * Validate that the team name is unique among siblings.
     *
     * Simplified using collection-based services and strategy pattern.
     */
    public function validateUnique(Team $team): void
    {
        $type = $this->typeResolutionService->resolve($team);
        $query = $this->buildSiblingQuery($team, $type);
        $nameToCheck = $this->normalizationService->normalize($team);

        // Use strategy pattern with collection-based builders
        $builder = $this->getQueryBuilder($nameToCheck);
        $builder->applyConstraints($query, $nameToCheck, $team);

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => ['The team name has already been taken within this scope.'],
            ]);
        }
    }

    /**
     * Get the appropriate query builder based on name type.
     */
    private function getQueryBuilder(array|string $name): NameQueryBuilderInterface
    {
        return is_array($name)
            ? new ArrayNameQueryBuilder()
            : new StringNameQueryBuilder();
    }

    /**
     * Build the base query for finding sibling teams.
     */
    private function buildSiblingQuery(Team $team, ?string $type): Builder
    {
        return Team::query()
            ->withoutGlobalScopes()
            ->where('parent_id', $team->parent_id)
            ->where('type', $type)
            ->where('id', '!=', $team->id ?? 0)
            ->whereNull('deleted_at');
    }
}
