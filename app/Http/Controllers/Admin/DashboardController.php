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
