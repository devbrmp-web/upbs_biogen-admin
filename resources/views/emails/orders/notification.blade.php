<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $order->order_code }} - Update Status Pesanan</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f7f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #2E7D32;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
        }
        .content {
            padding: 30px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .status-awaiting_payment { background-color: #FFF3E0; color: #E65100; }
        .status-paid, .status-processing { background-color: #E8F5E9; color: #2E7D32; }
        .status-pickup_ready { background-color: #E3F2FD; color: #1565C0; }
        .status-completed { background-color: #E8F5E9; color: #2E7D32; }
        .status-cancelled { background-color: #FFEBEE; color: #C62828; }

        .order-info {
            background-color: #f9f9f9;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #2E7D32;
        }
        .order-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .order-info strong {
            color: #2E7D32;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table th {
            text-align: left;
            border-bottom: 2px solid #E8F5E9;
            padding: 10px 5px;
            font-size: 13px;
            color: #666;
        }
        .items-table td {
            padding: 12px 5px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .total-row {
            font-weight: bold;
            font-size: 16px;
            color: #2E7D32;
        }

        .button {
            display: inline-block;
            padding: 14px 28px;
            background-color: #2E7D32;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .footer {
            background-color: #f1f1f1;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .notes-panel {
            background-color: #FFFDE7;
            border: 1px solid #FFF59D;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>UPBS BRMP Biogen</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9; font-size: 14px;">Inovasi Benih Unggul Nasional</p>
        </div>
        
        <div class="content">
            <div class="status-badge status-{{ $type }}">
                {{ str_replace('_', ' ', $type) }}
            </div>

            <p>Halo <strong>{{ $order->customer_name }}</strong>,</p>
            
            @if($type === 'awaiting_payment')
                <p>Terima kasih telah melakukan pemesanan di UPBS BRMP Biogen. Pesanan Anda telah kami terima dan sedang menunggu pembayaran.</p>
            @elseif($type === 'paid' || $type === 'processing')
                <p>Pembayaran untuk pesanan Anda telah berhasil diverifikasi. Tim kami sedang menyiapkan benih terbaik untuk Anda.</p>
            @elseif($type === 'pickup_ready')
                <p>Kabar gembira! Pesanan Anda sudah siap untuk diambil di kantor UPBS BRMP Biogen. Silakan datang dengan membawa bukti invoice ini.</p>
            @elseif($type === 'completed')
                <p>Pesanan Anda telah dinyatakan selesai. Terima kasih telah mempercayakan kebutuhan benih Anda kepada kami. Semoga hasil panen melimpah!</p>
            @elseif($type === 'cancelled')
                <p>Kami menginformasikan bahwa pesanan Anda telah dibatalkan.</p>
            @endif

            <div class="order-info">
                <p><strong>Nomor Pesanan:</strong> #{{ $order->order_code }}</p>
                <p><strong>Tanggal:</strong> {{ $order->created_at->format('d M Y, H:i') }} WIB</p>
                <p><strong>Metode:</strong> {{ $order->shipping_method === 'pickup' ? 'Ambil di Tempat' : 'Pengiriman Kurir' }}</p>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="text-align: center;">Jumlah</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->variety->name ?? $item->variety_name }}</strong><br>
                            <span style="font-size: 11px; color: #888;">{{ $item->seed_class ?? '-' }}</span>
                        </td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" style="text-align: right; padding-top: 20px;">Total Pembayaran:</td>
                        <td style="text-align: right; padding-top: 20px;">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            @if($notes)
            <div class="notes-panel">
                <strong>Catatan Admin:</strong><br>
                {{ $notes }}
            </div>
            @endif

            @if(in_array($type, ['awaiting_payment', 'paid', 'processing']))
                <p style="font-size: 13px; color: #555;">Kami telah melampirkan file PDF Invoice pada email ini untuk keperluan administrasi Anda.</p>
            @endif

            <div style="text-align: center;">
                <a href="{{ $trackingUrl }}" class="button">Lacak Pesanan Saya</a>
            </div>

            <p style="font-size: 14px; margin-top: 30px;">
                Jika ada pertanyaan lebih lanjut, silakan hubungi layanan pelanggan kami.<br><br>
                Salam hangat,<br>
                <strong>Tim UPBS BRMP Biogen</strong>
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} UPBS BRMP Biogen. All rights reserved.<br>
            Jl. Tentara Pelajar No.3A, Bogor, Jawa Barat.
        </div>
    </div>
</body>
</html>
