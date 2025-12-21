<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestigateDiscrepancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('investigate-discrepancy');
    }

    public function rules(): array
    {
        return [
            'resolution' => ['required', Rule::in(['accepted', 'rejected', 'partial', 'pending_further_review'])],
            'resolution_notes' => ['required', 'string', 'min:10', 'max:1000'],
            'adjustment_quantity' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'resolution.required' => 'Please select a resolution.',
            'resolution_notes.required' => 'Please provide investigation notes.',
            'resolution_notes.min' => 'Notes must be at least 10 characters.',
        ];
    }
}
