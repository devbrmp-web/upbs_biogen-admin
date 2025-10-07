@extends('layouts.vertical', ['title' => 'Variety Details', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h4 class="card-title">{{ $variety->name }}</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.varieties.edit', $variety) }}" class="btn btn-sm btn-primary">
                            <i class="bx bx-pencil"></i> Edit
                        </a>
                        <a href="{{ route('admin.varieties.index') }}" class="btn btn-sm btn-light">
                            <i class="bx bx-arrow-back"></i> Back
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        @if($variety->image_path)
                            <img src="{{ asset('storage/' . $variety->image_path) }}" alt="{{ $variety->name }}" class="img-fluid rounded" style="width:100%;max-width:300px;height:200px;object-fit:cover;" />
                        @else
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height:200px;">
                                <i class="bx bx-image fs-1 text-muted"></i>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-8">
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-semibold" style="width:120px;">Name:</td>
                                <td>{{ $variety->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Slug:</td>
                                <td><code>{{ $variety->slug }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Commodity:</td>
                                <td>
                                    <a href="{{ route('admin.commodities.show', $variety->commodity) }}" class="badge bg-primary text-decoration-none">
                                        {{ $variety->commodity->name }}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Stock Status:</td>
                                <td>
                                    @php
                                        $stockStatus = $variety->stock_status;
                                        $badgeClass = match($stockStatus) {
                                            'tersedia' => 'bg-success',
                                            'restock' => 'bg-warning',
                                            'habis' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($stockStatus) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Description:</td>
                                <td>{{ $variety->description ?: 'No description available' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Seed Lots:</td>
                                <td>
                                    <span class="badge bg-info">{{ $variety->seed_lots_count ?? $variety->seedLots->count() }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Created:</td>
                                <td>{{ $variety->created_at?->format('d M Y, H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Updated:</td>
                                <td>{{ $variety->updated_at?->format('d M Y, H:i') }}</td>
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
                    <a href="{{ route('admin.seed-lots.create', ['variety_id' => $variety->id]) }}" class="btn btn-success">
                        <i class="bx bx-plus"></i> Add New Seed Lot
                    </a>
                    <a href="{{ route('admin.seed-lots.index', ['variety' => $variety->id]) }}" class="btn btn-outline-primary">
                        <i class="bx bx-list-ul"></i> View All Seed Lots
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if($variety->seedLots && $variety->seedLots->count() > 0)
<div class="row mt-4">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Related Seed Lots</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th>Lot Code</th>
                                <th>Seed Class</th>
                                <th>Production Year</th>
                                <th>Quantity</th>
                                <th>Price/Unit</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($variety->seedLots->take(10) as $seedLot)
                            <tr>
                                <td><code>{{ $seedLot->lot_code }}</code></td>
                                <td>
                                    <span class="badge bg-secondary">{{ $seedLot->seedClass->name ?? 'N/A' }}</span>
                                </td>
                                <td>{{ $seedLot->production_year }}</td>
                                <td>{{ number_format($seedLot->quantity, 2) }} {{ $seedLot->unit }}</td>
                                <td>Rp {{ number_format($seedLot->price_per_unit, 0, ',', '.') }}</td>
                                <td>
                                    @if($seedLot->is_sellable)
                                        <span class="badge bg-success">Sellable</span>
                                    @else
                                        <span class="badge bg-warning">Not Sellable</span>
                                    @endif
                                </td>
                                <td>{{ $seedLot->created_at?->format('d M Y') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.seed-lots.show', $seedLot) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <a href="{{ route('admin.seed-lots.edit', $seedLot) }}" class="btn btn-sm btn-light" title="Edit">
                                            <i class="bx bx-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($variety->seedLots->count() > 10)
                    <div class="text-center mt-3">
                        <a href="{{ route('admin.seed-lots.index', ['variety' => $variety->id]) }}" class="btn btn-outline-primary">
                            View All {{ $variety->seedLots->count() }} Seed Lots
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@endsection