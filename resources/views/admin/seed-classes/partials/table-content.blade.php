<div id="list-body">
    <div class="table-responsive table-centered">
        <table class="table table-hover text-nowrap mb-0">
            <thead class="bg-light bg-opacity-50">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Seed Lots</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($seedClasses as $seedClass)
                <tr>
                    <td><span class="badge bg-primary">{{ $seedClass->code }}</span></td>
                    <td class="fw-semibold">{{ $seedClass->name }}</td>
                    <td class="text-muted">{{ Str::limit($seedClass->description, 50) ?: 'No description' }}</td>
                    <td><span class="badge bg-info">{{ $seedClass->seed_lots_count ?? $seedClass->seedLots->count() }}</span></td>
                    <td>{{ $seedClass->created_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $seedClass->updated_at?->format('Y-m-d H:i') }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('admin.seed-classes.show', $seedClass) }}" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>
                            <a href="{{ route('admin.seed-classes.edit', $seedClass) }}" class="btn btn-sm btn-light" title="Edit"><i class="bx bx-pencil"></i></a>
                            <button type="button" class="btn btn-sm btn-danger" title="Delete"
                                    onclick="confirmDelete('{{ $seedClass->code }}', '{{ $seedClass->name }}')">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bx bx-package fs-1 d-block mb-2"></i>
                            No seed classes found
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($seedClasses) && $seedClasses->hasPages())
    <div class="card-footer border-top">
        <nav aria-label="Page Navigation">
            {{ $seedClasses->links('custom.pagination') }}
        </nav>
    </div>
    @endif
</div>