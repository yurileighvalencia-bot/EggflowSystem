<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit-collection');
    }

    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'required', 'integer', 'min:1', 'max:100000'],
            'collection_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
            'revision_reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'revision_reason.required' => 'Please provide a reason for this revision.',
            'revision_reason.min' => 'Revision reason must be at least 10 characters.',
        ];
    }
}
