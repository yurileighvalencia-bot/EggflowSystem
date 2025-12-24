<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $targetUser = $this->route('user');

        // Allow users to update themselves
        if ($this->user()->id === $targetUser->id) {
            return true;
        }

        return $this->user()->can('manage-users');
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ];

        // Only managers can update farm_id, shop_id, and is_active
        if ($this->user()->can('manage-users')) {
            $rules['farm_id'] = ['nullable', 'exists:farms,id'];
            $rules['shop_id'] = ['nullable', 'exists:shops,id'];
            $rules['is_active'] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A user with this email already exists.',
        ];
    }
}
