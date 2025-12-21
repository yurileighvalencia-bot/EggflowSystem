<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-batch');
    }

    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'exists:farms,id'],
            'collection_date' => ['required', 'date', 'before_or_equal:today'],
            'expires_at' => ['nullable', 'date', 'after:collection_date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'collection_date.before_or_equal' => 'Collection date cannot be in the future.',
            'expires_at.after' => 'Expiry date must be after collection date.',
        ];
    }
}
