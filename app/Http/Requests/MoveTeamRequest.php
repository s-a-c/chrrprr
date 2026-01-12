<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

final class MoveTeamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return (Exists|string)[][]
     *
     * @psalm-return array{parent_id: list{'nullable', 'integer', Exists}, reason: list{'nullable', 'string', 'max:1000'}}
     */
    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('teams', 'id')->whereNull('deleted_at'),
            ],
            'reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
