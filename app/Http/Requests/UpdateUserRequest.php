<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

final class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return true
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[][]
     *
     * @psalm-return array{name: list{'sometimes', 'required', 'string', 'max:255'}, email: list{'sometimes', 'required', 'string', 'email', 'max:255'}, bio: list{'nullable', 'string', 'max:50000'}}
     */
    public function rules(): array
    {
        // Get tenant-configurable soft limit (default 10K, hard limit 50K)
        config('app.user_bio_soft_limit', 10000);
        $hardLimit = 50000; // Hard limit

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
            'bio' => [
                'nullable',
                'string',
                "max:{$hardLimit}", // Hard limit enforced by validation
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return string[]
     *
     * @psalm-return array{'bio.max': string}
     */
    #[Override]
    public function messages(): array
    {
        $softLimit = config('app.user_bio_soft_limit', 10000);
        $hardLimit = 50000;

        return [
            'bio.max' => "The bio may not be greater than {$hardLimit} characters. Recommended limit is {$softLimit} characters.",
        ];
    }
}
