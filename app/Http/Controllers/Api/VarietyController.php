<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Variety;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VarietyController extends Controller
{
    /**
     * GET /api/varieties
     * Return daftar varieties aktif beserta commodity terkait (ringkas).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Variety::query()
            ->where('is_active', true)
            ->with(['commodity' => function ($q) {
                $q->select('id', 'name', 'slug');
            }])
            ->select(['id', 'commodity_id', 'name', 'slug', 'sku', 'price', 'minimum_limit', 'image_path'])
            ->orderBy('name');

        // Optional: filter by commodity slug (?commodity=slug)
        if ($commoditySlug = $request->query('commodity')) {
            $query->whereHas('commodity', function ($q) use ($commoditySlug) {
                $q->where('slug', $commoditySlug);
            });
        }

        $varieties = $query->get()->map(function (Variety $v) {
            return [
                'id' => $v->id,
                'name' => $v->name,
                'slug' => $v->slug,
                'sku' => $v->sku,
                // Harga di DB disimpan sebagai rupiah (integer). Untuk perhitungan gunakan sen.
                'price_cents' => ((int) $v->price) * 100,
                // Format tampilan IDR tanpa desimal, pemisah ribuan '.'
                'price_idr' => 'Rp '.number_format((int) $v->price, 0, ',', '.'),
                'minimum_limit' => (int) ($v->minimum_limit ?? 0),
                'image_path' => $v->image_path,
                'image_url' => $v->image_path ? Storage::disk('public')->url($v->image_path) : null,
                'commodity' => [
                    'name' => optional($v->commodity)->name,
                    'slug' => optional($v->commodity)->slug,
                ],
            ];
        });

        return response()->json([
            'data' => $varieties,
            'meta' => [
                'count' => $varieties->count(),
            ],
        ]);
    }

    /**
     * GET /api/varieties/{slug}
     * Return detail variety berdasarkan slug.
     */
    public function show(string $slug): JsonResponse
{
    $v = Variety::query()
        ->with([
            'commodity:id,name,slug',
            'seedLots' => function ($q) {
                $q->select('id', 'variety_id', 'seed_class_id', 'quantity',
                    'unit', 'is_sellable', 'production_year');
            },
            'seedLots.seedClass:id,code,name'
        ])
        ->where('is_active', true)
        ->where('slug', $slug)
        ->select(['id', 'commodity_id', 'name', 'slug', 'sku',
            'price', 'minimum_limit', 'image_path', 'description'])
        ->firstOrFail();

    // Mapping Seed Lots untuk frontend
    $seedLots = $v->seedLots->map(function ($sl) {
        return [
            'id' => $sl->id,
            'lot_code' => $sl->lot_code, // Added lot_code
            'price_per_unit' => $sl->price_per_unit, // Added price
            'quantity' => $sl->quantity,
            'unit' => $sl->unit,
            'is_sellable' => (bool) $sl->is_sellable,
            'production_year' => $sl->production_year,
            'seed_class' => [
                'code' => optional($sl->seedClass)->code,
                'name' => optional($sl->seedClass)->name,
            ]
        ];
    });

    // Group stok berdasarkan seed class
    $stockByClass = $v->seedLots
        ->filter(fn ($sl) => $sl->is_sellable && $sl->quantity > 0)
        ->groupBy(fn ($sl) => optional($sl->seedClass)->code)
        ->map(fn ($items) => $items->sum('quantity'));

    $payload = [
        'id' => $v->id,
        'name' => $v->name,
        'slug' => $v->slug,
        'sku' => $v->sku,
        'description' => $v->description,
        'image_path' => $v->image_path,
        'image_url' => $v->image_path ? Storage::disk('public')->url($v->image_path) : null,
        'price_cents' => ((int) $v->price) * 100,
        'price_idr' => 'Rp '.number_format((int) $v->price, 0, ',', '.'),
        'minimum_limit' => (int) ($v->minimum_limit ?? 0),
        'commodity' => [
            'name' => optional($v->commodity)->name,
            'slug' => optional($v->commodity)->slug,
        ],
        'stock' => [
            'total_stock_kg' => $v->total_stock,
            'status' => $v->stock_status,
        ],
        'stock_by_class' => $stockByClass,
        'seed_lots' => $seedLots,
    ];

    return response()->json(['data' => $payload]);
}

    /**
     * GET /api/seed-classes/{id}/varieties
     * Return daftar varieties yang memiliki seed lots pada seed class tertentu.
     */
    public function bySeedClass(int $id): JsonResponse
    {
        // Ambil seed lots yang sellable untuk kelas tertentu
        $seedLots = \App\Models\SeedLot::query()
            ->where('seed_class_id', $id)
            ->where('is_sellable', true)
            ->where('quantity', '>', 0)
            ->select(['id', 'variety_id', 'seed_class_id', 'quantity', 'unit'])
            ->get();

        if ($seedLots->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $varietyIds = $seedLots->pluck('variety_id')->unique()->values();

        $varieties = Variety::query()
            ->whereIn('id', $varietyIds)
            ->with(['commodity' => function ($q) { $q->select('id','name','slug'); }])
            ->select(['id','commodity_id','name','slug','sku','price','minimum_limit','image_path'])
            ->orderBy('name')
            ->get()
            ->map(function (Variety $v) use ($seedLots, $id) {
                $lotsForVariety = $seedLots->where('variety_id', $v->id);
                $stockForClass = (float) $lotsForVariety->sum('quantity');

                return [
                    'id' => $v->id,
                    'name' => $v->name,
                    'slug' => $v->slug,
                    'sku' => $v->sku,
                    'price_cents' => ((int) $v->price) * 100,
                    'price_idr' => 'Rp '.number_format((int) $v->price, 0, ',', '.'),
                    'minimum_limit' => (int) ($v->minimum_limit ?? 0),
                    'image_url' => $v->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($v->image_path) : null,
                    'commodity' => [
                        'name' => optional($v->commodity)->name,
                        'slug' => optional($v->commodity)->slug,
                    ],
                    'stock_by_class' => [
                        'class_id' => $id,
                        'total' => $stockForClass,
                    ],
                ];
            });

        return response()->json(['data' => $varieties]);
    }
}
