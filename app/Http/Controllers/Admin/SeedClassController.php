<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeedClass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SeedClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SeedClass::query()->withCount('seedLots');

        // Support both 'q' and 'search' parameters to align with Commodities/Varieties
        $searchQuery = $request->string('q')->trim()->toString() ?: $request->string('search')->trim()->toString();
        if ($searchQuery) {
            $query->where(function ($builder) use ($searchQuery) {
                $builder->where('name', 'like', "%{$searchQuery}%")
                    ->orWhere('code', 'like', "%{$searchQuery}%")
                    ->orWhere('description', 'like', "%{$searchQuery}%");
            });
        }

        $seedClasses = $query->latest('updated_at')->paginate(10)->appends($request->query());

        // AJAX partial rendering for progressive enhancement (ignore query ?ajax=1 on normal navigation)
        if ($request->ajax()) {
            return view('admin.seed-classes.partials.table-content', compact('seedClasses'));
        }

        return view('admin.seed-classes.index', compact('seedClasses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.seed-classes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:seed_classes,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        SeedClass::create($validated);

        return redirect()->route('admin.seed-classes.index')
            ->with('success', 'Seed class created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SeedClass $seedClass)
    {
        $seedClass->load('seedLots.variety');
        
        return view('admin.seed-classes.show', compact('seedClass'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SeedClass $seedClass)
    {
        return view('admin.seed-classes.edit', compact('seedClass'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SeedClass $seedClass)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('seed_classes', 'code')->ignore($seedClass->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $seedClass->update($validated);

        return redirect()->route('admin.seed-classes.index')
            ->with('success', 'Seed class updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SeedClass $seedClass)
    {
        // Proactively prevent deletion if there are referencing seed lots (works across DBs and complements FK handling)
        if ($seedClass->seedLots()->exists()) {
            return back()->with('error', 'Seed class cannot be deleted because it is still being used by Seed Lots.');
        }

        try {
            $seedClass->delete();
            
            return redirect()->route('admin.seed-classes.index')
                ->with('success', 'Seed class deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle foreign key constraint violation (MySQL error code 1451, SQLSTATE 23000)
            if ($e->getCode() == '23000' || str_contains($e->getMessage(), '1451')) {
                return back()->with('error', 'Seed class cannot be deleted because it is still being used by Seed Lots.');
            }
            
            // Re-throw other database exceptions
            throw $e;
        }
    }
}
