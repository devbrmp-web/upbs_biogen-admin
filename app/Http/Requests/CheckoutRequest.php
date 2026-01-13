<?php

namespace App\Http\Requests;

use App\Models\SeedClass;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'customer_province' => 'nullable|string',
            'customer_city' => 'nullable|string',
            'customer_district' => 'nullable|string',
            'customer_postal_code' => 'nullable|string',

            'shipping_method' => 'required|in:pickup,delivery',
            'courier_name' => 'nullable|string',

            'terms_accepted' => 'required|boolean|in:1,true',

            'items' => 'required|array|min:1',
            'items.*.variety_id' => 'required|integer|exists:varieties,id',
            'items.*.seed_lot_id' => 'required|integer|exists:seed_lots,id', // Now required
            'items.*.quantity' => 'required|numeric|min:1',
        ];
    }

    public function messages()
    {
        return [
            'terms_accepted.in' => 'Anda harus menyetujui syarat & ketentuan.',
            'items.*.seed_lot_id.required' => 'Seed Lot harus dipilih untuk setiap item.',
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('terms_accepted')) {
            $this->merge([
                'terms_accepted' => filter_var($this->terms_accepted, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if (! $this->has('items')) {
            $cart = $this->session()->get('cart');
            if (is_array($cart) && ! empty($cart)) {
                $this->merge([
                    'items' => array_values($cart),
                ]);
            }
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! is_array($this->items) || empty($this->items)) {
                return;
            }
            foreach ($this->items as $index => $item) {
                // Validate seed_lot_id is present
                if (empty($item['seed_lot_id'])) {
                    $validator->errors()->add("items.{$index}.seed_lot_id", 'Seed Lot harus dipilih.');

                    continue;
                }

                $seedLot = SeedLot::find($item['seed_lot_id']);

                if (! $seedLot) {
                    $validator->errors()->add("items.{$index}.seed_lot_id", 'Seed Lot tidak ditemukan.');

                    continue;
                }

                // Validate seed lot belongs to the selected variety
                if ($seedLot->variety_id !== (int) $item['variety_id']) {
                    $validator->errors()->add("items.{$index}.seed_lot_id", 'Seed Lot tidak sesuai dengan varietas yang dipilih.');

                    continue;
                }

                if (! $seedLot->is_sellable) {
                    $validator->errors()->add("items.{$index}.seed_lot_id", 'Seed Lot tidak dapat dijual.');
                }

                if ($seedLot->quantity < $item['quantity']) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Stok Seed Lot tidak mencukupi. Tersedia: {$seedLot->quantity}, Diminta: {$item['quantity']}"
                    );
                }

                // Validate based on seed class rules
                if ($seedLot->seed_class_id) {
                    $class = SeedClass::find($seedLot->seed_class_id);

                    if ($class) {
                        if ($class->code === 'FS' && ($item['quantity'] % 5 !== 0)) {
                            $validator->errors()->add(
                                "items.{$index}.quantity",
                                'Pembelian benih FS harus kelipatan 5 kg.'
                            );
                        }

                        if ($class->code === 'BS' && $item['quantity'] < 1) {
                            $validator->errors()->add(
                                "items.{$index}.quantity",
                                'Pembelian benih BS minimal 1 kg.'
                            );
                        }
                    }
                }
            }
        });
    }
}
