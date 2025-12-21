<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEggCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-categories');
    }

    public function rules(): array
    {
        $category = $this->route('category');
        
        return [
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('egg_categories')->ignore($category)],
            'code' => ['sometimes', 'string', 'max:10', Rule::unique('egg_categories')->ignore($category)],
            'description' => ['nullable', 'string', 'max:500'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'restock_quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'default_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This category name already exists.',
            'code.unique' => 'This category code already exists.',
            'low_stock_threshold.min' => 'Threshold cannot be negative.',
        ];
    }
}
