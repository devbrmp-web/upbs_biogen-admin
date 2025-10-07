<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeedLot;
use App\Models\Variety;
use App\Models\SeedClass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SeedLotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SeedLot::with(['variety.commodity', 'seedClass']);

        // Filter by search query
        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(function ($builder) use ($q) {
                $builder->where('lot_code', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%")
                    ->orWhereHas('variety', function ($variety) use ($q) {
                        $variety->where('name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('seedClass', function ($seedClass) use ($q) {
                        $seedClass->where('name', 'like', "%{$q}%")
                            ->orWhere('code', 'like', "%{$q}%");
                    });
            });
        }

        // Filter by variety
        if ($varietyId = $request->integer('variety_id')) {
            $query->where('variety_id', $varietyId);
        }

        // Filter by seed class
        if ($seedClassId = $request->integer('seed_class_id')) {
            $query->where('seed_class_id', $seedClassId);
        }

        // Filter by sellable status
        if ($request->has('is_sellable')) {
            $query->where('is_sellable', $request->boolean('is_sellable'));
        }

        $seedLots = $query->latest('updated_at')->paginate(10)->appends($request->query());
        $varieties = Variety::with('commodity')->orderBy('name')->get();
        $seedClasses = SeedClass::where('is_active', true)->orderBy('name')->get();

        return view('admin.seed-lots.index', compact('seedLots', 'varieties', 'seedClasses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $varieties = Variety::with('commodity')->orderBy('name')->get();
        $seedClasses = SeedClass::where('is_active', true)->orderBy('name')->get();
        
        return view('admin.seed-lots.create', compact('varieties', 'seedClasses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'variety_id' => 'required|exists:varieties,id',
            'seed_class_id' => 'required|exists:seed_classes,id',
            'lot_code' => 'required|string|max:50|unique:seed_lots,lot_code',
            'production_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|in:kg,gram,ton,piece',
            'price_per_unit' => 'required|numeric|min:0',
            'is_sellable' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        SeedLot::create($validated);

        return redirect()->route('admin.seed-lots.index')
            ->with('success', 'Seed lot created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SeedLot $seedLot)
    {
        $seedLot->load(['variety.commodity', 'seedClass']);
        
        return view('admin.seed-lots.show', compact('seedLot'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SeedLot $seedLot)
    {
        $varieties = Variety::with('commodity')->orderBy('name')->get();
        $seedClasses = SeedClass::where('is_active', true)->orderBy('name')->get();
        
        return view('admin.seed-lots.edit', compact('seedLot', 'varieties', 'seedClasses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SeedLot $seedLot)
    {
        $validated = $request->validate([
            'variety_id' => 'required|exists:varieties,id',
            'seed_class_id' => 'required|exists:seed_classes,id',
            'lot_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('seed_lots', 'lot_code')->ignore($seedLot->id),
            ],
            'production_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|in:kg,gram,ton,piece',
            'price_per_unit' => 'required|numeric|min:0',
            'is_sellable' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $seedLot->update($validated);

        return redirect()->route('admin.seed-lots.index')
            ->with('success', 'Seed lot updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SeedLot $seedLot)
    {
        $seedLot->delete();

        return redirect()->route('admin.seed-lots.index')
            ->with('success', 'Seed lot deleted successfully.');
    }
}
