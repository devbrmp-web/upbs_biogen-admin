<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SeedLot;
use App\Models\Variety;
use Illuminate\Http\JsonResponse;
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
    public function index()
    {
        // 1. Counter Status Pesanan (Group By)
        $statusCounts = Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Mapping Logic
        // Pending: awaiting_payment
        $pending = $statusCounts[Order::STATUS_AWAITING_PAYMENT] ?? 0;
        
        // Processing: processing + pickup_ready (simplified, no more delivery_coordination)
        $processing = ($statusCounts[Order::STATUS_PROCESSING] ?? 0) 
                    + ($statusCounts[Order::STATUS_PICKUP_READY] ?? 0);
        
        // Shipping: legacy - kept for historical orders that may still have old statuses
        // New flow doesn't have shipped/picked_up, but we keep counting for any legacy data
        $shipping = 0;
        
        // Completed: completed
        $completed = $statusCounts[Order::STATUS_COMPLETED] ?? 0;

        // 2. Total Pendapatan (Revenue) - Completed Orders Only
        $revenue = Order::where('status', Order::STATUS_COMPLETED)
            ->sum('total_amount');

        // 3. Grafik Tren Pendapatan (7 Hari Terakhir)
        $sevenDaysAgo = Carbon::today()->subDays(6); // Today + 6 days back = 7 days
        
        // Driver check for date function
        $driver = DB::connection()->getDriverName();
        $dateFormat = $driver === 'sqlite' ? "date(created_at)" : "DATE(created_at)";

        $dailyRevenue = Order::where('created_at', '>=', $sevenDaysAgo)
            ->where('status', Order::STATUS_COMPLETED) // Only completed revenue usually, or all valid? 
            // "Hitung SUM(total_amount)" usually implies valid sales. 
            // If using created_at, it might include pending. 
            // Usually "Revenue" implies realized or at least valid orders. 
            // Let's exclude Cancelled.
            ->where('status', '!=', Order::STATUS_CANCELLED) 
            ->select(
                DB::raw("$dateFormat as date"),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill gaps for chart
        $chartDates = [];
        $chartValues = [];
        $period = Carbon::parse($sevenDaysAgo)->daysUntil(Carbon::today());
        
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $found = $dailyRevenue->firstWhere('date', $dateStr);
            $chartDates[] = $date->format('d M');
            $chartValues[] = $found ? (float) $found->total : 0;
        }

        // 4. Seed Viability Logic
        $viabilityData = $this->getSeedViabilityData();

        return view('admin.dashboard', [
            'countPending' => $pending,
            'countProcessing' => $processing,
            'countShipping' => $shipping,
            'countCompleted' => $completed,
            'totalRevenue' => $revenue,
            'chartDates' => $chartDates,
            'chartValues' => $chartValues,
            'viabilityCategories' => $viabilityData['categories'],
            'viabilityFresh' => $viabilityData['fresh'],
            'viabilityWarning' => $viabilityData['warning'],
            'viabilityCritical' => $viabilityData['critical'],
        ]);
    }

    public function getStats(Request $request): JsonResponse
    {
        $period = $request->string('period')->toString() ?: 'today';
        $cacheKey = 'dashboard.stats.' . $period;

        try {
            $data = Cache::remember($cacheKey, 60, function () use ($period) {
                [$currentStart, $currentEnd, $previousStart, $previousEnd] = $this->resolvePeriodRanges($period);

                $currentQuery = Order::query()
                    ->where('status', '!=', Order::STATUS_CANCELLED)
                    ->where('created_at', '>=', $currentStart)
                    ->where('created_at', '<', $currentEnd);

                $previousQuery = Order::query()
                    ->where('status', '!=', Order::STATUS_CANCELLED)
                    ->where('created_at', '>=', $previousStart)
                    ->where('created_at', '<', $previousEnd);

                $currentRevenue = (float) $currentQuery->sum('total_amount');
                $currentOrders = (int) $currentQuery->count();
                $currentAov = $currentOrders > 0 ? $currentRevenue / $currentOrders : 0.0;

                $previousRevenue = (float) $previousQuery->sum('total_amount');
                $previousOrders = (int) $previousQuery->count();
                $previousAov = $previousOrders > 0 ? $previousRevenue / $previousOrders : 0.0;

                return [
                    'revenue' => [
                        'value' => (int) round($currentRevenue),
                        'growth' => $this->percentGrowth($currentRevenue, $previousRevenue),
                    ],
                    'orders' => [
                        'value' => $currentOrders,
                        'growth' => $this->percentGrowth((float) $currentOrders, (float) $previousOrders),
                    ],
                    'aov' => [
                        'value' => (int) round($currentAov),
                        'growth' => $this->percentGrowth($currentAov, $previousAov),
                    ],
                ];
            });

            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch dashboard stats', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch stats'], 500);
        }
    }

    public function getCharts(Request $request): JsonResponse
    {
        $period = $request->string('period')->toString() ?: 'last_7_days';
        [$start, $end] = $this->resolveTrendRange($period);

        $driver = DB::connection()->getDriverName();
        $dateFormat = $driver === 'sqlite' ? "date(created_at)" : 'DATE(created_at)';

        $raw = Order::query()
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->select(DB::raw("$dateFormat as date"), DB::raw('SUM(total_amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $days = $start->copy()->daysUntil($end->copy()->subDay());
        $trend = [];
        foreach ($days as $day) {
            $dateStr = $day->format('Y-m-d');
            $found = $raw->firstWhere('date', $dateStr);
            $trend[] = (int) round($found ? (float) $found->total : 0.0);
        }

        $last = array_slice($trend, -3);
        $avg = count($last) > 0 ? array_sum($last) / count($last) : 0;
        $forecast = [
            (int) round($avg),
            (int) round($avg),
            (int) round($avg),
        ];

        return response()->json([
            'trend' => $trend,
            'forecast' => $forecast,
        ]);
    }

    public function getStock(): JsonResponse
    {
        $lots = SeedLot::query()
            ->where('is_sellable', true)
            ->where('quantity', '<', 50)
            ->orderBy('quantity')
            ->limit(10)
            ->get();

        $data = $lots->map(function (SeedLot $lot) {
            $qty = (int) $lot->quantity;
            $status = $qty < 10 ? 'critical' : 'low';

            return [
                'id' => $lot->id,
                'lot_code' => $lot->lot_code,
                'quantity' => $qty,
                'status' => $status,
            ];
        })->values()->all();

        return response()->json($data);
    }

    public function getTopProducts(): JsonResponse
    {
        return response()->json([]);
    }

    public function getHeatmap(): JsonResponse
    {
        return response()->json([]);
    }

    private function percentGrowth(float $current, float $previous): int
    {
        if ($previous == 0.0) {
            return $current == 0.0 ? 0 : 100;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function resolvePeriodRanges(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [
                Carbon::today(),
                Carbon::tomorrow(),
                Carbon::yesterday(),
                Carbon::today(),
            ],
            'yesterday' => [
                Carbon::yesterday(),
                Carbon::today(),
                Carbon::yesterday()->subDay(),
                Carbon::yesterday(),
            ],
            'last_30_days' => [
                Carbon::today()->subDays(29),
                Carbon::tomorrow(),
                Carbon::today()->subDays(59),
                Carbon::today()->subDays(29),
            ],
            default => [
                Carbon::today()->subDays(6),
                Carbon::tomorrow(),
                Carbon::today()->subDays(13),
                Carbon::today()->subDays(6),
            ],
        };
    }

    private function resolveTrendRange(string $period): array
    {
        return match ($period) {
            'last_30_days' => [Carbon::today()->subDays(29), Carbon::tomorrow()],
            default => [Carbon::today()->subDays(6), Carbon::tomorrow()],
        };
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
}
