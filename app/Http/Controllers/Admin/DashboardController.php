<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with essentials stats.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        // Cache versioning for total invalidation across all filter variants
        $version = Cache::get('admin_dashboard_v', 1);
        $cacheKey = "admin_dashboard_stats_{$version}_" . ($startDate ?: 'all') . "_" . ($endDate ?: 'all');

        $dashboardData = Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate) {
            // 0. Safe Date Parsing
            try {
                $parseStartDate = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
                $parseEndDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
            } catch (\Exception $e) {
                $parseStartDate = null;
                $parseEndDate = null;
            }

            // Helper for date filtering
            $applyDateFilter = function ($query) use ($parseStartDate, $parseEndDate) {
                if ($parseStartDate) {
                    $query->where('created_at', '>=', $parseStartDate);
                }
                if ($parseEndDate) {
                    $query->where('created_at', '<=', $parseEndDate);
                }
                return $query;
            };

            // 1. Counter Status Pesanan
            $statusCountsQuery = Order::select('status', DB::raw('count(*) as total'));
            $applyDateFilter($statusCountsQuery);
            $statusCounts = $statusCountsQuery->groupBy('status')->pluck('total', 'status')->toArray();

            // Mapping Logic
            $pending = $statusCounts[Order::STATUS_AWAITING_PAYMENT] ?? 0;
            $processing = ($statusCounts[Order::STATUS_PROCESSING] ?? 0) 
                        + ($statusCounts[Order::STATUS_PICKUP_READY] ?? 0);
            $shipping = 0;
            $completed = $statusCounts[Order::STATUS_COMPLETED] ?? 0;

            // 2. Total Pendapatan (Revenue) - Synchronized with Trend Chart & Summary Stats
            $revenueQuery = Order::where('status', '!=', Order::STATUS_CANCELLED);
            $applyDateFilter($revenueQuery);
            $totalRevenue = (float) $revenueQuery->sum('total_amount');

            // 3. Grafik Tren Pendapatan (Dynamic: Daily vs Monthly)
            $cEndDate = $parseEndDate ? $parseEndDate->copy() : Carbon::today()->endOfDay();
            $cStartDate = $parseStartDate ? $parseStartDate->copy() : null;
            
            // Determine grouping strategy and period
            $isMonthly = false;
            
            if (!$cStartDate) {
                // All Time: Truly All Time or Last 12 Months? 
                // User wants data to match Summary Stats, so let's find the earliest record.
                $firstOrder = Order::where('status', '!=', Order::STATUS_CANCELLED)->orderBy('created_at', 'asc')->first();
                $isMonthly = true;
                
                if ($firstOrder) {
                    $chartStart = Carbon::parse($firstOrder->created_at)->startOfMonth();
                } else {
                    $chartStart = Carbon::today()->subMonths(11)->startOfMonth();
                }
                $chartEnd = Carbon::today()->endOfMonth();
            } else {
                $daysDiff = $cStartDate->diffInDays($cEndDate);
                if ($daysDiff > 32) {
                    $isMonthly = true;
                    $chartStart = $cStartDate->copy()->startOfMonth();
                    $chartEnd = $cEndDate->copy()->endOfMonth();
                } else {
                    $isMonthly = false; // Daily
                    $chartStart = $cStartDate->copy()->startOfDay();
                    $chartEnd = $cEndDate->copy()->endOfDay();
                }
            }

            $driver = DB::connection()->getDriverName();
            
            // Base Query
            $revenueTrendQuery = Order::where('status', '!=', Order::STATUS_CANCELLED)
                ->whereBetween('created_at', [$chartStart, $chartEnd]);

            if ($isMonthly) {
                // Group by Year-Month
                $selectDate = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
                
                $trendResults = $revenueTrendQuery->select(
                    DB::raw("$selectDate as date"),
                    DB::raw('SUM(total_amount) as total')
                )
                ->groupBy('date')
                ->pluck('total', 'date')
                ->toArray();
                
                $period = Carbon::parse($chartStart)->monthsUntil($chartEnd);
                $formatCheck = 'Y-m';
                $formatLabel = 'M Y';
            } else {
                // Group by Date
                $selectDate = $driver === 'sqlite' ? "date(created_at)" : "DATE(created_at)";
                
                $trendResults = $revenueTrendQuery->select(
                    DB::raw("$selectDate as date"),
                    DB::raw('SUM(total_amount) as total')
                )
                ->groupBy('date')
                ->pluck('total', 'date')
                ->toArray();

                $period = Carbon::parse($chartStart)->daysUntil($chartEnd);
                $formatCheck = 'Y-m-d';
                $formatLabel = 'd M';
            }

            // Fill gaps for chart
            $chartDates = [];
            $chartValues = [];
            
            foreach ($period as $dt) {
                $key = $dt->format($formatCheck);
                $chartDates[] = $dt->format($formatLabel);
                $chartValues[] = (float) ($trendResults[$key] ?? 0);
            }

            // 4. Seed Viability Logic
            $viabilityData = $this->getSeedViabilityData();

            // 5. Geographic Data & Summary Stats (Respecting filter)
            $geoData = $this->getGeographicData($startDate, $endDate);

            // 6. Low Stock Alert (Row 4)
            $lowStock = Variety::needsRestock()->withStockCalculations()->get();

            return [
                'countPending' => $pending,
                'countProcessing' => $processing,
                'countShipping' => $shipping,
                'countCompleted' => $completed,
                'totalRevenue' => $totalRevenue,
                'chartDates' => $chartDates,
                'chartValues' => $chartValues,
                'viabilityCategories' => $viabilityData['categories'],
                'viabilityFresh' => $viabilityData['fresh'],
                'viabilityWarning' => $viabilityData['warning'],
                'viabilityCritical' => $viabilityData['critical'],
                'geoData' => $geoData['map_data'] ?? [],
                'revenueData' => $geoData['revenue_data'] ?? [],
                'topProvinces' => $geoData['top_provinces'] ?? [],
                'tableData' => $geoData['table_data'] ?? [],
                'summaryStats' => $geoData['summary'] ?? ['jangkauan' => 0, 'market_leader' => 'N/A', 'volume' => 0, 'omzet' => 0],
                'lowStock' => $lowStock,
                'last_updated' => Carbon::now()->format('d M Y, H:i')
            ];
        });

        return view('admin.dashboard', array_merge($dashboardData, [
            'startDate' => $startDate,
            'endDate' => $endDate
        ]));
    }

    /**
     * Calculate Seed Viability Data
     */
    private function getSeedViabilityData()
    {
        // Fetch all active seed lots (stock > 0) with variety info
        $activeLots = SeedLot::with('variety')
            ->where('quantity', '>', 0)
            ->get();

        // Group by Variety
        $grouped = $activeLots->groupBy('variety.name');

        $categories = [];
        $freshData = [];
        $warningData = [];
        $criticalData = [];

        foreach ($grouped as $varietyName => $lots) {
            $categories[] = $varietyName;
            
            $fresh = 0;
            $warning = 0;
            $critical = 0;

            foreach ($lots as $lot) {
                // Calculate age in months
                // Use harvest_date if available, else fallback to production_year (Jan 1st)
                $harvestDate = $lot->harvest_date 
                    ? Carbon::parse($lot->harvest_date) 
                    : Carbon::createFromDate($lot->production_year, 1, 1);
                
                $ageInMonths = $harvestDate->diffInMonths(Carbon::now());

                if ($ageInMonths < 6) {
                    $fresh += $lot->quantity;
                } elseif ($ageInMonths >= 6 && $ageInMonths <= 12) {
                    $warning += $lot->quantity;
                } else {
                    $critical += $lot->quantity;
                }
            }

            $freshData[] = $fresh;
            $warningData[] = $warning;
            $criticalData[] = $critical;
        }

        return [
            'categories' => $categories,
            'fresh' => $freshData,
            'warning' => $warningData,
            'critical' => $criticalData
        ];
    }

    /**
     * Get Geographic Data and Summary Stats for Indonesia Map
     */
    private function getGeographicData($startDate = null, $endDate = null)
    {
        try {
            $parseStartDate = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
            $parseEndDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
        } catch (\Exception $e) {
            $parseStartDate = null;
            $parseEndDate = null;
        }

        $query = Order::where('status', '!=', Order::STATUS_CANCELLED)
            ->with(['items']);

        if ($parseStartDate) {
            $query->where('created_at', '>=', $parseStartDate);
        }
        if ($parseEndDate) {
            $query->where('created_at', '<=', $parseEndDate);
        }

        $orders = $query->get();

        $provinceMapping = [
            'Aceh' => 'ID-AC',
            'Bali' => 'ID-BA',
            'Banten' => 'ID-BT',
            'Bengkulu' => 'ID-BE',
            'Gorontalo' => 'ID-GO',
            'Jakarta' => 'ID-JK',
            'DKI Jakarta' => 'ID-JK',
            'Jakarta Raya' => 'ID-JK',
            'Jambi' => 'ID-JA',
            'Jawa Barat' => 'ID-JB',
            'Jawa Tengah' => 'ID-JT',
            'Jawa Timur' => 'ID-JI',
            'DI Yogyakarta' => 'ID-YO',
            'Yogyakarta' => 'ID-YO',
            'Kalimantan Barat' => 'ID-KB',
            'Kalimantan Selatan' => 'ID-KS',
            'Kalimantan Tengah' => 'ID-KT',
            'Kalimantan Timur' => 'ID-KI',
            'Kalimantan Utara' => 'ID-KU',
            'Bangka Belitung' => 'ID-BB',
            'Kepulauan Bangka Belitung' => 'ID-BB',
            'Kepulauan Riau' => 'ID-KR',
            'Lampung' => 'ID-LA',
            'Maluku' => 'ID-MA',
            'Maluku Utara' => 'ID-MU',
            'Nusa Tenggara Barat' => 'ID-NB',
            'Nusa Tenggara Timur' => 'ID-NT',
            'Papua' => 'ID-PA',
            'Papua Barat' => 'ID-PB',
            'Irian Jaya Barat' => 'ID-PB',
            'Riau' => 'ID-RI',
            'Sulawesi Barat' => 'ID-SR',
            'Sulawesi Selatan' => 'ID-SN',
            'Sulawesi Tengah' => 'ID-ST',
            'Sulawesi Tenggara' => 'ID-SG',
            'Sulawesi Utara' => 'ID-SA',
            'Sumatera Barat' => 'ID-SB',
            'Sumatera Selatan' => 'ID-SS',
            'Sumatera Utara' => 'ID-SU',
        ];

        $dataByProvince = [];
        $totalOrdersCount = $orders->count();
        $totalVolume = 0;
        $totalOmzet = 0;

        foreach ($orders as $order) {
            $address = $order->customer_address;
            $parts = explode(',', $address);
            $provinceName = trim(end($parts));

            $isoCode = $provinceMapping[$provinceName] ?? null;

            // Global stats
            $totalOmzet += (float) $order->total_amount;
            $totalVolume += (float) $order->items->sum('quantity');

            if ($isoCode) {
                if (!isset($dataByProvince[$isoCode])) {
                    $dataByProvince[$isoCode] = [
                        'name' => $provinceName,
                        'total_orders' => 0,
                        'total_revenue' => 0,
                        'qty_bs' => 0,
                        'qty_fs' => 0,
                        'qty_pl' => 0,
                    ];
                }
                $dataByProvince[$isoCode]['total_orders']++;
                $dataByProvince[$isoCode]['total_revenue'] += (float) $order->total_amount;

                foreach ($order->items as $item) {
                    $qty = (float) $item->quantity;
                    $class = strtoupper($item->seed_class);
                    if ($class === 'BS') {
                        $dataByProvince[$isoCode]['qty_bs'] += $qty;
                    } elseif ($class === 'FS') {
                        $dataByProvince[$isoCode]['qty_fs'] += $qty;
                    } elseif ($class === 'PL') {
                        $dataByProvince[$isoCode]['qty_pl'] += $qty;
                    }
                }
            }
        }

        // Format for jsVectorMap (keys as ISO codes, value as count)
        $mapData = [];
        $revenueData = [];
        foreach ($dataByProvince as $iso => $val) {
            $mapData[$iso] = $val['total_orders'];
            $revenueData[$iso] = $val['total_revenue'];
        }

        // Top 5 Provinces
        $sortedProvinces = collect($dataByProvince)->sortByDesc('total_orders');
        
        $topProvinces = $sortedProvinces->take(5)
            ->map(function($item) use ($totalOrdersCount) {
                $item['percentage'] = $totalOrdersCount > 0 
                    ? round(($item['total_orders'] / $totalOrdersCount) * 100, 1) 
                    : 0;
                return $item;
            })
            ->all();

        // Table Data (Sorted by Revenue Desc)
        $tableData = collect($dataByProvince)->sortByDesc('total_revenue')->values()->all();

        $marketLeader = $sortedProvinces->first();

        return [
            'map_data' => $mapData,
            'revenue_data' => $revenueData,
            'top_provinces' => $topProvinces,
            'table_data' => $tableData,
            'summary' => [
                'jangkauan' => count($dataByProvince),
                'market_leader' => $marketLeader['name'] ?? 'N/A',
                'volume' => $totalVolume,
                'omzet' => $totalOmzet,
            ]
        ];
    }

    /**
     * Export Distribution Detail to CSV
     */
    public function export(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $geoData = $this->getGeographicData($startDate, $endDate);
        $tableData = $geoData['table_data'];

        $filename = "distribusi_benih_" . date('Ymd_His') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($tableData) {
            $file = fopen('php://output', 'w');
            // Byte Order Mark for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['No', 'Provinsi', 'Pesanan', 'BS (kg)', 'FS (kg)', 'PL (botol)', 'Revenue']);

            foreach ($tableData as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row['name'],
                    $row['total_orders'],
                    $row['qty_bs'],
                    $row['qty_fs'],
                    $row['qty_pl'],
                    $row['total_revenue']
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
