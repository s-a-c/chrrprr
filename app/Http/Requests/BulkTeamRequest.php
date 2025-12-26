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
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'teams' => ['required', 'array'],
            'teams.*.name' => ['required', 'string'],
            'teams.*.type' => ['required', new Enum(TeamType::class)],
            'teams.*.parent_id' => ['nullable', 'integer', Rule::exists('teams', 'id')->whereNull('deleted_at')],
            'teams.*.bio' => ['nullable', 'string', 'max:10000'],
            'teams.*.id' => ['nullable', 'integer', Rule::exists('teams', 'id')->whereNull('deleted_at')], // For updates
        ];
    }

    /**
     * Get the validated teams data.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTeams(): array
    {
        return $this->validated()['teams'];
    }
}
