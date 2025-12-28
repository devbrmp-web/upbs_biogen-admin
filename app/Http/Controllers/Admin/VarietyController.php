<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\Variety;
use App\Models\VarietyImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VarietyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Variety::with(['commodity', 'images', 'seedLots' => function($query) {
            $query->where('is_sellable', true)->where('unit', 'kg');
        }])
        ->withCount(['seedLots as seed_lots_count'])
        ->withStockCalculations();

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

        return view('admin.varieties.create', compact('commodities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'commodity_id' => 'required|exists:commodities,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:varieties,slug',
            // SKU kini opsional; jika kosong akan digenerate otomatis oleh model
            'sku' => 'nullable|string|max:100|unique:varieties,sku',
            'description' => 'required|string',
            'price' => 'required|numeric|integer|min:0',
            'minimum_limit' => 'nullable|integer|min:0',
            // Wajib pada create; opsional pada update
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = Storage::disk('public')->putFile('varieties', $request->file('image'));
        }

        // Normalize nullable inputs to 0 (DB columns are non-nullable dengan default 0)
        $validated['minimum_limit'] = $validated['minimum_limit'] ?? 0;

        // Harden price as integer
        $validated['price'] = (int) $validated['price'];

        Variety::create($validated);

        return redirect()->to($this->sanitizeReturnUrl($request, route('admin.varieties.index')))
            ->with('success', 'Variety created successfully.');
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

        // Calculate stock totals using aggregate queries
        $stockTotals = $variety->seedLots()
            ->where('is_sellable', true)
            ->where('unit', 'kg')
            ->selectRaw('
                SUM(quantity) as total_stock,
                SUM(CASE WHEN seed_class_id IN (SELECT id FROM seed_classes WHERE code = "BS") THEN quantity ELSE 0 END) as bs_stock,
                SUM(CASE WHEN seed_class_id IN (SELECT id FROM seed_classes WHERE code = "FS") THEN quantity ELSE 0 END) as fs_stock
            ')
            ->first();

        $variety->total_stock_calculated = $stockTotals->total_stock ?? 0;
        $variety->bs_stock_calculated = $stockTotals->bs_stock ?? 0;
        $variety->fs_stock_calculated = $stockTotals->fs_stock ?? 0;

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
            'description' => 'required|string',
            'price' => 'required|numeric|integer|min:0',
            'minimum_limit' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
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
        // Note: planlet is now calculated dynamically from seed lots via total_planlet accessor
        $validated['minimum_limit'] = $validated['minimum_limit'] ?? 0;

        // Harden price as integer
        $validated['price'] = (int) $validated['price'];

        $variety->update($validated);

        return redirect()->to($this->sanitizeReturnUrl($request, route('admin.varieties.index')))
            ->with('success', 'Variety updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Variety $variety)
    {
        // Delete image if exists
        if ($variety->image_path) {
            Storage::disk('public')->delete($variety->image_path);
        }

        $variety->delete();

        return redirect()->to($this->sanitizeReturnUrl($request, route('admin.varieties.index')))
            ->with('success', 'Variety deleted successfully.');
    }

    /**
     * Store multiple images for a variety.
     */
    public function storeImages(Request $request, Variety $variety)
    {
        $files = $request->file('images');
        if (!is_array($files) || count($files) === 0) {
            $files = array_values($request->allFiles());
        }
        if (!is_array($files) || count($files) === 0) {
            return back()->withErrors(['images' => 'No images provided.'])->withInput();
        }
        foreach ($files as $f) {
            $validator = Validator::make(['image' => $f], [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
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
