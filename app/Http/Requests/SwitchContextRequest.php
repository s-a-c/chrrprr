<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Override;

final class SwitchContextRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return true
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in component/controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return (Exists|string)[][]
     *
     * @psalm-return array{organisation_id: list{'required', 'integer', Exists}}
     */
    public function rules(): array
    {
        return [
            'organisation_id' => [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where('type', 'organisation')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return string[]
     *
     * @psalm-return array{'organisation_id.required': 'Please select an organisation.', 'organisation_id.exists': 'The selected organisation does not exist or is invalid.'}
     */
    #[Override]
    public function messages(): array
    {
        return [
            'organisation_id.required' => 'Please select an organisation.',
            'organisation_id.exists' => 'The selected organisation does not exist or is invalid.',
        ];
    }
}
