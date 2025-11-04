<div id="list-body">
    <div class="table-responsive table-centered">
        <table class="table table-hover text-nowrap mb-0">
            <thead class="table-light">
                <tr>
                    <th>Avatar</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th class="text-end">Actions</th>
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
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('admin.admin-users.show', $admin) }}" class="btn btn-sm btn-info" title="View">
                                <i class="bx bx-show"></i>
                            </a>
                            <a href="{{ route('admin.admin-users.edit', $admin) }}" class="btn btn-sm btn-light" title="Edit">
                                <i class="bx bx-pencil"></i>
                            </a>
                            @if($admin->id !== auth()->id())
                                <button type="button" class="btn btn-sm btn-danger" title="Delete" onclick="confirmDelete('{{ $admin->name }}', {{ $admin->id }})">
                                    <i class="bx bx-trash"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bx bx-user-x display-4 d-block mb-2"></i>
                            @if(request()->has('q') && \App\Models\User::whereIn('role_id', [1, 2])->count() > 0)
                                <p class="mt-2">No admin users match the current search.</p>
                                <a href="{{ route('admin.admin-users.index') }}" class="btn btn-outline-primary">
                                    <i class="bx bx-x"></i> Clear Search
                                </a>
                            @else
                                <p class="mt-2">No admin users found.</p>
                                <a href="{{ route('admin.admin-users.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus"></i> Add First Admin User
                                </a>
                            @endif
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
                {{ $admins->withQueryString()->links('custom.pagination') }}
            </nav>
        </div>
    @endif
</div>