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
        try {
            $query = Variety::query()
                ->where('is_active', true)
                ->with([
                    'commodity' => function ($q) {
                        $q->select('id', 'name', 'slug');
                    },
                    // Hindari LIMIT pada eager loading untuk kompatibilitas luas MySQL
                    'images' => function($q) {
                        $q->orderBy('order')->orderBy('id');
                    }
                ])
                ->withStockCalculations()
                ->withPriceRange()
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
                    'minimum_limit' => (int) ($v->minimum_limit ?? 0),
                    'image_path' => $v->image_path,
                    'image_url' => $v->image_path ? Storage::disk('public')->url($v->image_path) : null,
                    // Ambil maksimal 4 gambar di PHP-side untuk kompatibilitas
                    'images' => $v->images
                        ->sortBy('order')
                        ->sortBy('id')
                        ->take(4)
                        ->map(fn($img) => [
                            'image_url' => $img->image_url,
                            'is_primary' => (bool) $img->is_primary,
                        ]),
                    'commodity' => [
                        'name' => optional($v->commodity)->name,
                        'slug' => optional($v->commodity)->slug,
                    ],
                    'stock' => [
                        'total_weight_kg' => (float) $v->total_stock,
                        'total_unit_qty' => (float) ($v->total_unit_stock_calculated ?? 0),
                        'status' => $v->stock_status,
                        'details' => $v->getStocksByClass(),
                    ],
                    'price_range_text' => $v->price_range,
                ];
            });

            return response()->json([
                'data' => $varieties,
                'meta' => [
                    'count' => $varieties->count(),
                ],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Database query error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/varieties/{slug}
     * Return detail variety berdasarkan slug.
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $v = Variety::query()
                ->with([
                    'commodity:id,name,slug',
                    'images' => function ($q) {
                        $q->orderBy('order')->orderBy('id');
                    },
                    'seedLots' => function ($q) {
                        $q->select('id', 'variety_id', 'seed_class_id', 'lot_code', 'price_per_unit',
                            'quantity', 'unit', 'is_sellable', 'production_year')
                          ->where('is_sellable', true)
                          ->where('quantity', '>', 0);
                    },
                    // Eager-load semua kolom seed_class agar price & step tersedia
                    'seedLots.seedClass',
                ])
                ->where('is_active', true)
                ->where('slug', $slug)
                ->select(['id', 'commodity_id', 'name', 'slug', 'sku',
                    'minimum_limit', 'image_path', 'description'])
                ->firstOrFail();

            // ── Mapping Seed Lots (sertakan seed_class.id agar view bisa filter) ──
            $seedLots = $v->seedLots->map(function ($sl) {
                return [
                    'id'                   => $sl->id,
                    'lot_code'             => $sl->lot_code,
                    'price_per_unit'       => (int) $sl->price_per_unit,
                    'price_per_unit_cents' => ((int) $sl->price_per_unit) * 100,
                    'quantity'             => (int) $sl->quantity,
                    'unit'                 => $sl->unit,
                    'is_sellable'          => (bool) $sl->is_sellable,
                    'production_year'      => $sl->production_year,
                    'seed_class' => [
                        'id'   => optional($sl->seedClass)->id,
                        'code' => optional($sl->seedClass)->code,
                        'name' => optional($sl->seedClass)->name,
                    ],
                ];
            });

            // ── Group stok per kelas benih (kode → total qty) ─────────────────
            $stockByClass = $v->seedLots
                ->filter(fn ($sl) => $sl->is_sellable && $sl->quantity > 0)
                ->groupBy(fn ($sl) => optional($sl->seedClass)->code)
                ->map(fn ($items) => $items->sum('quantity'));

            // ── Stock details (getStocksByClass + enriched dengan price & step) ─
            //
            // getStocksByClass() mengembalikan semua kelas benih aktif.
            // Kita enrich dengan price (min price dari seed_lots yang ada) dan
            // field step_increment / min_order_qty dari seed_class.
            $stockDetails = $v->getStocksByClass()->map(function ($classData) use ($v) {
                $seedClassId = $classData['class_id'];

                // Ambil harga terendah dari lots yang tersedia untuk kelas ini
                $lowestPriceLot = $v->seedLots
                    ->filter(fn ($sl) =>
                        optional($sl->seedClass)->id == $seedClassId
                        && $sl->is_sellable
                        && $sl->quantity > 0
                    )
                    ->sortBy('price_per_unit')
                    ->first();

                // Ambil seed_class model untuk step_increment & min_order_qty
                $scModel = $lowestPriceLot?->seedClass
                    ?? \App\Models\SeedClass::find($seedClassId);

                return array_merge($classData, [
                    'price'          => $lowestPriceLot ? (int) $lowestPriceLot->price_per_unit : 0,
                    'step_increment' => (int) ($scModel->step_increment ?? 1),
                    'min_order_qty'  => (int) ($scModel->min_order_qty ?? 1),
                ]);
            });

            // ── Map images ────────────────────────────────────────────────────
            $images = $v->images->map(function ($img) {
                return [
                    'id'         => $img->id,
                    'image_url'  => $img->image_url,
                    'is_primary' => (bool) $img->is_primary,
                    'order'      => $img->order,
                ];
            });

            $payload = [
                'id'             => $v->id,
                'name'           => $v->name,
                'slug'           => $v->slug,
                'sku'            => $v->sku,
                'description'    => $v->description,
                'image_path'     => $v->image_path,
                'image_url'      => $v->image_path ? Storage::disk('public')->url($v->image_path) : null,
                'images'         => $images,
                'minimum_limit'  => (int) ($v->minimum_limit ?? 0),
                'commodity' => [
                    'name' => optional($v->commodity)->name,
                    'slug' => optional($v->commodity)->slug,
                ],
                'stock' => [
                    'total_weight_kg' => (float) $v->total_stock,
                    'total_unit_qty'  => (float) ($v->total_unit_stock_calculated ?? 0),
                    'status'          => $v->stock_status,
                    'details'         => $stockDetails,
                ],
                'stock_by_class' => $stockByClass,
                'seed_lots'      => $seedLots,
            ];

            return response()->json(['data' => $payload]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Database query error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/seed-classes/{id}/varieties
     * Return daftar varieties yang memiliki seed lots pada seed class tertentu.
     */
    public function bySeedClass(int $id): JsonResponse
    {
        try {
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
                ->with([
                    'commodity' => function ($q) { $q->select('id','name','slug'); },
                    'images' => function($q) {
                        $q->orderBy('order')->orderBy('id');
                    }
                ])
                ->select(['id','commodity_id','name','slug','sku','minimum_limit','image_path'])
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
                        'minimum_limit' => (int) ($v->minimum_limit ?? 0),
                        'image_url' => $v->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($v->image_path) : null,
                        'images' => $v->images
                            ->sortBy('order')
                            ->sortBy('id')
                            ->take(4)
                            ->map(fn($img) => [
                                'image_url' => $img->image_url,
                                'is_primary' => (bool) $img->is_primary,
                            ]),
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
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Database query error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
