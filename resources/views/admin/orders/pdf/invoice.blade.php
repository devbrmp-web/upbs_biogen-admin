<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $order->order_code }}</title>
    <style>
        /* A4 Paper Dimensions and Base Settings */
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 20mm 15mm; /* reduced top margin */
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5pt; /* Smaller base font for compact layout */
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .page-break-avoid {
            page-break-inside: avoid;
        }

        /* Watermark Background */
        .watermark-container {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(45deg);
            z-index: -1; 
            text-align: center;
            width: 100%;
        }

        .watermark-badge {
            font-weight: bold;
            font-size: 72pt;
            border: 8px solid;
            padding: 20px 40px;
            display: inline-block;
            text-align: center;
        }
        
        .watermark-badge.paid {
            color: rgba(46, 125, 50, 0.15); /* Forest Green opacity */
            border-color: rgba(46, 125, 50, 0.15);
        }

        .watermark-badge.unpaid {
            color: rgba(220, 53, 69, 0.15); /* Red opacity */
            border-color: rgba(220, 53, 69, 0.15);
        }

        /* Professional Header */
        .header-official {
            width: 100%;
            margin-bottom: 15px;
            display: table;
            page-break-after: avoid;
        }

        .header-logo {
            display: table-cell;
            vertical-align: middle;
            width: 90px; /* Reduced for elegance */
        }

        .header-logo img {
            width: 80px; /* Refined size */
        }

        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: left;
            padding-left: 10px;
        }

        .header-text h1 {
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #2E7D32; /* Forest Green Branding */
        }

        .header-text p.address {
            font-size: 8.5pt;
            margin: 0;
            color: #555;
        }

        .header-line {
            border-top: 3px solid #2E7D32;
            border-bottom: 1px solid #2E7D32;
            height: 2px;
            margin-top: 10px;
            margin-bottom: 20px;
            padding: 0;
        }

        /* Invoice Title */
        .invoice-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2E7D32;
            letter-spacing: 2px;
        }

        /* 2-Column Info Table */
        .info-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }

        .info-table td {
            vertical-align: top;
            padding: 2px 0;
            font-size: 9.5pt;
        }

        .info-table .col-left {
            width: 50%;
            padding-right: 15px;
        }

        .info-table .col-right {
            width: 50%;
            padding-left: 15px;
        }

        .info-table .label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 120px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9.5pt;
            page-break-inside: auto; 
        }

        .items-table th {
            background-color: #2E7D32; /* Forest Green */
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 8px 6px;
            border: 1px solid #1B5E20;
        }

        .items-table td {
            border-bottom: 1px solid #eee;
            border-left: 1px solid #eee;
            border-right: 1px solid #eee;
            padding: 8px 6px;
            vertical-align: middle;
        }

        /* Zebra striping specific to modern agriculture theme */
        .items-table tbody tr:nth-child(even) {
            background-color: #E8F5E9; /* Light green tint */
        }

        .items-table td.text-right { text-align: right; }
        .items-table td.text-center { text-align: center; }

        .items-table .product-name {
            font-weight: bold;
            color: #000;
            margin-bottom: 2px;
            display: block;
        }

        .items-table .product-meta {
            font-size: 8.5pt;
            color: #666;
        }

        /* Calculation Summary */
        .summary-container {
            width: 100%;
            page-break-inside: avoid;
        }

        .summary-table {
            width: 45%; 
            float: right;
            border-collapse: collapse;
            font-size: 9.5pt;
        }

        .summary-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #eee;
        }

        .summary-table .label {
            font-weight: bold;
            text-align: right;
            color: #555;
        }

        .summary-table .amount {
            text-align: right;
            width: 120px;
            color: #000;
        }

        .summary-table .grand-total-row {
            background-color: #2E7D32;
        }

        .summary-table .grand-total-row td {
            color: #ffffff;
            font-size: 11pt;
            font-weight: bold;
            border-bottom: none;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Decorative Bottom Line */
        .decorative-line {
            height: 4px;
            background-color: #2E7D32;
            width: 100%;
            margin-top: 30px;
        }

        /* Footer Validation */
        .footer-validation {
            text-align: center;
            font-size: 8.5pt;
            color: #666;
            padding-top: 10px;
            page-break-inside: avoid;
        }
        
        .qr-image {
            width: 70px; /* Reduced further to save space */
            height: 70px;
            margin: 5px auto;
            display: block;
        }
        
        .qr-text {
            font-size: 7.5pt;
            color: #777;
            margin-top: 3px;
        }

        .slogan {
            font-weight: bold;
            color: #2E7D32;
            font-size: 9.5pt;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>

    <!-- Watermark Layer -->
    <div class="watermark-container">
        @if(in_array($order->status, ['paid', 'completed', 'pickup_ready', 'processing']))
            <div class="watermark-badge paid">LUNAS</div>
        @else
            <div class="watermark-badge unpaid">BELUM DIBAYAR</div>
        @endif
    </div>

    <!-- Official Header -->
    <div class="header-official">
        <div class="header-logo">
            @if(!empty($logoBase64))
                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo Kementan">
            @endif
        </div>
        <div class="header-text">
            <h1>Balai Besar Perakitan dan Modernisasi Bioteknologi dan Sumber Daya Genetik Pertanian</h1>
            <p class="address">Jl. Tentara Pelajar No.3A, RT.02/RW.7, Menteng, Kec. Bogor Bar., Kota Bogor, Jawa Barat 16111</p>
        </div>
    </div>
    <div class="header-line"></div>

    <div class="invoice-title">INVOICE</div>

    <!-- Transaction & Customer Info -->
    <table class="info-table">
        <tr>
            <td class="col-left">
                <span class="label">Nomor Invoice</span>: INV-{{ $order->order_code }}<br>
                <span class="label">Tanggal Order</span>: {{ $order->created_at->format('d F Y') }}<br>
                <span class="label">Metode Layanan</span>: {{ $order->shipping_method == 'pickup' ? 'Ambil di Tempat' : 'Kirim Kurir' }}
            </td>
            <td class="col-right">
                <span class="label">Nama Pembeli</span>: {{ $order->customer_name }}<br>
                <span class="label">Telepon</span>: {{ $order->customer_phone }}<br>
                <span class="label">Alamat</span>: {{ $order->customer_address ?? '-' }}
            </td>
        </tr>
    </table>

    <!-- Products Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No.</th>
                <th width="45%">Detail Produk (Komoditas, Varietas, Kelas)</th>
                <th width="15%">Jumlah</th>
                <th width="15%">Harga Satuan</th>
                <th width="20%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <span class="product-name">
                            {{ $item->seedLot->variety->commodity->name ?? 'Komoditas' }} - {{ $item->seedLot->variety->name ?? 'Varietas' }}
                        </span>
                        <span class="product-meta">
                            Kelas Benih: {{ $item->seedLot->seedClass->name ?? 'N/A' }} | Lot: {{ $item->seedLot->lot_number ?? '-' }}
                        </span>
                    </td>
                    <td class="text-center">{{ $item->quantity }} {{ $item->seedLot->unit ?? 'kg' }}</td>
                    <td class="text-right">Rp {{ number_format($item->price_at_order, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->quantity * $item->price_at_order, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Calculation Summary -->
    <div class="summary-container clearfix">
        <table class="summary-table">
            <tr>
                <td class="label">Subtotal Barang</td>
                <td class="amount">Rp {{ number_format($order->computed_subtotal ?? 0, 0, ',', '.') }}</td>
            </tr>
            
            @if($order->shipping_cost > 0)
            <tr>
                <td class="label">Biaya Pengiriman</td>
                <td class="amount">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
            </tr>
            @endif
            
            <tr>
                <td class="label">Biaya Layanan (1%)</td>
                <td class="amount">Rp {{ number_format($order->computed_biaya_layanan ?? 0, 0, ',', '.') }}</td>
            </tr>
            
            <tr>
                <td class="label">Biaya Aplikasi</td>
                <td class="amount">Rp {{ number_format($order->computed_biaya_aplikasi ?? 0, 0, ',', '.') }}</td>
            </tr>
            
            <tr class="grand-total-row">
                <td class="label" style="color:#fff;">Total Pembayaran</td>
                <td class="amount">Rp {{ number_format($order->computed_total ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Footer Validation -->
    <div class="decorative-line"></div>
    <div class="footer-validation page-break-avoid">
        <div class="slogan">"Benih Berkualitas untuk Kedaulatan Pangan Bangsa"</div>
        <p style="margin: 8px 0 2px 0;">Dokumen ini dihasilkan secara otomatis oleh Sistem E-Commerce UPBS BRMP Biogen</p>
        <div class="qr-image">
            {!! QrCode::size(70)->margin(0)->format('svg')->generate(route('orders.track.alias', $order->order_code)) !!}
        </div>
        <div class="qr-text">Scan untuk verifikasi:<br>{{ route('orders.track.alias', $order->order_code) }}</div>
    </div>

</body>
</html>
