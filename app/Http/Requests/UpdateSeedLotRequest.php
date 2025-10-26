<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SeedClass;

class UpdateSeedLotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $seedLot = $this->route('seed_lot');
        
        $rules = [
            'variety_id' => 'required|exists:varieties,id',
            'seed_class_id' => 'required|exists:seed_classes,id',
            'lot_code' => 'required|string|max:50|unique:seed_lots,lot_code,' . $seedLot->id,
            'production_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            // Integer-only policy by default
            'quantity' => 'required|integer|min:0',
            'unit' => 'required|string|in:kg,gram,ton,piece,bottle',
            'price_per_unit' => 'required|integer|min:0',
            'is_sellable' => 'boolean',
            'notes' => 'nullable|string',
        ];

        // Add conditional validation based on seed class
        if ($this->filled('seed_class_id')) {
            $seedClass = SeedClass::find($this->seed_class_id);
            
            if ($seedClass) {
                switch ($seedClass->code) {
                    case 'BS':
                    case 'FS':
                        // BS and FS should use weight-based units (kg, gram, ton)
                        $rules['unit'] = 'required|string|in:kg,gram,ton';
                        // Quantity must be integer for consistency
                        $rules['quantity'] = 'required|integer|min:0';
                        break;
                    
                    case 'PL':
                        // Planlet should use bottle/piece units
                        $rules['unit'] = 'required|string|in:bottle,piece';
                        // Quantity must be integer (no decimals)
                        $rules['quantity'] = 'required|integer|min:0';
                        break;
                    
                    default:
                        // Other seed classes can use any unit
                        $rules['unit'] = 'required|string|in:kg,gram,ton,piece,bottle';
                        $rules['quantity'] = 'required|integer|min:0';
                        break;
                }
            }
        }

        return $rules;
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
            'quantity.integer' => 'Quantity must be an integer for this seed class.',
            'quantity.min' => 'Quantity must be at least 0.',
            'unit.required' => 'Unit is required.',
            'unit.in' => 'The selected unit is invalid for this seed class.',
            'price_per_unit.required' => 'Price per unit is required.',
            'price_per_unit.numeric' => 'Price per unit must be an integer.',
            'price_per_unit.min' => 'Price per unit must be at least 0.',
        ];

        // Add conditional messages based on seed class
        if ($this->filled('seed_class_id')) {
            $seedClass = SeedClass::find($this->seed_class_id);
            
            if ($seedClass) {
                switch ($seedClass->code) {
                    case 'BS':
                    case 'FS':
                        $messages['unit.in'] = 'The unit is invalid for this seed class. Valid units: kg, gram, ton.';
                        $messages['quantity.integer'] = 'Quantity must be an integer (no decimals) for weight-based seed classes.';
                        break;
                    
                    case 'PL':
                        $messages['unit.in'] = 'The unit is invalid for this seed class. Valid units: bottle, piece.';
                        $messages['quantity.integer'] = 'Quantity must be an integer for planlet (bottle/piece).';
                        break;
                    
                    default:
                        $messages['unit.in'] = 'The selected unit is invalid for this seed class.';
                        $messages['quantity.integer'] = 'Quantity must be an integer (no decimals).';
                        break;
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