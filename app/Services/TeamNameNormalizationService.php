<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;

/**
 * Service for normalizing team names using collection pipelines.
 */
final readonly class TeamNameNormalizationService
{
    /**
     * Normalize the team name for validation purposes.
     *
     * Uses collection pipelines to replace nested conditionals.
     *
     * @return array<string, string>|string
     */
    public function normalize(Team $team): array|string
    {
        $nameRaw = $team->attributes['name'] ?? null;

        // Use collection pipeline instead of nested if/else
        return collect([$nameRaw])
            ->filter($this->isValidStringName(...))
            ->map($this->processStringName(...))
            ->first() ?? $this->getTeamNameFallback($team);
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
     * Uses collection to handle array extraction.
     *
     * @return array<string, string>|string
     */
    private function processStringName(string $nameRaw): array|string
    {
        $decoded = json_decode($nameRaw, true);

        return is_array($decoded)
            ? $this->extractStringPairs($decoded)
            : $nameRaw;
    }

    /**
     * Extract string key-value pairs from decoded JSON using collections.
     *
     * Replaces foreach loop with collection filter/map pipeline.
     *
     * @param  array<mixed>  $decoded
     * @return array<string, string>
     */
    private function extractStringPairs(array $decoded): array
    {
        return collect($decoded)
            ->filter(static fn ($value, $key): bool => is_string($key) && is_string($value))
            ->toArray();
    }

    /**
     * Get team name fallback value.
     */
    private function getTeamNameFallback(Team $team): string
    {
        $name = $team->name;

        return is_string($name) ? $name : '';
    }
}
