<div id="list-body">
    <div class="table-responsive table-centered">
        <table class="table text-nowrap mb-0">
            <thead class="bg-light bg-opacity-50">
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Varieties Count</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($commodities as $commodity)
                <tr>
                    <td>
                        @if($commodity->image_path)
                                <img src="{{ asset('storage/' . $commodity->image_path) }}" alt="{{ $commodity->name }}" class="img-fluid" style="width:56px;height:56px;object-fit:cover;border-radius:6px;" />
                        @else
                            <div class="avatar-sm">
                                <span class="avatar-title bg-light text-secondary rounded">
                                    <i class="bx bx-image"></i>
                                </span>
                            </div>
                        @endif
                    </td>
                    <td>{{ \Illuminate\Support\Str::limit($commodity->name, 60) }}</td>
                    <td>
                        <code class="text-muted">{{ \Illuminate\Support\Str::limit($commodity->slug, 80) }}</code>
                    </td>
                    <td>
                        <span class="badge bg-info">{{ $commodity->varieties_count ?? 0 }}</span>
                    </td>
                    <td>{{ $commodity->created_at?->format('d M Y, H:i') }}</td>
                    <td>{{ $commodity->updated_at?->format('d M Y, H:i') }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('admin.commodities.show', $commodity) }}?return={{ urlencode(request()->fullUrl()) }}" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>
                            <a href="{{ route('admin.commodities.edit', $commodity) }}?return={{ urlencode(request()->fullUrl()) }}" class="btn btn-sm btn-light" title="Edit"><i class="bx bx-pencil"></i></a>
                            <form id="delete-form-{{ $commodity->id }}" action="{{ route('admin.commodities.destroy', $commodity) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return" value="{{ request()->fullUrl() }}">
                                <button type="button" data-delete-form="delete-form-{{ $commodity->id }}" class="btn btn-sm btn-danger js-delete-btn" title="Delete"><i class="bx bx-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bx bx-package fs-1 d-block mb-2"></i>
                            No commodities found
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($commodities) && method_exists($commodities, 'links'))
    <div class="card-footer">
        {{ $commodities->links('custom.pagination') }}
    </div>
    @endif
</div>
