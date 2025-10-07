<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CommodityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $commodities = Commodity::withCount('varieties')->paginate(10);
        
        return view('admin.commodities.index', compact('commodities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.commodities.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:commodities,slug',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('commodities', 'public');
        }

        Commodity::create($validated);

        return redirect()->route('admin.commodities.index')
            ->with('success', 'Commodity created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Commodity $commodity)
    {
        $commodity->load(['varieties' => function ($query) {
            $query->withCount('seedLots');
        }]);
        
        return view('admin.commodities.show', compact('commodity'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Commodity $commodity)
    {
        return view('admin.commodities.edit', compact('commodity'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Commodity $commodity)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('commodities', 'slug')->ignore($commodity->id),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($commodity->image_url) {
                Storage::disk('public')->delete($commodity->image_url);
            }
            
            $validated['image_url'] = $request->file('image')->store('commodities', 'public');
        }

        $commodity->update($validated);

        return redirect()->route('admin.commodities.index')
            ->with('success', 'Commodity updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Commodity $commodity)
    {
        // Delete image if exists
        if ($commodity->image_url) {
            Storage::disk('public')->delete($commodity->image_url);
        }

        $commodity->delete();

        return redirect()->route('admin.commodities.index')
            ->with('success', 'Commodity deleted successfully.');
    }
}
