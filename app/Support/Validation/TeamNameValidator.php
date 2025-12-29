<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Enums\TeamType;
use App\Models\Department;
use App\Models\Division;
use App\Models\Enterprise;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class TeamNameValidator
{
    /**
     * Validate that the team name is unique among siblings.
     */
    public function validateUnique(Team $team): void
    {
        $type = $this->getTeamType($team);
        $query = $this->buildSiblingQuery($team, $type);
        $nameToCheck = $this->normalizeNameForValidation($team);

        if (is_array($nameToCheck)) {
            $this->applyArrayNameConstraints($query, $nameToCheck);

            return;
        }

        $this->applyStringNameConstraints($query, $nameToCheck, $team);

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => ['The team name has already been taken within this scope.'],
            ]);
        }
    }

    /**
     * Normalize the team name for validation purposes.
     *
     * @return array<string, string>|string
     */
    private function normalizeNameForValidation(Team $team): array|string
    {
        $nameRaw = $team->attributes['name'] ?? null;
        if ($this->isValidStringName($nameRaw)) {
            return $this->processStringName($nameRaw);
        }

        return $this->getTeamNameFallback($team);
    }

    /**
     * Check if the name is a valid string.
     */
    private function isValidStringName(mixed $nameRaw): bool
    {
        return is_string($nameRaw) && $nameRaw !== '';
    }

    /**
     * Process a string name, potentially decoding JSON.
     *
     * @return array<string, string>|string
     */
    private function processStringName(string $nameRaw): array|string
    {
        $decoded = json_decode($nameRaw, true);
        if (is_array($decoded)) {
            return $this->extractStringPairs($decoded);
        }

        return $nameRaw;
    }

    /**
     * Get team name fallback value.
     */
    private function getTeamNameFallback(Team $team): string
    {
        $name = $team->name;

        return is_string($name) ? $name : '';
    }

    /**
     * Extract string key-value pairs from decoded JSON.
     *
     * @param  array<mixed>  $decoded
     * @return array<string, string>
     */
    private function extractStringPairs(array $decoded): array
    {
        $result = [];
        foreach ($decoded as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            if (! is_string($value)) {
                continue;
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Get the team type from various sources.
     */
    private function getTeamType(Team $team): ?string
    {
        $type = $team->type ?? $team->getAttribute('type');

        if ($type instanceof TeamType) {
            return $type->value;
        }

        if (is_string($type)) {
            return $type;
        }

        return $this->getTypeFromModel($team);
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

    /**
     * Apply name constraints for array-based names.
     *
     * @param  array<string, string>  $nameToCheck
     */
    private function applyArrayNameConstraints(Builder $query, array $nameToCheck): void
    {
        $query->where(static function (Builder $q) use ($nameToCheck): void {
            foreach ($nameToCheck as $locale => $value) {
                if ($value === null) {
                    continue;
                }
                if ($value === '') {
                    continue;
                }
                $q->orWhere("name->{$locale}", $value);
            }
        });
    }

    /**
     * Apply name constraints for string-based names.
     */
    private function applyStringNameConstraints(Builder $query, string $nameToCheck, Team $team): void
    {
        $stringValue = $this->getStringValue($nameToCheck, $team);
        if ($stringValue === '') {
            return;
        }

        $this->applyStringValueConstraints($query, $stringValue);
    }

    /**
     * Get the string value for validation.
     */
    private function getStringValue(string $nameToCheck, Team $team): string
    {
        if ($nameToCheck !== '') {
            return $nameToCheck;
        }

        return is_string($team->name) ? $team->name : '';
    }

    /**
     * Apply constraints for a string value across locales.
     */
    private function applyStringValueConstraints(Builder $query, string $stringValue): void
    {
        $query->where(static function (Builder $q) use ($stringValue): void {
            foreach (['en', 'es', 'fr', 'de'] as $locale) {
                $q->orWhere("name->{$locale}", $stringValue);
            }
            $q->orWhereJsonContains('name', $stringValue);
        });
    }

    /**
     * Get the type from the model class TeamNameValidator not set as attribute.
     */
    private function getTypeFromModel(Team $team): ?string
    {
        // Try to get type from the model's class TeamNameValidator via Parental
        $className = $team::class;
        $typeMap = [
            Enterprise::class => 'enterprise',
            Organisation::class => 'organisation',
            Division::class => 'division',
            Department::class => 'department',
            Project::class => 'project',
        ];

        return $typeMap[$className] ?? null;
    }
}
