<div id="list-body">
    <div class="table-responsive table-centered">
        <table class="table text-nowrap mb-0">
            <thead class="bg-light bg-opacity-50">
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Commodity</th>
                    <th>Planlet</th>
                    <th>Stock Status</th>
                    <th>Seed Lots</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($varieties as $variety)
                <tr>
                    <td>
                        @if($variety->image_path)
                                <img src="{{ asset('storage/' . $variety->image_path) }}" alt="{{ $variety->name }}" class="img-fluid" style="width:56px;height:56px;object-fit:cover;border-radius:6px;" />
                        @else
                            <div class="avatar-sm">
                                <span class="avatar-title bg-light text-secondary rounded">
                                    <i class="bx bx-image"></i>
                                </span>
                            </div>
                        @endif
                    </td>
                    <td>
                        <div>
                            <h6 class="mb-0">{{ \Illuminate\Support\Str::limit($variety->name, 60) }}</h6>
                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($variety->slug, 80) }}</small>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-primary">{{ $variety->commodity->name ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ $variety->total_planlet ?? 0 }}</span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $variety->stock_status_class }}">{{ $variety->stock_status_label }}</span>
                    </td>
                    <td>
                        <span class="badge bg-info">{{ $variety->seed_lots_count ?? 0 }}</span>
                    </td>
                    <td>{{ $variety->created_at?->format('d M Y, H:i') }}</td>
                    <td>{{ $variety->updated_at?->format('d M Y, H:i') }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('admin.varieties.show', $variety) }}?return={{ urlencode(request()->fullUrl()) }}" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>
                            <a href="{{ route('admin.varieties.edit', $variety) }}?return={{ urlencode(request()->fullUrl()) }}" class="btn btn-sm btn-light" title="Edit"><i class="bx bx-pencil"></i></a>
                            <form id="delete-form-{{ $variety->id }}" action="{{ route('admin.varieties.destroy', $variety) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return" value="{{ request()->fullUrl() }}">
                                <button type="button" data-delete-form="delete-form-{{ $variety->id }}" class="btn btn-sm btn-danger js-delete-btn" title="Delete"><i class="bx bx-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bx bx-package fs-1 d-block mb-2"></i>
                            No varieties found
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($varieties) && method_exists($varieties, 'links'))
    <div class="card-footer">
        {{ $varieties->links('custom.pagination') }}
    </div>
    @endif
</div>
