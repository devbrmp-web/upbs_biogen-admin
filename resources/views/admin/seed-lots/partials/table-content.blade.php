<div id="list-body">
    <div class="table-responsive table-centered" id="tableContainer">
        <table class="table table-hover text-nowrap mb-0">
            <thead class="bg-light bg-opacity-50">
                <tr>
                    <th>Lot Code</th>
                    <th>Variety</th>
                    <th>Seed Class</th>
                    <th>Production Year</th>
                    <th>Quantity</th>
                    <th>Price/Unit</th>
                    <th>Total Value</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Build sanitized return URL for action links
                    $rawReturn = request()->input('return', request()->fullUrl());
                    $rParts = parse_url($rawReturn);
                    $rPath = $rParts['path'] ?? '';
                    $rQuery = [];
                    if (!empty($rParts['query'])) { parse_str($rParts['query'], $rQuery); }
                    unset($rQuery['ajax'], $rQuery['X-Requested-With']);
                    $rAllowed = ['q','search','variety_id','seed_class_id','is_sellable','page'];
                    $rQuery = array_intersect_key($rQuery, array_flip($rAllowed));
                    $rawSanitizedReturn = url($rPath) . (count($rQuery) ? ('?' . http_build_query($rQuery)) : '');
                    $sanitizedReturn = sanitizeReturnUrl($rawSanitizedReturn, route('admin.seed-lots.index'));
                @endphp
                @forelse ($seedLots as $seedLot)
                    <tr>
                        <td><code>{{ $seedLot->lot_code }}</code></td>
                        <td>{{ $seedLot->variety->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $seedLot->seedClass?->code === 'BS' ? 'primary' : ($seedLot->seedClass?->code === 'FS' ? 'success' : 'secondary') }}">
                                {{ $seedLot->seedClass->name ?? 'N/A' }}
                            </span>
                        </td>
                        <td>{{ $seedLot->production_year }}</td>
                        <td>{{ number_format($seedLot->quantity) }} {{ $seedLot->unit }}</td>
                        <td>Rp {{ number_format($seedLot->price_per_unit, 0, ',', '.') }}</td>
                        <td><strong>Rp {{ number_format($seedLot->quantity * $seedLot->price_per_unit, 0, ',', '.') }}</strong></td>
                        <td>
                            <span class="badge bg-{{ $seedLot->is_sellable ? 'success' : 'warning' }}">
                                {{ $seedLot->is_sellable ? 'Sellable' : 'Not Sellable' }}
                            </span>
                        </td>
                        <td>{{ $seedLot->updated_at?->format('d M Y, H:i') }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('admin.seed-lots.show', ['seed_lot' => $seedLot, 'return' => $sanitizedReturn]) }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bx bx-show"></i>
                                </a>
                                <a href="{{ route('admin.seed-lots.edit', ['seed_lot' => $seedLot, 'return' => $sanitizedReturn]) }}" class="btn btn-sm btn-light" title="Edit">
                                    <i class="bx bx-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" title="Delete" onclick="confirmDelete('{{ $seedLot->lot_code }}')">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bx bx-info-circle fs-1"></i>
                                @if(request()->hasAny(['q', 'variety_id', 'seed_class_id', 'is_sellable']) && \App\Models\SeedLot::count() > 0)
                                    <p class="mt-2">No seed lots match the current filters.</p>
                                    <a href="{{ route('admin.seed-lots.index') }}" class="btn btn-outline-primary">
                                        <i class="bx bx-x"></i> Clear Filters
                                    </a>
                                @else
                                    <p class="mt-2">No seed lots found.</p>
                                    <a href="{{ route('admin.seed-lots.create', array_merge(request()->query(), ['return' => $sanitizedReturn])) }}" class="btn btn-primary">
                                        <i class="bx bx-plus"></i> Add First Seed Lot
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($seedLots instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="d-flex justify-content-center mt-3 seed-lots-pagination" id="paginationContainer">
            {{ $seedLots->withQueryString()->links('custom.pagination') }}
        </div>
    @endif
</div>
