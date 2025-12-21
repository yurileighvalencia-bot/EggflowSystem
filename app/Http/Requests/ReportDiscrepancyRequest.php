<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportDiscrepancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('report-discrepancy');
    }

    public function rules(): array
    {
        return [
            'delivery_id' => ['required', 'exists:deliveries,id'],
            'delivery_item_id' => ['nullable', 'exists:delivery_items,id'],
            'qty_sent' => ['required', 'integer', 'min:0'],
            'qty_received' => ['required', 'integer', 'min:0'],
            'qty_rejected' => ['nullable', 'integer', 'min:0'],
            'notes' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Please describe the discrepancy.',
            'notes.min' => 'Description must be at least 10 characters.',
        ];
    }
}
