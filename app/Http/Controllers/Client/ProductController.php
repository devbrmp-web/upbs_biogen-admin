<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Variety;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $variety = Variety::query()
            ->with([
                'commodity:id,name,slug',
                'images:id,variety_id,image_path,is_primary,order',
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->select(['id','commodity_id','name','slug','sku','price','description','image_path'])
            ->firstOrFail();

        $images = $variety->images->sortBy(['order', 'id'])->values();
        if ($images->isEmpty() && $variety->image_path) {
            $images = collect([
                (object)[
                    'id' => 0,
                    'image_path' => $variety->image_path,
                    'is_primary' => true,
                ],
            ]);
        }

        $imageUrls = $images->map(function ($img) {
            return [
                'id' => $img->id,
                'url' => $img->image_path ? Storage::disk('public')->url($img->image_path) : null,
                'is_primary' => (bool) ($img->is_primary ?? false),
            ];
        });

        return view('client.product.show', [
            'variety' => $variety,
            'images' => $imageUrls,
        ]);
    }
}
