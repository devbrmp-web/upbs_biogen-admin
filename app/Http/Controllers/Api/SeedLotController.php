<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SeedLotIndexRequest;
use App\Models\SeedLot;
use Illuminate\Http\JsonResponse;

class SeedLotController extends Controller
{
    public function index(SeedLotIndexRequest $request): JsonResponse
    {
        $query = SeedLot::query()
            ->with([
                'variety' => function ($q) {
                    $q->select('id', 'name', 'slug');
                },
                'seedClass' => function ($q) {
                    $q->select('id', 'code', 'name');
                },
            ])
            ->select([
                'id', 'lot_code', 'variety_id', 'seed_class_id', 'production_year', 'quantity', 'unit', 'price_per_unit', 'is_sellable', 'description',
            ])
            ->orderByDesc('updated_at');

        if ($request->boolean('sellable_only', true)) {
            $query->where('is_sellable', true);
        }

        if ($request->validated('variety_id')) {
            $query->where('variety_id', (int) $request->validated('variety_id'));
        }

        if ($request->validated('variety_slug')) {
            $slug = $request->validated('variety_slug');
            $query->whereHas('variety', function ($q) use ($slug) {
                $q->where('slug', $slug);
            });
        }

        if ($request->validated('seed_class_id')) {
            $query->where('seed_class_id', (int) $request->validated('seed_class_id'));
        }

        if ($request->validated('seed_class_code')) {
            $code = $request->validated('seed_class_code');
            $query->whereHas('seedClass', function ($q) use ($code) {
                $q->where('code', $code);
            });
        }

        if ($request->validated('production_year')) {
            $query->where('production_year', (int) $request->validated('production_year'));
        }

        $minStock = (int) ($request->validated('min_stock') ?? 1);
        if ($minStock > 0) {
            $query->where('quantity', '>=', $minStock);
        }

        $lots = $query->get()->map(function (SeedLot $sl) {
            return [
                'id' => $sl->id,
                'lot_code' => $sl->lot_code,
                'quantity' => (int) $sl->quantity,
                'unit' => $sl->unit,
                'price_per_unit_cents' => ((int) $sl->price_per_unit) * 100,
                'price_idr' => 'Rp ' . number_format((int) $sl->price_per_unit, 0, ',', '.'),
                'is_sellable' => (bool) $sl->is_sellable,
                'production_year' => (int) $sl->production_year,
                'variety' => [
                    'id' => optional($sl->variety)->id,
                    'name' => optional($sl->variety)->name,
                    'slug' => optional($sl->variety)->slug,
                ],
                'seed_class' => [
                    'id' => optional($sl->seedClass)->id,
                    'code' => optional($sl->seedClass)->code,
                    'name' => optional($sl->seedClass)->name,
                ],
                'description' => $sl->description,
            ];
        });

        return response()->json([
            'data' => $lots,
            'meta' => ['count' => $lots->count()],
        ]);
    }

    public function show(string $lotCode): JsonResponse
    {
        $sl = SeedLot::query()
            ->with([
                'variety' => function ($q) {
                    $q->select('id', 'name', 'slug');
                },
                'seedClass' => function ($q) {
                    $q->select('id', 'code', 'name');
                },
            ])
            ->where('lot_code', $lotCode)
            ->select([
                'id', 'lot_code', 'variety_id', 'seed_class_id', 'production_year', 'quantity', 'unit', 'price_per_unit', 'is_sellable', 'description',
            ])
            ->firstOrFail();

        $payload = [
            'id' => $sl->id,
            'lot_code' => $sl->lot_code,
            'quantity' => (int) $sl->quantity,
            'unit' => $sl->unit,
            'price_per_unit_cents' => ((int) $sl->price_per_unit) * 100,
            'price_idr' => 'Rp ' . number_format((int) $sl->price_per_unit, 0, ',', '.'),
            'is_sellable' => (bool) $sl->is_sellable,
            'production_year' => (int) $sl->production_year,
            'variety' => [
                'id' => optional($sl->variety)->id,
                'name' => optional($sl->variety)->name,
                'slug' => optional($sl->variety)->slug,
            ],
            'seed_class' => [
                'id' => optional($sl->seedClass)->id,
                'code' => optional($sl->seedClass)->code,
                'name' => optional($sl->seedClass)->name,
            ],
            'description' => $sl->description,
        ];

        return response()->json(['data' => $payload]);
    }
}

