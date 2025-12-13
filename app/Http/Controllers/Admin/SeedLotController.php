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
use Illuminate\Support\Str;

class SeedLotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SeedLot::with(['variety.commodity', 'seedClass']);

        // Filter by search query
        if ($request->filled('q')) {
            $q = $request->string('q')->trim()->toString();
            $query->where(function ($builder) use ($q) {
                $builder->where('lot_code', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
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
        if ($request->filled('variety_id')) {
            $query->where('variety_id', $request->integer('variety_id'));
        }

        // Filter by seed class
        if ($request->filled('seed_class_id')) {
            $query->where('seed_class_id', $request->integer('seed_class_id'));
        }

        // Filter by sellable status - only apply if value is not empty (All Status = empty string)
        if ($request->filled('is_sellable') && $request->input('is_sellable') !== '') {
            $query->where('is_sellable', (int) $request->input('is_sellable') === 1);
        }

        $seedLots = $query->latest('updated_at')->paginate(10)->withQueryString();

        // AJAX support - return partial view for AJAX requests (ignore query ?ajax=1 on normal navigation)
        if ($request->ajax()) {
            return view('admin.seed-lots.partials.table-content', [
                'seedLots' => $seedLots,
            ]);
        }

        return view('admin.seed-lots.index', [
            'seedLots' => $seedLots,
            'varieties' => Variety::orderBy('name')->get(),
            'seedClasses' => SeedClass::orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return view('admin.seed-lots.create', [
            'varieties' => Variety::orderBy('name')->get(),
            'seedClasses' => SeedClass::orderBy('name')->get(),
            'selectedVarietyId' => $request->integer('variety_id'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSeedLotRequest $request)
    {
        $validated = $request->validated();

        // Ensure harvest_date is saved
        // (Validation is handled in FormRequest, but ensure it's in $validated)
        
        // Normalize unit and quantity for BS/FS when using 'ton' -> convert to kg
        if (!empty($validated['seed_class_id'])) {
            $seedClass = SeedClass::find($validated['seed_class_id']);
            if ($seedClass && in_array($seedClass->code, ['BS', 'FS'])) {
                if (isset($validated['unit']) && $validated['unit'] === 'ton') {
                    $validated['quantity'] = (int) ($validated['quantity'] * 1000);
                    // Normalize price to per kg when incoming unit is ton
                    $validated['price_per_unit'] = (int) ($validated['price_per_unit'] / 1000);
                }
                // Always store as kg for BS/FS
                $validated['unit'] = 'kg';
            }
        }

        $seedLot = SeedLot::create($validated);

        // Prefer sanitized return URL jika disediakan untuk mempertahankan filter/paginasi
        if ($return = $request->input('return')) {
            $sanitized = $this->sanitizeReturnUrl($return, route('admin.seed-lots.index'));
            if ($sanitized) {
                return redirect()->to($sanitized)->with('success', 'Seed lot created successfully.');
            }
        }

        // Fallback: jika variety_id disediakan, kembali ke halaman Variety
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
        return view('admin.seed-lots.show', [
            'seedLot' => $seedLot->load(['variety.commodity', 'seedClass']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SeedLot $seedLot)
    {
        return view('admin.seed-lots.edit', [
            'seedLot' => $seedLot->load(['variety.commodity', 'seedClass']),
            'varieties' => Variety::orderBy('name')->get(),
            'seedClasses' => SeedClass::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSeedLotRequest $request, SeedLot $seedLot)
    {
        $validated = $request->validated();

        // Normalize unit and quantity for BS/FS when using 'ton' -> convert to kg
        if (!empty($validated['seed_class_id'])) {
            $seedClass = SeedClass::find($validated['seed_class_id']);
            if ($seedClass && in_array($seedClass->code, ['BS', 'FS'])) {
                if (isset($validated['unit']) && $validated['unit'] === 'ton') {
                    $validated['quantity'] = (int) ($validated['quantity'] * 1000);
                    // Normalize price to per kg when incoming unit is ton
                    $validated['price_per_unit'] = (int) ($validated['price_per_unit'] / 1000);
                }
                // Always store as kg for BS/FS
                $validated['unit'] = 'kg';
            }
        }

        $seedLot->update($validated);

        // Prefer sanitized return URL jika disediakan untuk mempertahankan filter/paginasi
        if ($return = $request->input('return')) {
            $sanitized = $this->sanitizeReturnUrl($return, route('admin.seed-lots.index'));
            if ($sanitized) {
                return redirect()->to($sanitized)->with('success', 'Seed lot updated successfully.');
            }
        }

        // Fallback: jika variety_id disediakan, kembali ke halaman Variety
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

        // Prefer sanitized return URL jika disediakan untuk mempertahankan filter/paginasi
        if ($return = $request->input('return')) {
            $sanitized = $this->sanitizeReturnUrl($return, route('admin.seed-lots.index'));
            if ($sanitized) {
                return redirect()->to($sanitized)->with('success', 'Seed lot deleted successfully.');
            }
        }

        // Fallback: jika variety_id disediakan, kembali ke halaman Variety
        if ($request->filled('variety_id')) {
            $variety = Variety::findOrFail($request->variety_id);
            return redirect()->route('admin.varieties.show', $variety)
                ->with('success', 'Seed lot deleted successfully.');
        }

        return redirect()->route('admin.seed-lots.index')
            ->with('success', 'Seed lot deleted successfully.');
    }

    /**
     * Sanitasi URL return: pastikan internal (/admin), hapus flag AJAX & header, pertahankan filter aman.
     * Jika tidak valid, kembalikan fallback URL indeks penuh.
     */
    protected function sanitizeReturnUrl(?string $return, string $fallback): ?string
    {
        if (!$return) return $fallback;
        $adminBase = url('/admin');
        if (!\Illuminate\Support\Str::startsWith($return, $adminBase)) {
            return $fallback; // outside domain or not under /admin
        }

        $parts = parse_url($return);
        $path = $parts['path'] ?? '/admin';
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        // Allowed filter keys across list pages
        $allowed = [
            'q', 'search', 'variety_id', 'seed_class_id', 'is_sellable', 'page', 'commodity', 'stock_status'
        ];

        // Remove flags that trigger partials
        unset($query['ajax'], $query['X-Requested-With']);
        // Keep only allowed params
        $query = array_intersect_key($query, array_flip($allowed));

        $sanitized = url($path);
        if (!empty($query)) {
            $sanitized .= '?' . http_build_query($query);
        }
        return $sanitized ?: $fallback;
    }
}
