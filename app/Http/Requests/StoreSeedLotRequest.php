<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SeedClass;

class StoreSeedLotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_sellable' => $this->has('is_sellable'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'variety_id' => 'required|exists:varieties,id',
            'seed_class_id' => 'required|exists:seed_classes,id',
            'lot_code' => 'required|string|max:50|unique:seed_lots,lot_code',
            'production_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'harvest_date' => 'nullable|date',
            // Integer-only policy by default
            'quantity' => 'required|integer|min:1',
            'unit' => 'required|string|in:kg,ton,piece,bottle',
            'price_per_unit' => 'required|integer|min:0',
            'is_sellable' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ];

        // Add conditional validation based on seed class category
        if ($this->filled('seed_class_id')) {
            $seedClass = SeedClass::find($this->seed_class_id);
            
            if ($seedClass) {
                $allowedUnits = [$seedClass->default_unit];
                if ($seedClass->stock_category === 'weight') {
                    $allowedUnits = array_unique(array_merge($allowedUnits, ['kg', 'ton']));
                } else {
                    $allowedUnits = array_unique(array_merge($allowedUnits, ['bottle', 'piece']));
                }
                
                $rules['unit'] = 'required|string|in:' . implode(',', $allowedUnits);
                
                // Quantity must be integer for consistency across all classes (per project policy)
                $rules['quantity'] = 'required|integer|min:1';
            }
        }

        return $rules;
    }

    /**
     * Hook into validator for additional constraints.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Enforce price_per_unit divisibility by 1000 when unit is 'ton' for BS/FS
            $seedClass = null;
            if ($this->filled('seed_class_id')) {
                $seedClass = SeedClass::find($this->seed_class_id);
            }
            $unit = $this->input('unit');
            if ($seedClass && $seedClass->stock_category === 'weight' && $unit === 'ton') {
                $price = $this->input('price_per_unit');
                if (is_numeric($price) && ((int) $price % 1000 !== 0)) {
                    $validator->errors()->add('price_per_unit', 'For weight-based classes with unit ton, price per unit must be a multiple of 1000 to normalize to per kg.');
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $messages = [
            'variety_id.required' => 'Please select a variety.',
            'variety_id.exists' => 'The selected variety is invalid.',
            'seed_class_id.required' => 'Please select a seed class.',
            'seed_class_id.exists' => 'The selected seed class is invalid.',
            'lot_code.required' => 'Lot code is required.',
            'lot_code.unique' => 'This lot code already exists.',
            'production_year.required' => 'Production year is required.',
            'production_year.min' => 'Production year must be at least 2000.',
            'production_year.max' => 'Production year cannot be more than next year.',
            'quantity.required' => 'Quantity is required.',
            // Integer-only policy across all seed classes
            'quantity.integer' => 'Jumlah harus berupa angka bulat (tidak boleh desimal).',
            'quantity.min' => 'Jumlah minimal adalah 1.',
            'unit.required' => 'Unit is required.',
            'price_per_unit.required' => 'Price per unit is required.',
            'price_per_unit.integer' => 'Price per unit must be an integer.',
            'price_per_unit.min' => 'Price per unit must be at least 0.',
        ];

        // Add conditional messages based on seed class category
        if ($this->filled('seed_class_id')) {
            $seedClass = SeedClass::find($this->seed_class_id);
            
            if ($seedClass) {
                if ($seedClass->stock_category === 'weight') {
                    $messages['unit.in'] = sprintf('The unit is invalid for %s. Valid units: kg, ton.', $seedClass->name);
                } elseif ($seedClass->stock_category === 'unit') {
                    $messages['unit.in'] = sprintf('The unit is invalid for %s. Valid units: bottle, piece.', $seedClass->name);
                }
            }
        }

        return $messages;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'variety_id' => 'variety',
            'seed_class_id' => 'seed class',
            'lot_code' => 'lot code',
            'production_year' => 'production year',
            'price_per_unit' => 'price per unit',
            'is_sellable' => 'sellable status',
        ];
    }
}