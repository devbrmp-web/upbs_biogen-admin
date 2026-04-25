<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\Variety;
use App\Models\VarietyImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VarietyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Variety::with(['commodity', 'images', 'seedLots' => function ($query) {
            $query->where('is_sellable', true)->where('unit', 'kg');
        }])
            ->withCount(['seedLots as seed_lots_count'])
            ->withStockCalculations()
            ->withPriceRange();

        // Filters: q (name/sku or commodity name), commodity (commodity_id), stock_status
        // Support both 'q' and 'search' parameters for flexibility
        $searchQuery = $request->string('q')->trim()->toString() ?: $request->string('search')->trim()->toString();

        if ($searchQuery) {
            $query->where(function ($builder) use ($searchQuery) {
                $builder->where('name', 'like', "%{$searchQuery}%")
                    ->orWhere('sku', 'like', "%{$searchQuery}%")
                    ->orWhereHas('commodity', function ($commodity) use ($searchQuery) {
                        $commodity->where('name', 'like', "%{$searchQuery}%");
                    });
            });
        }

        // Filter by commodity (matching view parameter name)
        if ($commodityId = $request->integer('commodity')) {
            $query->where('commodity_id', $commodityId);
        }

        // Filter by stock status using optimized scopes
        if ($stockStatus = $request->string('stock_status')->trim()->toString()) {
            $query->byStockStatus($stockStatus);
        }

        $varieties = $query->latest('updated_at')->paginate(10)->appends($request->query());
        $commodities = Commodity::orderBy('name')->get();

        // AJAX partial rendering for progressive enhancement (ignore query ?ajax=1 on normal navigation)
        if ($request->ajax()) {
            return view('admin.varieties.partials.table-content', compact('varieties'));
        }

        return view('admin.varieties.index', compact('varieties', 'commodities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $commodities = Commodity::orderBy('name')->get();
        $selectedCommodityId = request()->input('commodity_id');

        return view('admin.varieties.create', compact('commodities', 'selectedCommodityId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commodity_id' => 'required|exists:commodities,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:varieties,slug',
            'sku' => 'nullable|string|max:100|unique:varieties,sku',
            'description' => 'nullable|string',
            'minimum_limit' => 'nullable|integer|min:0',
            'status' => 'nullable|in:available,out_of_stock,discontinued',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'temp_image_path' => 'nullable|string',
        ], [
            'image.max' => 'File terlalu besar (Maks 10MB).',
            'image.required' => 'Gambar wajib diunggah.',
        ]);

        $validator->after(function ($v) use ($request) {
            $tempPath = $request->string('temp_image_path')->toString();
            if (! $request->hasFile('image') && $tempPath === '') {
                $v->errors()->add('image', 'Gambar wajib diunggah.');
                return;
            }
            if ($tempPath !== '' && ! Storage::disk('public')->exists($tempPath)) {
                $v->errors()->add('image', 'Gambar tidak ditemukan, silakan upload ulang.');
            }
        });

        $validated = $validator->validate();

        $tempPath = $validated['temp_image_path'] ?? '';
        unset($validated['temp_image_path']);

        if ($request->hasFile('image')) {
            $validated['image_path'] = Storage::disk('public')->putFile('varieties', $request->file('image'));
        } else {
            if ($tempPath !== '') {
                $ext = pathinfo($tempPath, PATHINFO_EXTENSION) ?: 'jpg';
                $finalPath = 'varieties/' . Str::uuid()->toString() . '.' . $ext;
                if (! Storage::disk('public')->move($tempPath, $finalPath)) {
                    return back()->withErrors(['image' => 'Gagal memindahkan gambar. Silakan upload ulang.'])->withInput();
                }
                $validated['image_path'] = $finalPath;
            }
        }

        // Normalize nullable inputs to 0 (DB columns are non-nullable dengan default 0)
        $validated['minimum_limit'] = $validated['minimum_limit'] ?? 0;

        Variety::create($validated);

        return redirect()->to($this->sanitizeReturnUrl($request, route('admin.varieties.index')))
            ->with('success', 'Variety created successfully.');
    }

    public function tempImageUpload(Request $request)
    {
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        $postMax = $this->bytesFromIni((string) ini_get('post_max_size'));
        if ($contentLength > 0 && $postMax > 0 && $contentLength > $postMax && ! $request->hasFile('image')) {
            return response()->json(['message' => 'File terlalu besar (Maks 10MB).'], 413);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ], [
            'image.max' => 'File terlalu besar (Maks 10MB).',
            'image.required' => 'Gambar wajib diunggah.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = Storage::disk('public')->putFile('tmp/varieties', $request->file('image'));

        return response()->json([
            'path' => $path,
        ]);
    }

    private function bytesFromIni(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $unit = strtolower(substr($value, -1));
        $number = (int) $value;
        if ($unit === 'g') {
            return $number * 1024 * 1024 * 1024;
        }
        if ($unit === 'm') {
            return $number * 1024 * 1024;
        }
        if ($unit === 'k') {
            return $number * 1024;
        }
        return (int) $value;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Variety $variety)
    {
        $variety->load(['commodity']);

        // Build seed lots query with search and filters
        $seedLotsQuery = $variety->seedLots()->with('seedClass');

        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $seedLotsQuery->where('lot_code', 'like', "%{$searchTerm}%");
        }

        // Apply seed class filter
        if ($request->filled('seed_class')) {
            $seedLotsQuery->where('seed_class_id', $request->seed_class);
        }

        // Apply sellable status filter
        if ($request->filled('is_sellable')) {
            $seedLotsQuery->where('is_sellable', $request->is_sellable);
        }

        // Load filtered seed lots
        $variety->setRelation('seedLots', $seedLotsQuery->orderBy('created_at', 'desc')->get());

        // Load aggregate data for stock calculations
        $variety->loadCount(['seedLots as seed_lots_count']);

        // Load detailed stock summary using the new model methods
        $variety->stock_summary = $variety->getStocksByClass();
        $variety->total_weight_stock_calculated = $variety->total_stock; // Uses accessor
        
        // Find total unit stock if any
        $variety->total_unit_stock_calculated = $variety->seedLots()
            ->where('is_sellable', true)
            ->whereHas('seedClass', function($q) {
                $q->where('stock_category', 'unit');
            })
            ->sum('quantity');

        return view('admin.varieties.show', compact('variety'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Variety $variety)
    {
        $commodities = Commodity::orderBy('name')->get();

        return view('admin.varieties.edit', compact('variety', 'commodities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Variety $variety)
    {
        $validated = $request->validate([
            'commodity_id' => 'required|exists:commodities,id',
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('varieties', 'slug')->ignore($variety->id),
            ],
            // SKU opsional pada update juga; hormati nilai yang diinput admin
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('varieties', 'sku')->ignore($variety->id),
            ],
            'description' => 'nullable|string',
            'minimum_limit' => 'nullable|integer|min:0',
            'status' => 'nullable|in:available,out_of_stock,discontinued',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($request->hasFile('image')) {
            // Hapus file lama jika ada
            if ($variety->image_path) {
                Storage::disk('public')->delete($variety->image_path);
            }
            // Simpan file baru dengan nama unik di disk public
            $validated['image_path'] = Storage::disk('public')->putFile('varieties', $request->file('image'));
        }

        // Normalize nullable inputs to 0 (DB columns are non-nullable dengan default 0)
        $validated['minimum_limit'] = $validated['minimum_limit'] ?? 0;

        $variety->update($validated);

        return redirect()->to($this->sanitizeReturnUrl($request, route('admin.varieties.index')))
            ->with('success', 'Variety updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Variety $variety)
    {
        if ($variety->orderItems()->exists()) {
            return back()->with('error', 'Cannot delete. This variety already has order transaction history.');
        }

        if ($variety->seedLots()->exists()) {
            return back()->with('error', 'Cannot delete. This variety still has registered seed lots.');
        }

        try {
            $mainImagePath = $variety->image_path;
            $imagePaths = VarietyImage::query()
                ->where('variety_id', $variety->id)
                ->pluck('image_path')
                ->filter()
                ->values()
                ->all();

            DB::transaction(function () use ($variety) {
                VarietyImage::query()->where('variety_id', $variety->id)->forceDelete();
                $variety->delete();
            });

            $pathsToDelete = array_values(array_unique(array_filter(array_merge(
                $imagePaths,
                [$mainImagePath],
            ))));
            foreach ($pathsToDelete as $path) {
                Storage::disk('public')->delete($path);
            }

            return redirect()->to($this->sanitizeReturnUrl($request, route('admin.varieties.index')))
                ->with('success', 'Variety deleted permanently.');

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error', 'Cannot delete. This record is referenced by other data.');
            }
            throw $e;
        }
    }

    /**
     * Store multiple images for a variety.
     */
    public function storeImages(Request $request, Variety $variety)
    {
        $files = $request->file('images');
        if (! is_array($files) || count($files) === 0) {
            $files = array_values($request->allFiles());
        }
        if (! is_array($files) || count($files) === 0) {
            return back()->withErrors(['images' => 'No images provided.'])->withInput();
        }
        foreach ($files as $f) {
            $validator = Validator::make(['image' => $f], [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            ]);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }
        $existingCount = (int) $variety->images()->count();
        $newCount = count($files);
        if ($existingCount + $newCount > 6) {
            return back()->withErrors(['images' => 'Maximum 6 images per variety.'])->withInput();
        }
        $lastOrder = (int) ($variety->images()->max('order') ?? 0);
        foreach ($files as $file) {
            $path = Storage::disk('public')->putFile('varieties', $file);
            $lastOrder++;
            VarietyImage::create([
                'variety_id' => $variety->id,
                'image_path' => $path,
                'order' => $lastOrder,
                'is_primary' => false,
            ]);
        }

        return back()->with('success', 'Images uploaded successfully.');
    }

    /**
     * Delete a single image from a variety.
     */
    public function destroyImage(Request $request, Variety $variety, VarietyImage $image)
    {
        if ($image->variety_id !== $variety->id) {
            abort(404);
        }

        if ($image->image_path) {
            Storage::disk('public')->delete($image->image_path);
        }
        $image->delete();

        return back()->with('success', 'Image deleted successfully.');
    }

    /**
     * Reorder images for a variety.
     */
    public function reorderImages(Request $request, Variety $variety)
    {
        $data = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:variety_images,id',
        ]);

        $ids = $data['order'];
        $validIds = $variety->images()->pluck('id')->all();
        foreach ($ids as $id) {
            if (! in_array($id, $validIds, true)) {
                abort(422);
            }
        }

        DB::transaction(function () use ($ids, $variety) {
            $position = 1;
            foreach ($ids as $id) {
                VarietyImage::where('id', $id)->where('variety_id', $variety->id)->update(['order' => $position]);
                $position++;
            }
        });

        return response()->json(['status' => 'ok']);
    }

    /**
     * Set primary image for a variety.
     */
    public function setPrimaryImage(Request $request, Variety $variety, VarietyImage $image)
    {
        if ($image->variety_id !== $variety->id) {
            abort(404);
        }

        DB::transaction(function () use ($variety, $image) {
            VarietyImage::where('variety_id', $variety->id)->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
            $variety->update(['image_path' => $image->image_path]);
        });

        return back()->with('success', 'Primary image updated.');
    }

    /**
     * Sanitize return URL to prevent open redirects and ensure it stays within app domain.
     */
    private function sanitizeReturnUrl(Request $request, string $fallbackUrl): string
    {
        $return = $request->string('return')->trim()->toString();
        if (! $return) {
            return $fallbackUrl;
        }

        // Basic hardening
        if (Str::contains($return, ['javascript:', "\n", "\r"])) {
            return $fallbackUrl;
        }

        $appUrl = rtrim(config('app.url'), '/');
        if (Str::startsWith($return, [$appUrl, '/'])) {
            return $return;
        }

        return $fallbackUrl;
    }
}
