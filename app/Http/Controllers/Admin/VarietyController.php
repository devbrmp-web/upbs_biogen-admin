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
        $query = Variety::with('commodity');

        // Filters: q (name/sku or commodity name), commodity (commodity_id), stock_status
        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhereHas('commodity', function ($commodity) use ($q) {
                        $commodity->where('name', 'like', "%{$q}%");
                    });
            });
        }

        // Filter by commodity (matching view parameter name)
        if ($commodityId = $request->integer('commodity')) {
            $query->where('commodity_id', $commodityId);
        }

        // Filter by stock status
        if ($stockStatus = $request->string('stock_status')->trim()->toString()) {
            if ($stockStatus === 'tersedia') {
                $query->whereHas('seedLots', function ($seedLots) {
                    $seedLots->where('is_sellable', true)
                        ->where('quantity_kg', '>', 0);
                });
            } elseif ($stockStatus === 'habis') {
                $query->whereDoesntHave('seedLots', function ($seedLots) {
                    $seedLots->where('is_sellable', true)
                        ->where('quantity_kg', '>', 0);
                });
            } elseif ($stockStatus === 'restock') {
                $query->whereHas('seedLots', function ($seedLots) {
                    $seedLots->where('is_sellable', true)
                        ->where('quantity_kg', '<=', 10); // Assuming restock threshold is 10kg
                });
            }
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
            'sku' => 'required|string|max:100|unique:varieties,sku',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock_bs_kg' => 'required|numeric|min:0',
            'stock_fs_kg' => 'required|numeric|min:0',
            'minimum_limit' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('varieties', 'public');
        }

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
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('varieties', 'sku')->ignore($variety->id),
            ],
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock_bs_kg' => 'required|numeric|min:0',
            'stock_fs_kg' => 'required|numeric|min:0',
            'minimum_limit' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($variety->image) {
                Storage::disk('public')->delete($variety->image);
            }
            
            $validated['image'] = $request->file('image')->store('varieties', 'public');
        }

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
        if ($variety->image) {
            Storage::disk('public')->delete($variety->image);
        }

        $variety->delete();

        return redirect()->route('admin.varieties.index')
            ->with('success', 'Variety deleted successfully.');
    }
}
