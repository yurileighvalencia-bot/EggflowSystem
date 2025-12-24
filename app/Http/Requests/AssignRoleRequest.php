<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-users');
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'in:farm_staff,shop_staff,manager,customer'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Invalid role. Must be one of: farm_staff, shop_staff, manager, customer.',
        ];
    }
}
