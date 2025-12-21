<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-collection');
    }

    public function rules(): array
    {
        return [
            'batch_id' => ['required', 'exists:batches,id'],
            'egg_category_id' => ['required', 'exists:egg_categories,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'collection_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'batch_id.required' => 'Please select a batch.',
            'batch_id.exists' => 'The selected batch does not exist.',
            'egg_category_id.required' => 'Please select an egg category.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'Quantity cannot exceed 100,000.',
        ];
    }
}
