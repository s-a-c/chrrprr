<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get tenant-configurable soft limit (default 10K, hard limit 50K)
        $softLimit = config('app.user_bio_soft_limit', 10000);
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
     * @return array<string, string>
     */
    public function messages(): array
    {
        $softLimit = config('app.user_bio_soft_limit', 10000);
        $hardLimit = 50000;

        return [
            'bio.max' => "The bio may not be greater than {$hardLimit} characters. Recommended limit is {$softLimit} characters.",
        ];
    }
}
