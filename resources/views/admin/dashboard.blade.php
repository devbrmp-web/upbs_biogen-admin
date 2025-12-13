@extends('layouts.vertical', ['title' => 'Dashboard', 'subTitle' => 'Analytics'])

@section('content')

<!-- Task 1: 4 Status Widgets -->
<div class="row">
    <!-- Pending -->
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">{{ $countPending }}</h4>
                        <p class="text-muted mb-0">Pending</p>
                    </div>
                    <div class="avatar-md bg-warning bg-opacity-10 rounded d-flex align-items-center justify-content-center">
                        <iconify-icon icon="solar:clock-circle-bold-duotone" class="fs-32 text-warning"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Processing -->
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">{{ $countProcessing }}</h4>
                        <p class="text-muted mb-0">Processing</p>
                    </div>
                    <div class="avatar-md bg-info bg-opacity-10 rounded d-flex align-items-center justify-content-center">
                        <iconify-icon icon="solar:box-bold-duotone" class="fs-32 text-info"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipping -->
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">{{ $countShipping }}</h4>
                        <p class="text-muted mb-0">Shipping</p>
                    </div>
                    <div class="avatar-md bg-primary bg-opacity-10 rounded d-flex align-items-center justify-content-center">
                        <iconify-icon icon="solar:delivery-bold-duotone" class="fs-32 text-primary"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed -->
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">{{ $countCompleted }}</h4>
                        <p class="text-muted mb-0">Completed</p>
                    </div>
                    <div class="avatar-md bg-success bg-opacity-10 rounded d-flex align-items-center justify-content-center">
                        <iconify-icon icon="solar:verified-check-bold-duotone" class="fs-32 text-success"></iconify-icon>
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

<!-- Task 1: Revenue Chart -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Pendapatan 7 Hari Terakhir</h4>
                <div class="text-success fw-bold">
                    Total: Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </div>
            </div>
            <div class="card-body">
                <div id="revenue-trend-chart" style="min-height: 350px;"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script-bottom')
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Revenue Chart ---
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
            colors: ['#22c55e'], // Green for revenue
            grid: {
                borderColor: '#f1f1f1',
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
