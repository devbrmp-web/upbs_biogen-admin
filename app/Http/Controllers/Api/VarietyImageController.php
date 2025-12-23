<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Variety;
use App\Models\VarietyImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VarietyImageController extends Controller
{
    public function store(Request $request, int $variety): JsonResponse
    {
        $varietyModel = Variety::query()->whereKey($variety)->firstOrFail();

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if (! in_array((int) ($user->role_id ?? 0), [1, 2], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['required', 'file', 'mimetypes:image/jpeg,image/png', 'max:5120'],
        ]);

        $files = $request->file('images', []);
        $created = [];

        DB::transaction(function () use ($varietyModel, $files, &$created) {
            $lastOrder = (int) ($varietyModel->images()->max('order') ?? 0);
            $hasAny = $varietyModel->images()->exists();

            foreach ($files as $file) {
                $lastOrder++;

                $ext = strtolower((string) ($file->guessExtension() ?: $file->extension() ?: 'jpg'));
                if ($ext === 'jpeg') {
                    $ext = 'jpg';
                }

                $timestamp = now()->format('YmdHisv');
                $rand = Str::lower(Str::random(8));
                $filename = "variety_{$varietyModel->id}_{$timestamp}_{$rand}.{$ext}";

                $path = Storage::disk('public')->putFileAs('varieties', $file, $filename);

                $isPrimary = ! $hasAny && count($created) === 0;

                $image = VarietyImage::create([
                    'variety_id' => $varietyModel->id,
                    'image_path' => $path,
                    'is_primary' => $isPrimary,
                    'order' => $lastOrder,
                ]);

                $created[] = $image;
            }
        });

        return response()->json([
            'data' => collect($created)->map(fn (VarietyImage $img) => $this->serialize($img))->values(),
        ], 201);
    }

    public function setPrimary(Request $request, int $variety, VarietyImage $image): JsonResponse
    {
        $varietyModel = Variety::query()->whereKey($variety)->firstOrFail();

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if (! in_array((int) ($user->role_id ?? 0), [1, 2], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        if ($image->variety_id !== $varietyModel->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        DB::transaction(function () use ($varietyModel, $image) {
            $varietyModel->images()->where('is_primary', true)->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        $image->refresh();

        return response()->json(['data' => $this->serialize($image)], 200);
    }

    public function destroy(Request $request, int $variety, VarietyImage $image): JsonResponse
    {
        $varietyModel = Variety::query()->whereKey($variety)->firstOrFail();

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if (! in_array((int) ($user->role_id ?? 0), [1, 2], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        if ($image->variety_id !== $varietyModel->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $count = $varietyModel->images()->count();
        if ($count <= 1) {
            return response()->json([
                'message' => 'Tidak bisa menghapus gambar terakhir pada varietas.',
            ], 422);
        }

        DB::transaction(function () use ($varietyModel, $image) {
            if ($image->is_primary) {
                $replacement = $varietyModel->images()
                    ->where('id', '!=', $image->id)
                    ->orderBy('order')
                    ->orderBy('id')
                    ->first();

                $image->update(['is_primary' => false]);
                $image->delete();

                if ($replacement) {
                    $replacement->update(['is_primary' => true]);
                }

                return;
            }

            $image->delete();
        });

        return response()->json(['message' => 'Deleted.'], 200);
    }

    private function serialize(VarietyImage $img): array
    {
        return [
            'id' => $img->id,
            'variety_id' => $img->variety_id,
            'image_path' => $img->image_path,
            'image_url' => $img->image_url,
            'is_primary' => (bool) $img->is_primary,
            'order' => (int) ($img->order ?? 0),
            'created_at' => optional($img->created_at)->toISOString(),
            'updated_at' => optional($img->updated_at)->toISOString(),
        ];
    }
}
