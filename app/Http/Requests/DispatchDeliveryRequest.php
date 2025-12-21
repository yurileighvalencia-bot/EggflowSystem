<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DispatchDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $delivery = $this->route('delivery');
        return $this->user()->can('dispatch', $delivery);
    }

    public function rules(): array
    {
        return [
            'vehicle_number' => ['nullable', 'string', 'max:20'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'driver_phone' => ['nullable', 'string', 'max:20'],
            'estimated_arrival' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
