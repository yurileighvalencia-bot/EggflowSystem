<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit-batch');
    }

    public function rules(): array
    {
        $batch = $this->route('batch');
        
        return [
            'expires_at' => [
                'nullable',
                'date',
                'after:' . $batch->collection_date->toDateString(),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'expires_at.after' => 'Expiry date must be after the collection date.',
        ];
    }
}
