<?php

namespace App\Http\Requests;

use App\Models\WastageLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWastageLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('log-wastage');
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['nullable', 'exists:shops,id'],
            'batch_id' => ['required', 'exists:batches,id'],
            'egg_category_id' => ['required', 'exists:egg_categories,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'source' => ['required', Rule::in(array_keys(WastageLog::SOURCES))],
            'delivery_id' => ['required_if:source,delivery_rejection', 'nullable', 'exists:deliveries,id'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please provide a reason for the wastage.',
            'reason.min' => 'Reason must be at least 10 characters.',
            'delivery_id.required_if' => 'Delivery ID is required for delivery rejection wastage.',
        ];
    }
}
