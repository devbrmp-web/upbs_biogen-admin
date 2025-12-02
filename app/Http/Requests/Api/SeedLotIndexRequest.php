<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SeedLotIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variety_id' => ['nullable', 'integer', 'min:1'],
            'variety_slug' => ['nullable', 'string', 'max:255'],
            'seed_class_id' => ['nullable', 'integer', 'min:1'],
            'seed_class_code' => ['nullable', 'string', 'max:10'],
            'production_year' => ['nullable', 'integer', 'min:1900', 'max:' . (int) now()->year],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'sellable_only' => ['nullable', 'boolean'],
        ];
    }
}

