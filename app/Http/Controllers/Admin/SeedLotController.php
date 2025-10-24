<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeedLotRequest;
use App\Http\Requests\UpdateSeedLotRequest;
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
        
        // Load dropdown data separately to avoid duplicate queries
        // Use separate queries to avoid conflicts with eager loading
        $varieties = \DB::table('varieties')
            ->join('commodities', 'varieties.commodity_id', '=', 'commodities.id')
            ->select('varieties.id', 'varieties.name', 'commodities.name as commodity_name')
            ->orderBy('varieties.name')
            ->get();
            
        $seedClasses = \DB::table('seed_classes')
            ->select('id', 'name', 'code')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.seed-lots.index', compact('seedLots', 'varieties', 'seedClasses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $varieties = Variety::select('id', 'name', 'commodity_id')
            ->with('commodity:id,name')
            ->orderBy('name')
            ->get();
            
        $seedClasses = SeedClass::select('id', 'name', 'code')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Pre-select variety if provided in query parameter
        $selectedVarietyId = $request->integer('variety_id');
        
        return view('admin.seed-lots.create', compact('varieties', 'seedClasses', 'selectedVarietyId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSeedLotRequest $request)
    {
        $validated = $request->validated();

        $seedLot = SeedLot::create($validated);

        // Redirect back to variety show page if variety_id was provided
        if ($request->filled('variety_id')) {
            $variety = Variety::findOrFail($request->variety_id);
            return redirect()->route('admin.varieties.show', $variety)
                ->with('success', 'Seed lot created successfully.');
        }

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
    public function update(UpdateSeedLotRequest $request, SeedLot $seedLot)
    {
        $validated = $request->validated();

        $seedLot->update($validated);

        // Redirect back to variety show page if variety_id was provided
        if ($request->filled('variety_id')) {
            $variety = Variety::findOrFail($request->variety_id);
            return redirect()->route('admin.varieties.show', $variety)
                ->with('success', 'Seed lot updated successfully.');
        }

        return redirect()->route('admin.seed-lots.index')
            ->with('success', 'Seed lot updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SeedLot $seedLot, Request $request)
    {
        $varietyId = $seedLot->variety_id;
        $seedLot->delete();

        // Redirect back to variety show page if variety_id was provided
        if ($request->filled('variety_id')) {
            $variety = Variety::findOrFail($request->variety_id);
            return redirect()->route('admin.varieties.show', $variety)
                ->with('success', 'Seed lot deleted successfully.');
        }

        return redirect()->route('admin.seed-lots.index')
            ->with('success', 'Seed lot deleted successfully.');
    }
}
