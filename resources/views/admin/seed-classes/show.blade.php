@extends('layouts.vertical', ['title' => 'Seed Class Details', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h4 class="card-title">{{ $seedClass->name }} <small class="text-muted">({{ $seedClass->code }})</small></h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.seed-classes.edit', $seedClass) }}" class="btn btn-sm btn-primary">
                            <i class="bx bx-pencil"></i> Edit
                        </a>
                        <a href="{{ route('admin.seed-classes.index') }}" class="btn btn-sm btn-light">
                            <i class="bx bx-arrow-back"></i> Back
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-semibold" style="width:120px;">Code:</td>
                                <td><code class="fs-5">{{ $seedClass->code }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Name:</td>
                                <td class="fs-5">{{ $seedClass->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Description:</td>
                                <td>{{ $seedClass->description ?: 'No description available' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Seed Lots:</td>
                                <td>
                                    <span class="badge bg-info fs-6">{{ $seedClass->seed_lots_count ?? $seedClass->seedLots->count() }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Created:</td>
                                <td>{{ $seedClass->created_at?->format('d M Y, H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Updated:</td>
                                <td>{{ $seedClass->updated_at?->format('d M Y, H:i') }}</td>
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
                    <a href="{{ route('admin.seed-lots.create', ['seed_class_id' => $seedClass->id]) }}" class="btn btn-success">
                        <i class="bx bx-plus"></i> Add New Seed Lot
                    </a>
                    <a href="{{ route('admin.seed-lots.index', ['seed_class' => $seedClass->id]) }}" class="btn btn-outline-primary">
                        <i class="bx bx-list-ul"></i> View All Seed Lots
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if($seedClass->seedLots && $seedClass->seedLots->count() > 0)
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
                                <th>Variety</th>
                                <th>Production Year</th>
                                <th>Quantity</th>
                                <th>Price/Unit</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($seedClass->seedLots->take(10) as $seedLot)
                            <tr>
                                <td><code>{{ $seedLot->lot_code }}</code></td>
                                <td>
                                    <a href="{{ route('admin.varieties.show', $seedLot->variety) }}" class="text-decoration-none">
                                        {{ $seedLot->variety->name ?? 'N/A' }}
                                    </a>
                                    @if($seedLot->variety && $seedLot->variety->commodity)
                                        <br><small class="text-muted">{{ $seedLot->variety->commodity->name }}</small>
                                    @endif
                                </td>
                                <td>{{ $seedLot->production_year }}</td>
                                <td>{{ number_format($seedLot->quantity, 0) }} {{ $seedLot->unit }}</td>
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
                @if($seedClass->seedLots->count() > 10)
                    <div class="text-center mt-3">
                        <a href="{{ route('admin.seed-lots.index', ['seed_class' => $seedClass->id]) }}" class="btn btn-outline-primary">
                            View All {{ $seedClass->seedLots->count() }} Seed Lots
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@endsection