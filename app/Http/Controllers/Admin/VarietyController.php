<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Variety;
use App\Models\Commodity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VarietyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Variety::with(['commodity', 'seedLots' => function($query) {
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
            'price' => 'required|numeric|min:0',
            'stock_bs_kg' => 'nullable|integer|min:0',
            'stock_fs_kg' => 'nullable|integer|min:0',
            'planlet' => 'nullable|integer|min:0',
            'minimum_limit' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('varieties', 'public');
        }

        // Normalize nullable stock inputs to 0 (DB columns are non-nullable dengan default 0)
        $validated['stock_bs_kg'] = $validated['stock_bs_kg'] ?? 0;
        $validated['stock_fs_kg'] = $validated['stock_fs_kg'] ?? 0;
        $validated['planlet'] = $validated['planlet'] ?? 0;
        $validated['minimum_limit'] = $validated['minimum_limit'] ?? 0;
        // Hitung total stock (kg) secara otomatis dari BS + FS (Planlet tidak dihitung)
        $validated['stock'] = ($validated['stock_bs_kg'] + $validated['stock_fs_kg']);

        Variety::create($validated);

        return redirect()->route('admin.varieties.index')
            ->with('success', 'Variety created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Variety $variety)
    {
        $variety->load(['commodity', 'seedLots.seedClass']);
        
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
            'price' => 'required|numeric|min:0',
            'stock_bs_kg' => 'nullable|integer|min:0',
            'stock_fs_kg' => 'nullable|integer|min:0',
            'planlet' => 'nullable|integer|min:0',
            'minimum_limit' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($variety->image_path) {
                Storage::disk('public')->delete($variety->image_path);
            }
            
            $validated['image_path'] = $request->file('image')->store('varieties', 'public');
        }

        // Normalize nullable stock inputs to 0 (DB columns are non-nullable dengan default 0)
        $validated['stock_bs_kg'] = $validated['stock_bs_kg'] ?? 0;
        $validated['stock_fs_kg'] = $validated['stock_fs_kg'] ?? 0;
        $validated['planlet'] = $validated['planlet'] ?? 0;
        $validated['minimum_limit'] = $validated['minimum_limit'] ?? 0;
        // Hitung total stock (kg) secara otomatis dari BS + FS (Planlet tidak dihitung)
        $validated['stock'] = ($validated['stock_bs_kg'] + $validated['stock_fs_kg']);

        $variety->update($validated);

        return redirect()->route('admin.varieties.index')
            ->with('success', 'Variety updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Variety $variety)
    {
        // Delete image if exists
        if ($variety->image_path) {
            Storage::disk('public')->delete($variety->image_path);
        }

        $variety->delete();

        return redirect()->route('admin.varieties.index')
            ->with('success', 'Variety deleted successfully.');
    }
}
