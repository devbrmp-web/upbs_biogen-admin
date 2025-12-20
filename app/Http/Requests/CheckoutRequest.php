<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SeedLot;
use App\Models\Variety;
use App\Models\SeedClass;

class CheckoutRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_name'     => 'required|string|max:255',
            'customer_email'             => 'required|email',
            'customer_phone'             => 'required|string|max:20',
            'customer_address'           => 'required|string',

            'shipping_method'   => 'required|string',
            'courier_name'      => 'nullable|string',

            'terms_accepted'    => 'required|boolean|in:1,true',

            'items'             => 'required|array|min:1',
            'items.*.variety_id' => 'required|integer|exists:varieties,id',
            'items.*.seed_lot_id' => 'nullable|integer|exists:seed_lots,id',
            'items.*.quantity'  => 'required|numeric|min:1',
        ];
    }

    public function messages()
    {
        return [
            'terms_accepted.in' => 'Anda harus menyetujui syarat & ketentuan.',
        ];
    }

    public function prepareForValidation()
    {
        if ($this->has('terms_accepted')) {
            $this->merge([
                'terms_accepted' => filter_var($this->terms_accepted, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            foreach ($this->items as $item) {

                // ============================
                // CHECK SEED LOT FIRST
                // ============================
                if (!empty($item['seed_lot_id'])) {

                    $seedLot = SeedLot::find($item['seed_lot_id']);

                    if (!$seedLot) {
                        $validator->errors()->add('items', 'Seed Lot tidak ditemukan.');
                        continue;
                    }

                    if (!$seedLot->is_sellable) {
                        $validator->errors()->add('items', 'Seed Lot tidak dapat dijual.');
                    }

                    if ($seedLot->quantity < $item['quantity']) {
                        $validator->errors()->add(
                            'items',
                            'Stok Seed Lot tidak mencukupi.'
                        );
                    }

                    // RULE KHUSUS BERDASARKAN SEED CLASS
                    if ($seedLot->seed_class_id) {
                        $class = SeedClass::find($seedLot->seed_class_id);

                        if ($class) {
                            if ($class->code === 'FS' && ($item['quantity'] % 5 !== 0)) {
                                $validator->errors()->add(
                                    'items',
                                    'Pembelian benih FS harus kelipatan 5 kg.'
                                );
                            }

                            if ($class->code === 'BS' && $item['quantity'] < 1) {
                                $validator->errors()->add(
                                    'items',
                                    'Pembelian benih BS minimal 1 kg.'
                                );
                            }
                        }
                    }
                }

                // ============================
                // CHECK VARIETY STOCK
                // ============================
                else {
                    $variety = Variety::find($item['variety_id']);

                    if ($variety && $variety->stock < $item['quantity']) {
                        $validator->errors()->add(
                            'items',
                            "Stok untuk varietas {$variety->name} tidak mencukupi."
                        );
                    }
                }
            }
        });
    }
}
