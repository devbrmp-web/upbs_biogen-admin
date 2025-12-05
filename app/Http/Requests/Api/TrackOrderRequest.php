<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TrackOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $tracking = $this->route('tracking_number');
        if ($tracking && !$this->has('tracking_number')) {
            $this->merge(['tracking_number' => $tracking]);
        }
    }

    public function rules(): array
    {
        return [
            'tracking_number' => [
                'sometimes',
                'required_without_all:order_code,phone',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9._-]+$/',
            ],
            'order_code' => [
                'sometimes',
                'required_without_all:tracking_number,phone',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9._-]+$/',
            ],
            'phone' => [
                'sometimes',
                'required_without_all:tracking_number,order_code',
                'string',
                'max:20',
                'regex:/^[\+0-9\-\(\)\s]+$/',
            ],
        ];
    }
}
