<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEggCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-categories');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:egg_categories,name'],
            'code' => ['required', 'string', 'max:10', 'unique:egg_categories,code'],
            'description' => ['nullable', 'string', 'max:500'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'restock_quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'default_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.unique' => 'This category name already exists.',
            'code.required' => 'Category code is required.',
            'code.unique' => 'This category code already exists.',
            'low_stock_threshold.required' => 'Low stock threshold is required.',
            'restock_quantity.required' => 'Restock quantity is required.',
        ];
    }
}
