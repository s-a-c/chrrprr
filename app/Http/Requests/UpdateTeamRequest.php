<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTeamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[][]
     *
     * @psalm-return array{name: list{'required'}, bio: list{'nullable', 'string', 'max:10000'}}
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'bio' => ['nullable', 'string', 'max:10000'], // Soft limit: 10,000 characters (tenant-configurable default, hard limit 50K)
        ];
    }
}
