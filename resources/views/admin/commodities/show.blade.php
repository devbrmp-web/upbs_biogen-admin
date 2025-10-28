@extends('layouts.vertical', ['title' => 'Commodity Details', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h4 class="card-title">{{ $commodity->name }}</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.commodities.edit', $commodity) }}?return={{ urlencode(request()->input('return', request()->fullUrl())) }}" class="btn btn-sm btn-primary">
                            <i class="bx bx-pencil"></i> Edit
                        </a>
                        <a href="{{ sanitizeReturnUrl(request()->input('return'), route('admin.commodities.index')) }}" class="btn btn-sm btn-light">
                            <i class="bx bx-arrow-back"></i> Back
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        @if($commodity->image_path)
                            <img src="{{ asset('storage/' . $commodity->image_path) }}" alt="{{ $commodity->name }}" class="img-fluid rounded" style="width:100%;max-width:300px;height:200px;object-fit:cover;" />
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
                                <td>{{ $commodity->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Slug:</td>
                                <td><code>{{ $commodity->slug }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Varieties:</td>
                                <td>
                                    <span class="badge bg-info">{{ $commodity->varieties_count ?? $commodity->varieties->count() }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Created:</td>
                                <td>{{ $commodity->created_at?->format('d M Y, H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Updated:</td>
                                <td>{{ $commodity->updated_at?->format('d M Y, H:i') }}</td>
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
                    <a href="{{ route('admin.varieties.create', ['commodity_id' => $commodity->id]) }}?return={{ urlencode(request()->input('return', request()->fullUrl())) }}" class="btn btn-success">
                        <i class="bx bx-plus"></i> Add New Variety
                    </a>
                    <a href="{{ route('admin.varieties.index', ['commodity' => $commodity->id]) }}?return={{ urlencode(request()->input('return', request()->fullUrl())) }}" class="btn btn-outline-primary">
                        <i class="bx bx-list-ul"></i> View All Varieties
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if($commodity->varieties && $commodity->varieties->count() > 0)
<div class="row mt-4">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Related Varieties</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Stock Status</th>
                                <th>Seed Lots</th>
                                <th>Created</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($commodity->varieties->take(10) as $variety)
                            <tr>
                                <td>
                                    @if($variety->image_path)
                                            <img src="{{ asset('storage/' . $variety->image_path) }}" alt="{{ $variety->name }}" class="img-fluid rounded" style="width:40px;height:40px;object-fit:cover;" />
                                    @else
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-light text-secondary rounded">
                                                <i class="bx bx-image"></i>
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $variety->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $variety->stock_status_class }}">{{ $variety->stock_status_label }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $variety->seed_lots_count ?? $variety->seedLots->count() }}</span>
                                </td>
                                <td>{{ $variety->created_at?->format('d M Y') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.varieties.show', $variety) }}?return={{ urlencode(request()->input('return', request()->fullUrl())) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <a href="{{ route('admin.varieties.edit', $variety) }}?return={{ urlencode(request()->input('return', request()->fullUrl())) }}" class="btn btn-sm btn-light" title="Edit">
                                            <i class="bx bx-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($commodity->varieties->count() > 10)
                    <div class="text-center mt-3">
                        <a href="{{ route('admin.varieties.index', ['commodity' => $commodity->id]) }}?return={{ urlencode(request()->input('return', request()->fullUrl())) }}" class="btn btn-outline-primary">
                            View All {{ $commodity->varieties->count() }} Varieties
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@endsection
