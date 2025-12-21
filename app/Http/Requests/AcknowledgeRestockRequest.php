<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeRestockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('acknowledge-restock');
    }

    public function rules(): array
    {
        return [
            'estimated_dispatch_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
