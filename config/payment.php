<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Method
    |--------------------------------------------------------------------------
    |
    | Determines which payment method is active.
    | Options: 'midtrans' or 'manual'
    |
    */
    'method' => env('PAYMENT_METHOD', 'manual'),

    /*
    |--------------------------------------------------------------------------
    | Bank Accounts for Manual Transfer
    |--------------------------------------------------------------------------
    |
    | List of bank accounts for manual transfer payment.
    |
    */
    'banks' => [
        [
            'bank' => 'BRI',
            'account_number' => '0123-01-012345-56-7',
            'account_name' => 'Bendahara Pengeluaran BRMP Biogen',
            'logo' => '/images/banks/bri.png',
        ],
        [
            'bank' => 'Bank Mandiri',
            'account_number' => '123-00-1234567-8',
            'account_name' => 'Bendahara Pengeluaran BRMP Biogen',
            'logo' => '/images/banks/mandiri.png',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Instructions
    |--------------------------------------------------------------------------
    */
    'instructions' => [
        'Pastikan nominal transfer sesuai dengan total pembayaran.',
        'Simpan bukti transfer untuk diunggah pada langkah selanjutnya.',
        'Pesanan akan diproses setelah pembayaran terverifikasi oleh admin.',
        'Waktu verifikasi maksimal 1x24 jam kerja.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration (when enabled)
    |--------------------------------------------------------------------------
    */
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'is_sanitized' => true,
        'is_3ds' => true,
    ],
];
