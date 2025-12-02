<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SeedClassIndexRequest;
use App\Models\SeedClass;
use Illuminate\Http\JsonResponse;

class SeedClassController extends Controller
{
    public function index(SeedClassIndexRequest $request): JsonResponse
    {
        $query = SeedClass::query()
            ->select(['id', 'code', 'name', 'description', 'is_active'])
            ->orderBy('name');

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        if ($q = $request->validated('q')) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $classes = $query->get()->map(function (SeedClass $c) {
            return [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'description' => $c->description,
                'is_active' => (bool) $c->is_active,
            ];
        });

        return response()->json([
            'data' => $classes,
            'meta' => ['count' => $classes->count()],
        ]);
    }

    public function show(string $code): JsonResponse
    {
        $c = SeedClass::query()
            ->where('code', $code)
            ->select(['id', 'code', 'name', 'description', 'is_active'])
            ->firstOrFail();

        $payload = [
            'id' => $c->id,
            'code' => $c->code,
            'name' => $c->name,
            'description' => $c->description,
            'is_active' => (bool) $c->is_active,
        ];

        return response()->json(['data' => $payload]);
    }
}

