<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumen Kerjasama - {{ $order->order_code }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem;
            color: rgba(0, 0, 0, 0.05);
            z-index: 0;
            pointer-events: none;
            font-weight: bold;
            white-space: nowrap;
            text-align: center;
        }
        .content-layer {
            position: relative;
            z-index: 1;
        }
        @media print {
            .no-print { display: none; }
            .watermark { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="watermark">
        DOKUMEN ADMIN<br>OFFICIAL COPY
    </div>

    <div class="container mx-auto px-4 max-w-4xl content-layer">
        <div class="bg-white shadow-lg rounded-lg p-8 relative print:shadow-none print:p-0">
            <!-- Header Dokumen -->
            <div class="text-center mb-8 border-b pb-4">
                <div class="mb-2 text-xs font-bold text-gray-400 uppercase tracking-widest border border-gray-200 inline-block px-2 py-1 rounded">
                    Dokumen Admin - Official Copy
                </div>
                <h1 class="text-2xl font-bold uppercase tracking-wider mb-2">Dokumen Kerjasama</h1>
                <h2 class="text-lg font-semibold text-gray-600">UPBS Balai Besar Biogen</h2>
                <p class="text-sm text-gray-500">Jl. Tentara Pelajar No. 3A, Bogor 16111 - Jawa Barat</p>
            </div>

            <!-- Info Pesanan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <h3 class="font-bold text-gray-700 mb-2 border-b w-max">Data Pesanan</h3>
                    <table class="w-full text-sm">
                        <tr>
                            <td class="py-1 text-gray-600 w-32">No. Transaksi</td>
                            <td class="font-medium">: {{ $order->order_code }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 text-gray-600">Status</td>
                            <td class="font-medium uppercase">: {{ $order->status }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 text-gray-600">Tanggal</td>
                            <td class="font-medium">: {{ $order->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
                <div>
                    <h3 class="font-bold text-gray-700 mb-2 border-b w-max">Data Pelanggan</h3>
                    <table class="w-full text-sm">
                        <tr>
                            <td class="py-1 text-gray-600 w-32">Nama</td>
                            <td class="font-medium">: {{ $order->customer_name }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 text-gray-600">No. HP</td>
                            <td class="font-medium">: {{ $order->customer_phone }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 text-gray-600">Alamat</td>
                            <td class="font-medium">: {{ $order->customer_address }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Tabel Item -->
            <div class="mb-8">
                <h3 class="font-bold text-gray-700 mb-4">Rincian Item</h3>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border border-gray-200 px-4 py-2 text-left">No</th>
                                <th class="border border-gray-200 px-4 py-2 text-left">Varietas</th>
                                <th class="border border-gray-200 px-4 py-2 text-center">Kelas</th>
                                <th class="border border-gray-200 px-4 py-2 text-center">Qty</th>
                                <th class="border border-gray-200 px-4 py-2 text-right">Harga Satuan</th>
                                <th class="border border-gray-200 px-4 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->orderItems as $index => $item)
                            <tr>
                                <td class="border border-gray-200 px-4 py-2 text-center">{{ $index + 1 }}</td>
                                <td class="border border-gray-200 px-4 py-2">
                                    {{ $item->variety->name ?? $item->variety_name }}
                                </td>
                                <td class="border border-gray-200 px-4 py-2 text-center">{{ $item->seed_class ?? '-' }}</td>
                                <td class="border border-gray-200 px-4 py-2 text-center">{{ $item->quantity }}</td>
                                <td class="border border-gray-200 px-4 py-2 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="border border-gray-200 px-4 py-2 text-right">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-bold bg-gray-50">
                                <td colspan="5" class="border border-gray-200 px-4 py-2 text-right">Total</td>
                                <td class="border border-gray-200 px-4 py-2 text-right">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Tanda Tangan -->
            <div class="grid grid-cols-2 gap-8 mt-12 mb-4">
                <div class="text-center">
                    <p class="mb-16 font-medium">Pihak UPBS Biogen</p>
                    <div class="border-b border-gray-400 w-2/3 mx-auto"></div>
                    <p class="mt-2 text-sm text-gray-500">(Admin/Petugas)</p>
                </div>
                
                <div class="text-center flex flex-col items-center">
                    <p class="mb-4 font-medium">Pihak Pembeli</p>
                    
                    <div class="mb-2">
                         @if($order->signature_path)
                            <img src="{{ $order->signature_path }}" class="border border-gray-300 rounded" width="300" height="150" alt="Tanda Tangan Pembeli">
                         @else
                            <div class="border-2 border-dashed border-gray-300 rounded bg-gray-50 flex items-center justify-center text-gray-400" style="width: 300px; height: 150px;">
                                Belum ada tanda tangan
                            </div>
                         @endif
                    </div>

                    <div class="border-b border-gray-400 w-2/3 mx-auto mt-2"></div>
                    <p class="mt-2 text-sm text-gray-500">({{ $order->customer_name }})</p>
                </div>
            </div>

            <div class="text-center mt-12 text-xs text-gray-400 italic">
                Dokumen ini dicetak secara otomatis oleh sistem UPBS Biogen pada {{ date('d/m/Y H:i') }}
            </div>
        </div>

        <div class="mt-8 flex justify-center gap-4 no-print">
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2 font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Download PDF / Cetak
            </button>
            <button onclick="window.close()" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition font-medium">
                Tutup
            </button>
        </div>
    </div>
</body>
</html>
