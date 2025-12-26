<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TeamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreTeamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return (Enum|string)[][]
     *
     * @psalm-return array{name: list{'required'}, type: list{'required', Enum}, parent_id: list{'nullable', 'exists:teams,id'}, bio: list{'nullable', 'string', 'max:1000'}}
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'type' => ['required', new Enum(TeamType::class)],
            'parent_id' => ['nullable', 'exists:teams,id'],
            'bio' => ['nullable', 'string', 'max:10000'], // Soft limit: 10,000 characters (tenant-configurable default, hard limit 50K)
        ];
    }
}
