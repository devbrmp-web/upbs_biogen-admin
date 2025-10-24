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
                                <td class="fw-semibold">SKU:</td>
                                <td><code>{{ $variety->sku ?? 'N/A' }}</code></td>
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
                                <td class="fw-semibold">Price:</td>
                                <td><strong>Rp {{ number_format($variety->price ?? 0, 0, ',', '.') }}</strong></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Stock Status:</td>
                                <td>
                                    @php
                                        $stockStatus = $variety->stock_status; // accessor returns: Tersedia | Restock | Habis
                                        $badgeClass = match($stockStatus) {
                                            'Tersedia' => 'bg-success',
                                            'Restock' => 'bg-warning',
                                            'Habis' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $stockStatus }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">BS Stock:</td>
                                <td>{{ number_format($variety->stock_bs_kg ?? 0, 3) }} kg</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">FS Stock:</td>
                                <td>{{ number_format($variety->stock_fs_kg ?? 0, 3) }} kg</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Planlet (per botol):</td>
                                <td>{{ number_format($variety->planlet ?? 0, 0) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Total Stock:</td>
                                <td><strong>{{ number_format(($variety->stock_bs_kg ?? 0) + ($variety->stock_fs_kg ?? 0), 3) }} kg</strong></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Min. Limit:</td>
                                <td>{{ number_format($variety->minimum_limit ?? 0, 3) }} kg</td>
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
                    <a href="{{ route('admin.seed-lots.index', ['variety_id' => $variety->id]) }}" class="btn btn-outline-primary">
                        <i class="bx bx-list-ul"></i> View All Seed Lots
                    </a>
                </div>
            </div>
        </div>

        <!-- Stock Summary Card -->
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Stock Summary</h6>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h5 class="text-primary mb-1">{{ number_format($variety->stock_bs_kg ?? 0, 3) }}</h5>
                            <small class="text-muted">BS Stock (kg)</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h5 class="text-success mb-1">{{ number_format($variety->stock_fs_kg ?? 0, 3) }}</h5>
                        <small class="text-muted">FS Stock (kg)</small>
                    </div>
                </div>
                <hr class="my-3">
                <div class="text-center">
                    <h4 class="text-dark mb-1">{{ number_format(($variety->stock_bs_kg ?? 0) + ($variety->stock_fs_kg ?? 0), 3) }}</h4>
                    <small class="text-muted">Total Stock (kg)</small>
                </div>
                @php($totalBsFs = ($variety->stock_bs_kg ?? 0) + ($variety->stock_fs_kg ?? 0))
                @if(($variety->minimum_limit ?? 0) > 0 && $totalBsFs <= ($variety->minimum_limit ?? 0))
                    <div class="alert alert-warning mt-3 mb-0 py-2">
                        <small><i class="bx bx-warning"></i> Stock below minimum limit!</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($variety->seedLots && $variety->seedLots->count() > 0)
<div class="row mt-4">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Seed Lots Management</h5>
                    <a href="{{ route('admin.seed-lots.create', ['variety_id' => $variety->id]) }}" class="btn btn-sm btn-success">
                        <i class="bx bx-plus"></i> Add Seed Lot
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th>Lot Code</th>
                                <th>Seed Class</th>
                                <th>Production Year</th>
                                <th>Quantity</th>
                                <th>Price/Unit</th>
                                <th>Total Value</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
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
                                <td><strong>Rp {{ number_format($seedLot->total_value, 0, ',', '.') }}</strong></td>
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
                                        <form action="{{ route('admin.seed-lots.destroy', $seedLot) }}" method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this seed lot?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($variety->seedLots->count() > 10)
                    <div class="text-center mt-3">
                        <a href="{{ route('admin.seed-lots.index', ['variety_id' => $variety->id]) }}" class="btn btn-outline-primary">
                            View All {{ $variety->seedLots->count() }} Seed Lots
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@else
<div class="row mt-4">
    <div class="col">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bx bx-package fs-1 text-muted mb-3"></i>
                <h5 class="text-muted">No Seed Lots Found</h5>
                <p class="text-muted mb-4">This variety doesn't have any seed lots yet. Create the first one to start managing inventory.</p>
                <a href="{{ route('admin.seed-lots.create', ['variety_id' => $variety->id]) }}" class="btn btn-success">
                    <i class="bx bx-plus"></i> Create First Seed Lot
                </a>
            </div>
        </div>
    </div>
</div>
@endif

@endsection