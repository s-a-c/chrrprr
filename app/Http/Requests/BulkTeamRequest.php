<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TeamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * DTO for bulk team operations.
 *
 * Supports both DTO format (type-safe) and array format (backward compatibility).
 */
final class BulkTeamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'teams' => ['required', 'array', 'min:1'],
            'teams.*.name' => ['required', 'string'],
            'teams.*.type' => ['required', new Enum(TeamType::class)],
            'teams.*.parent_id' => [
                'nullable',
                'integer',
                Rule::exists('teams', 'id')->whereNull('deleted_at'),
            ],
            'teams.*.id' => [
                'nullable',
                'integer',
                Rule::exists('teams', 'id')->whereNull('deleted_at'),
            ],
            'teams.*.lock_version' => ['nullable', 'integer'],
            'teams.*.bio' => ['nullable', 'string', 'max:50000'],
        ];
    }

    /**
     * Get the validated teams data.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTeams(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return $validated['teams'] ?? [];
    }
}
