@extends('layouts.vertical', ['title' => 'Dashboard', 'subTitle' => 'Analytics'])

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap/dist/css/jsvectormap.min.css">
@endpush

@section('content')

<!-- Dashboard Header & Filter -->
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title-box d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 flex-md-nowrap">
            <h4 class="mb-0 fs-18 fw-bold text-dark">Ringkasan Distribusi & Stok</h4>
            
            <form action="{{ route('admin.dashboard') }}" method="GET" class="row g-2 w-100 w-md-auto ms-md-auto">
                <div class="col-12 col-sm-6 col-md-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 border-primary-subtle"><iconify-icon icon="solar:calendar-bold-duotone" class="text-primary"></iconify-icon></span>
                        <input type="date" name="start_date" class="form-control border-start-0 border-primary-subtle" value="{{ $startDate }}" placeholder="Mulai">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-auto">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 border-primary-subtle"><iconify-icon icon="solar:calendar-bold-duotone" class="text-primary"></iconify-icon></span>
                        <input type="date" name="end_date" class="form-control border-start-0 border-primary-subtle" value="{{ $endDate }}" placeholder="Selesai">
                    </div>
                </div>
                <div class="col-6 col-md-auto">
                    <button type="submit" class="btn btn-sm btn-primary w-100 px-3 shadow-sm">Filter</button>
                </div>
                <div class="col-6 col-md-auto">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-soft-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Baris 1: Summary Statistics (Modern Lucide-style Icons) -->
<div class="row">
    <!-- Jangkauan -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3">
                        <iconify-icon icon="lucide:map-pin" class="fs-20 text-primary"></iconify-icon>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 fs-12 fw-semibold text-uppercase ls-1">Provinsi Terjangkau</p>
                        <h3 class="mb-0 fw-bold">{{ $summaryStats['jangkauan'] }} <span class="fs-12 text-muted fw-normal lowercase">wilayah</span></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Juara Wilayah -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-warning bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3">
                        <iconify-icon icon="lucide:activity" class="fs-20 text-warning"></iconify-icon>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 fs-12 fw-semibold text-uppercase ls-1">Wilayah Teraktif</p>
                        <h3 class="mb-0 fw-bold text-truncate" style="max-width: 151px;">{{ $summaryStats['market_leader'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Volume -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-info bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3">
                        <iconify-icon icon="lucide:package" class="fs-20 text-info"></iconify-icon>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 fs-12 fw-semibold text-uppercase ls-1">Benih Terdistribusi</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($summaryStats['volume'], 0, ',', '.') }} <span class="fs-12 text-muted fw-normal lowercase">unit</span></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Omzet -->
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-success bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3">
                        <iconify-icon icon="lucide:calculator" class="fs-20 text-success"></iconify-icon>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 fs-12 fw-semibold text-uppercase ls-1">Total Penjualan</p>
                        <h3 class="mb-0 fw-bold">Rp {{ number_format($summaryStats['omzet'], 0, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Baris 2: Order Status Trackers -->
<div class="row">
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-secondary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3">
                        <iconify-icon icon="lucide:clock" class="fs-20 text-secondary"></iconify-icon>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 fs-12 fw-medium">Pending</p>
                        <h4 class="mb-0 fw-bold">{{ $countPending }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3">
                        <iconify-icon icon="lucide:refresh-cw" class="fs-20 text-primary"></iconify-icon>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 fs-12 fw-medium">Processing</p>
                        <h4 class="mb-0 fw-bold">{{ $countProcessing }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-success bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3">
                        <iconify-icon icon="lucide:check-circle" class="fs-20 text-success"></iconify-icon>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 fs-12 fw-medium">Completed</p>
                        <h4 class="mb-0 fw-bold">{{ $countCompleted }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task 2: Seed Viability Chart (Stacked Bar) -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom">
                <h4 class="card-title mb-0">Analisis Usia Stok (Seed Viability)</h4>
            </div>
            <div class="card-body">
                @if(empty($viabilityCategories))
                    <div class="text-center py-5 text-muted">
                        <iconify-icon icon="solar:leaf-bold-duotone" class="fs-48 mb-2"></iconify-icon>
                        <p>Belum ada data stok benih aktif.</p>
                    </div>
                @else
                    <div id="seed-viability-chart" style="min-height: 350px;"></div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Task 3: Indonesia Map & Top Provinces -->
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Sebaran Pesanan (Indonesia)</h4>
                @if(isset($last_updated))
                    <span class="text-muted fs-11">Data diperbarui: {{ $last_updated }}</span>
                @endif
            </div>
            <div class="card-body">
                <div id="indonesia-map" style="height: 350px"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Top 5 Provinsi</h4>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-soft-secondary">Lihat Semua</a>
            </div>
            <div class="card-body">
                @if(empty($topProvinces))
                    <div class="text-center py-5 text-muted">
                        <iconify-icon icon="solar:map-bold-duotone" class="fs-48 mb-2"></iconify-icon>
                        <p>Belum ada data geografis pesanan.</p>
                    </div>
                @else
                    @foreach($topProvinces as $iso => $province)
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h5 class="m-0 fs-14 fw-medium">{{ $province['name'] }}</h5>
                                <span class="text-muted fs-12">{{ $province['total_orders'] }} Pesanan</span>
                            </div>
                            <div class="progress progress-sm rounded-pill">
                                <div class="progress-bar bg-primary bg-gradient" role="progressbar" 
                                    style="width: {{ $province['percentage'] }}%" 
                                    aria-valuenow="{{ $province['percentage'] }}" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100"></div>
                            </div>
                        </div>
                    @endforeach
                    
                    <div class="mt-2 text-center text-muted fs-13">
                        Total Pesanan: {{ array_sum($geoData) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Task 1: Revenue Chart -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Tren Pendapatan</h4>
                <div class="text-success fw-bold">
                    Total: Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </div>
            </div>
            <div class="card-body position-relative" style="min-height: 400px;">
                @if(empty($chartValues) || array_sum($chartValues) == 0)
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75" style="z-index: 10;">
                        <div class="text-center text-muted">
                            <iconify-icon icon="lucide:chart-column" class="fs-48 mb-2 opacity-50"></iconify-icon>
                            <p class="mb-0 fw-medium">Tidak ada data transaksi pada periode ini.</p>
                        </div>
                    </div>
                @endif
                <div id="revenue-trend-chart" class="apex-charts" style="min-height: 350px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Task 6: Detail Distribusi Benih Per Wilayah (Baris 3) -->
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm border-0">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Detail Distribusi Benih Per Wilayah</h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.dashboard.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-soft-primary shadow-sm hover-elevate">
                        <iconify-icon icon="solar:file-download-bold-duotone" class="align-middle me-1 fs-16"></iconify-icon> Export Excel (CSV)
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-nowrap mb-0">
                        <thead class="bg-light-subtle">
                            <tr>
                                <th class="ps-3" style="width: 50px;">No</th>
                                <th>Provinsi</th>
                                <th class="text-center">Pesanan</th>
                                <th class="text-center">BS (kg)</th>
                                <th class="text-center">FS (kg)</th>
                                <th class="text-center">PL (botol)</th>
                                <th class="text-end pe-3">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tableData as $index => $row)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="fw-medium">{{ $row['name'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2">
                                            {{ number_format($row['total_orders'], 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($row['qty_bs'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['qty_fs'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['qty_pl'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format($row['total_revenue'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <iconify-icon icon="solar:database-bold-duotone" class="fs-48 mb-2 opacity-50"></iconify-icon>
                                        <p class="mb-0">Belum ada data transaksi untuk periode ini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Inventory Watchdog (Elegant Subtle Alert) -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-left: 5px solid #f59e0b !important;">
            <div class="card-header border-bottom bg-white py-3">
                <h5 class="card-title mb-0 text-dark d-flex align-items-center fw-bold">
                    <span class="pulse-warning me-2"></span>
                    <iconify-icon icon="lucide:shield-alert" class="me-2 fs-20 text-warning"></iconify-icon> 
                    Inventory Watchdog: Restock Recommendations
                </h5>
            </div>
            <div class="card-body bg-white">
                <div class="row g-3">
                    @forelse($lowStock as $variety)
                        @php
                            $isCritical = $variety->total_stock <= 0;
                            $themeColor = $isCritical ? '#ef4444' : '#f59e0b';
                            $badgeClass = $isCritical ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning';
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <div class="p-3 border rounded-3 h-100 bg-light bg-opacity-25" style="border-left: 4px solid {{ $themeColor }} !important;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="fs-14 mb-1 fw-bold text-dark">{{ $variety->name }}</h5>
                                        <p class="mb-0 fs-12 text-muted">SKU: <span class="text-dark fw-medium">{{ $variety->sku }}</span></p>
                                    </div>
                                    <div class="text-end">
                                        <div class="badge {{ $badgeClass }} rounded-pill px-3 py-1 mb-1 fs-12 shadow-sm border border-opacity-25">
                                            Stok: {{ number_format($variety->total_stock, 0, ',', '.') }} kg
                                        </div>
                                        <div class="fs-11 text-muted fw-medium">Batas: {{ number_format($variety->minimum_limit, 0, ',', '.') }} kg</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 py-1">
                            <div class="text-center py-4 text-muted bg-light bg-opacity-30 rounded-3 border border-dashed">
                                <iconify-icon icon="lucide:check-circle" class="text-success fs-32 mb-2"></iconify-icon>
                                <p class="mb-0 fw-medium">Semua stok varietas aman. Tidak ada rekomendasi restok saat ini.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pulse-warning {
    width: 8px;
    height: 8px;
    background: #f59e0b;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
    animation: pulse-amber 3s infinite;
}

@keyframes pulse-amber {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

.hover-elevate:hover { transform: translateY(-1px); transition: all 0.2s; }

.apex-charts {
    min-height: 350px !important;
}

/* Ensure empty state overlay covers correctly */
.card-body.position-relative {
    min-height: 350px;
}
</style>

@endsection

@section('script-bottom')
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- jsVectorMap -->
<script src="https://cdn.jsdelivr.net/npm/jsvectormap"></script>
<script src="{{ asset('assets/libs/jsvectormap/maps/indonesia.js') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Indonesia Map ---
        @if(!empty($geoData))
        const map = new jsVectorMap({
            selector: '#indonesia-map',
            map: 'indonesia',
            zoomOnScroll: false,
            regionStyle: {
                initial: {
                    fill: '#f4f4f4',
                    stroke: '#dee2e6',
                    strokeWidth: 0.5,
                    fillOpacity: 1
                },
                hover: {
                    fillOpacity: 0.8,
                    cursor: 'pointer'
                }
            },
            visualizeData: {
                scale: ['#e3ebf6', '#3e60d5'],
                values: @json($geoData)
            },
            onRegionTooltipShow(event, tooltip, code) {
                let count = @json($geoData)[code] || 0;
                let revenue = @json($revenueData)[code] || 0;
                let formattedRevenue = new Intl.NumberFormat('id-ID', { 
                    style: 'currency', 
                    currency: 'IDR',
                    maximumFractionDigits: 0
                }).format(revenue);

                tooltip.text(
                    `<strong>${tooltip.text()}</strong><br>
                     <span class="fs-12">${count} Pesanan</span><br>
                     <span class="fs-12 text-success">Revenue: ${formattedRevenue}</span>`
                , true)
            }
        });
        @endif

        // --- 2. Revenue Chart ---
        var revenueOptions = {
            series: [{
                name: "Pendapatan",
                data: @json($chartValues)
            }],
            chart: {
                height: 350,
                type: 'area',
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.3,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: @json($chartDates),
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('id-ID', { 
                            style: 'currency', 
                            currency: 'IDR',
                            maximumFractionDigits: 0 
                        }).format(value);
                    }
                }
            },
            colors: ['#0ab39c'], // Brand-like Success Green (Tealish-Green)
            grid: {
                borderColor: '#eef1f5',
                strokeDashArray: 5,
            },
            markers: {
                size: 4,
                colors: ['#0ab39c'],
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: { size: 6 }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('id-ID', { 
                            style: 'currency', 
                            currency: 'IDR' 
                        }).format(value);
                    }
                }
            }
        };

        var revenueChart = new ApexCharts(document.querySelector("#revenue-trend-chart"), revenueOptions);
        revenueChart.render();

        // --- 2. Seed Viability Chart (Stacked Bar) ---
        @if(!empty($viabilityCategories))
        var viabilityOptions = {
            series: [
                {
                    name: 'Fresh (< 6 Months)',
                    data: @json($viabilityFresh)
                },
                {
                    name: 'Warning (6-12 Months)',
                    data: @json($viabilityWarning)
                },
                {
                    name: 'Critical (> 12 Months)',
                    data: @json($viabilityCritical)
                }
            ],
            chart: {
                type: 'bar',
                height: 350,
                stacked: true,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    dataLabels: {
                        total: {
                            enabled: true,
                            offsetX: 0,
                            style: {
                                fontSize: '13px',
                                fontWeight: 900
                            }
                        }
                    }
                },
            },
            stroke: {
                width: 1,
                colors: ['#fff']
            },
            xaxis: {
                categories: @json($viabilityCategories),
                labels: {
                    formatter: function (val) {
                        return val // + " Kg" or unit if consistent
                    }
                }
            },
            yaxis: {
                title: {
                    text: undefined
                },
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + " (Qty)";
                    }
                }
            },
            fill: {
                opacity: 1
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                offsetX: 40
            },
            colors: ['#0ab39c', '#f7b84b', '#f06548'] // Fresh (Green), Warning (Yellow), Critical (Red)
        };

        var viabilityChart = new ApexCharts(document.querySelector("#seed-viability-chart"), viabilityOptions);
        viabilityChart.render();
        @endif
    });
</script>
@endsection
