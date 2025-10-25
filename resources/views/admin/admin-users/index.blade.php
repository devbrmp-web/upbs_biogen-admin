@extends('layouts.vertical', ['title' => 'Admin Users', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <div class="search-bar">
                        <span><i class="bx bx-search-alt"></i></span>
                        <input type="search" class="form-control" id="search" placeholder="Search admin users..." value="{{ request('search', request('q')) }}" />
                    </div>
                    <div>
                        <a href="{{ route('admin.admin-users.create') }}" class="btn btn-primary">
                            + Add Admin User
                        </a>
                    </div>
                </div>
                <!-- end row -->
            </div>
            <div>
                <div class="table-responsive table-centered">
                    <table class="table text-nowrap mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th>Avatar</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <!-- end thead-->
                        <tbody>
                            @forelse($admins as $admin)
                            <tr>
                                <td>
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-primary text-white rounded-circle">
                                            {{ strtoupper(substr($admin->name, 0, 2)) }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <h6 class="mb-1 fw-semibold">{{ $admin->name }}</h6>
                                            @if($admin->id === auth()->id())
                                                <span class="badge bg-info-subtle text-info">You</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $admin->email }}</td>
                                <td>
                                    @if($admin->role_id === 1)
                                        <span class="badge bg-danger-subtle text-danger">Super Admin</span>
                                    @elseif($admin->role_id === 2)
                                        <span class="badge bg-warning-subtle text-warning">Admin</span>
                                    @endif
                                </td>
                                <td>{{ $admin->created_at->format('M d, Y') }}</td>
                                <td>{{ $admin->updated_at->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <a href="#" class="dropdown-toggle btn btn-sm btn-outline-light" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bx bx-dots-horizontal-rounded"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="{{ route('admin.admin-users.show', $admin) }}" class="dropdown-item">
                                                <i class="bx bx-show me-2"></i>View
                                            </a>
                                            <a href="{{ route('admin.admin-users.edit', $admin) }}" class="dropdown-item">
                                                <i class="bx bx-edit me-2"></i>Edit
                                            </a>
                                            @if($admin->id !== auth()->id())
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('admin.admin-users.destroy', $admin) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this admin user?')">
                                                        <i class="bx bx-trash me-2"></i>Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bx bx-user-x display-4 text-muted mb-2"></i>
                                        <h5 class="text-muted">No admin users found</h5>
                                        <p class="text-muted mb-3">Get started by adding your first admin user.</p>
                                        <a href="{{ route('admin.admin-users.create') }}" class="btn btn-primary">
                                            + Add Admin User
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <!-- end tbody -->
                    </table>
                    <!-- end table -->
                </div>
                <!-- table responsive -->
                @if($admins->hasPages())
                <div class="card-footer border-top">
                    <nav aria-label="Page Navigation">
                        {{ $admins->links() }}
                    </nav>
                </div>
                @endif
            </div>
        </div>
        <!-- end card -->
    </div>
    <!-- end col -->
</div>
<!-- end row -->

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    
    // Handle search input to update URL params
    function updateSearch() {
        const url = new URL(window.location);
        const query = searchInput ? searchInput.value.trim() : '';
        if (query !== '') {
            // Prefer 'search' param (controller supports both 'search' and 'q')
            url.searchParams.set('search', query);
        } else {
            url.searchParams.delete('search');
            url.searchParams.delete('q');
        }
        window.location.href = url.toString();
    }

    if (searchInput) {
        // Trigger on Enter key
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                updateSearch();
            }
        });
        // Trigger on blur
        searchInput.addEventListener('blur', function() {
            updateSearch();
        });
    }
});
</script>
@endpush