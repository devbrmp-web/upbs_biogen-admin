<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Guest checkout allowed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Customer information (required for guest checkout)
            'customer_name' => 'required|string|max:100',
            'customer_address' => 'required|string|max:500',
            'customer_phone' => 'required|string|max:20|regex:/^[\+]?[0-9\-\(\)\s]+$/',
            'customer_email' => 'nullable|email|max:100',
            
            // Shipping method (pickup is default, delivery requires call center coordination)
            'shipping_method' => 'required|in:pickup,delivery',
            'courier_name' => 'nullable|string|in:Pos Indonesia,Indah Cargo',
            
            // Cart items validation
            'items' => 'required|array|min:1',
            'items.*.variety_id' => 'required|exists:varieties,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
            'items.*.seed_lot_id' => 'nullable|exists:seed_lots,id',
            
            // Payment method (for future payment gateway integration)
            'payment_method' => 'nullable|string|in:va_bca,va_bni,va_bri,va_mandiri,qris,bank_transfer',
            
            // Terms and conditions
            'terms_accepted' => 'required|accepted',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Customer name is required for order processing.',
            'customer_address.required' => 'Customer address is required for order processing.',
            'customer_phone.required' => 'Customer phone number is required for order processing.',
            'customer_phone.regex' => 'Please enter a valid phone number.',
            'customer_email.email' => 'Please enter a valid email address.',
            'shipping_method.required' => 'Please select a shipping method.',
            'shipping_method.in' => 'Invalid shipping method selected.',
            'courier_name.required_if' => 'Please select a courier for delivery.',
            'courier_name.in' => 'Invalid courier selected. Please choose Pos Indonesia or Indah Cargo.',
            'items.required' => 'At least one item must be selected for checkout.',
            'items.min' => 'At least one item must be selected for checkout.',
            'items.*.variety_id.required' => 'Product selection is required.',
            'items.*.variety_id.exists' => 'Selected product is not available.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.min' => 'Minimum quantity is 1.',
            'items.*.quantity.max' => 'Maximum quantity per item is 1000.',
            'items.*.seed_lot_id.exists' => 'Selected seed lot is not available.',
            'terms_accepted.required' => 'You must accept the terms and conditions to proceed.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions to proceed.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'customer_name' => 'customer name',
            'customer_address' => 'customer address',
            'customer_phone' => 'phone number',
            'customer_email' => 'email address',
            'shipping_method' => 'shipping method',
            'items.*.variety_id' => 'product',
            'items.*.quantity' => 'quantity',
            'items.*.seed_lot_id' => 'seed lot',
            'terms_accepted' => 'terms and conditions',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default shipping method if not provided
        if (!$this->has('shipping_method')) {
            $this->merge(['shipping_method' => 'pickup']);
        }
        
        // Clean phone number
        if ($this->has('customer_phone')) {
            $phone = preg_replace('/[^\+0-9]/', '', $this->customer_phone);
            $this->merge(['customer_phone' => $phone]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Shipping method-specific rules handled automatically in backend
            
            // Validate item availability, seed class rules (BS/FS), and stock source
            if ($this->has('items') && is_array($this->items)) {
                foreach ($this->items as $index => $item) {
                    if (!isset($item['variety_id']) || !isset($item['quantity'])) {
                        continue;
                    }

                    $qty = (int) $item['quantity'];

                    // Prefer validating against SeedLot if provided; fallback to Variety stock
                    if (!empty($item['seed_lot_id'])) {
                        $seedLot = \App\Models\SeedLot::with('seedClass')->find($item['seed_lot_id']);
                        if (!$seedLot) {
                            $validator->errors()->add("items.{$index}.seed_lot_id", 'Selected seed lot is not available.');
                            continue;
                        }

                        // Ensure sellable and sufficient quantity
                        if (!$seedLot->is_sellable) {
                            $validator->errors()->add("items.{$index}.seed_lot_id", 'Selected seed lot is not sellable.');
                        }
                        if ($seedLot->quantity < $qty) {
                            $validator->errors()->add(
                                "items.{$index}.quantity",
                                "Insufficient seed lot stock. Available: {$seedLot->quantity}, Requested: {$qty}"
                            );
                        }

                        // Enforce BS (5kg multiples) vs FS (1kg)
                        $classCode = $seedLot->seedClass?->code;
                        if ($classCode === 'BS' && ($qty % 5 !== 0)) {
                            $validator->errors()->add(
                                "items.{$index}.quantity",
                                'For BS class seeds, quantity must be in multiples of 5 kg.'
                            );
                        }
                        if ($classCode === 'FS' && $qty < 1) {
                            $validator->errors()->add("items.{$index}.quantity", 'For FS class seeds, minimum quantity is 1 kg.');
                        }
                    } else {
                        // Fallback: validate against variety stock
                        $variety = \App\Models\Variety::find($item['variety_id']);
                        if ($variety && $variety->stock < $qty) {
                            $validator->errors()->add(
                                "items.{$index}.quantity",
                                "Insufficient stock. Available: {$variety->stock}, Requested: {$qty}"
                            );
                        }
                    }
                }
            }
        });
    }
}
