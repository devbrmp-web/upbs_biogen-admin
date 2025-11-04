<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use Illuminate\Http\JsonResponse;

class CommodityController extends Controller
{
    /**
     * GET /api/commodities
     * Return daftar commodities aktif untuk konsumsi frontend client.
     */
    public function index(): JsonResponse
    {
        $commodities = Commodity::query()
            ->where('is_active', true)
            ->select(['id', 'name', 'slug', 'image_path'])
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'image' => $c->image_path,
            ]);

        return response()->json([
            'data' => $commodities,
            'meta' => [
                'count' => $commodities->count(),
            ],
        ]);
    }
}