@extends('layouts.vertical', ['title' => 'Seed Lot Details', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h4 class="card-title">{{ $seedLot->lot_code }}</h4>
                    @php
                        // Build sanitized return URL and ensure it stays within admin area
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
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.seed-lots.edit', $seedLot) }}?return={{ urlencode($sanitizedReturn) }}" class="btn btn-sm btn-primary">
                            <i class="bx bx-pencil"></i> Edit
                        </a>
                        <a href="{{ $sanitizedReturn ?: route('admin.seed-lots.index') }}" class="btn btn-sm btn-light">
                            <i class="bx bx-arrow-back"></i> Back
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-semibold" style="width:150px;">Lot Code:</td>
                                <td><code class="fs-5">{{ $seedLot->lot_code }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Variety:</td>
                                <td>
                                    @if($seedLot->variety)
                                        <a href="{{ route('admin.varieties.show', ['variety' => $seedLot->variety, 'return' => $sanitizedReturn]) }}" class="text-decoration-none fw-semibold">
                                            {{ $seedLot->variety->name }}
                                        </a>
                                        @if($seedLot->variety->commodity)
                                            <br><small class="text-muted">{{ $seedLot->variety->commodity->name }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Seed Class:</td>
                                <td>
                                    @if($seedLot->seedClass)
                                        <a href="{{ route('admin.seed-classes.show', ['seed_class' => $seedLot->seedClass, 'return' => $sanitizedReturn]) }}" class="badge bg-secondary text-decoration-none">
                                            {{ $seedLot->seedClass->name }} ({{ $seedLot->seedClass->code }})
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Production Year:</td>
                                <td>{{ $seedLot->production_year }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Quantity:</td>
                                <td>
                                    <span class="fs-5 fw-semibold">{{ number_format($seedLot->quantity, 0) }}</span> 
                                    <span class="text-muted">{{ $seedLot->unit }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Price per Unit:</td>
                                <td>
                                    <span class="fs-5 fw-semibold text-success">Rp {{ number_format($seedLot->price_per_unit, 0, ',', '.') }}</span>
                                    <span class="text-muted">/ {{ $seedLot->unit }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Total Value:</td>
                                <td>
                                    <span class="fs-4 fw-bold text-primary">
                                        Rp {{ number_format($seedLot->total_value, 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Status:</td>
                                <td>
                                    @if($seedLot->is_sellable)
                                        <span class="badge bg-success fs-6">Available for Sale</span>
                                    @else
                                        <span class="badge bg-warning fs-6">Not Available for Sale</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Description:</td>
                                <td style="white-space: pre-line;">{{ $seedLot->description ?: 'No description available' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Created:</td>
                                <td>{{ $seedLot->created_at?->format('d M Y, H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Updated:</td>
                                <td>{{ $seedLot->updated_at?->format('d M Y, H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Quick Actions</h5>
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.seed-lots.edit', $seedLot) }}?return={{ urlencode($sanitizedReturn) }}" class="btn btn-primary">
                        <i class="bx bx-pencil"></i> Edit Seed Lot
                    </a>
                    @if($seedLot->variety)
                        <a href="{{ route('admin.varieties.show', ['variety' => $seedLot->variety, 'return' => $sanitizedReturn]) }}" class="btn btn-outline-info">
                            <i class="bx bx-show"></i> View Variety
                        </a>
                    @endif
                    @if($seedLot->seedClass)
                        <a href="{{ route('admin.seed-classes.show', ['seed_class' => $seedLot->seedClass, 'return' => $sanitizedReturn]) }}" class="btn btn-outline-secondary">
                            <i class="bx bx-show"></i> View Seed Class
                        </a>
                    @endif
                    <a href="{{ route('admin.seed-lots.create', ['variety_id' => $seedLot->variety?->id, 'return' => $sanitizedReturn]) }}" class="btn btn-outline-success">
                        <i class="bx bx-plus"></i> Add New Seed Lot
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Summary</h5>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="fs-4 fw-bold text-primary">{{ number_format($seedLot->quantity, 0) }}</div>
                            <div class="small text-muted">{{ $seedLot->unit }} Available</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <div class="fs-4 fw-bold text-success">{{ number_format($seedLot->price_per_unit, 0) }}</div>
                            <div class="small text-muted">IDR per {{ $seedLot->unit }}</div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <div class="fs-6 text-muted">Total Inventory Value</div>
                    <div class="fs-3 fw-bold text-primary">
                        Rp {{ number_format($seedLot->total_value, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        
        @if($seedLot->variety && $seedLot->variety->image_path)
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Variety Image</h5>
                <img src="{{ asset('storage/' . $seedLot->variety->image_path) }}" alt="{{ $seedLot->variety->name }}" 
                     class="img-fluid rounded" style="width:100%;height:200px;object-fit:cover;" />
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

@section('script')
<script>
    // Initialize Bootstrap tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endsection
