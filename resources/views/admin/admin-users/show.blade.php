@extends('layouts.vertical', ['title' => 'Admin User Details', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title">Admin User Details</h4>
                        <p class="text-muted mb-0">View administrator account information and permissions.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.admin-users.edit', $adminUser) }}" class="btn btn-primary btn-sm">
                            <i class="bx bx-edit me-1"></i>Edit
                        </a>
                        <a href="{{ route('admin.admin-users.index') }}" class="btn btn-light btn-sm">
                            <i class="bx bx-arrow-back me-1"></i>Back to List
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label text-muted">Full Name</label>
                            <div class="fw-semibold">{{ $adminUser->name }}</div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label text-muted">Email Address</label>
                            <div class="fw-semibold">{{ $adminUser->email }}</div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label text-muted">Role</label>
                            <div>
                                @if($adminUser->role_id === 1)
                                    <span class="badge bg-danger-subtle text-danger fs-6">Super Admin</span>
                                @elseif($adminUser->role_id === 2)
                                    <span class="badge bg-warning-subtle text-warning fs-6">Admin</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label text-muted">Account Created</label>
                            <div class="fw-semibold">{{ $adminUser->created_at->format('M d, Y \a\t H:i') }}</div>
                            <small class="text-muted">{{ $adminUser->created_at->diffForHumans() }}</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label text-muted">Last Updated</label>
                            <div class="fw-semibold">{{ $adminUser->updated_at->format('M d, Y \a\t H:i') }}</div>
                            <small class="text-muted">{{ $adminUser->updated_at->diffForHumans() }}</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label text-muted">Status</label>
                            <div>
                                @if($adminUser->id === auth()->id())
                                    <span class="badge bg-info-subtle text-info fs-6">Current User</span>
                                @else
                                    <span class="badge bg-success-subtle text-success fs-6">Active</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                @if($adminUser->role && $adminUser->role->description)
                <div class="mt-4">
                    <label class="form-label text-muted">Role Description</label>
                    <div class="fw-semibold">{{ $adminUser->role->description }}</div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Role Permissions Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Role Permissions</h5>
            </div>
            <div class="card-body">
                @if($adminUser->role_id === 1)
                    <div class="alert alert-danger-subtle">
                        <h6 class="text-danger mb-2">
                            <i class="bx bx-shield-check me-2"></i>Super Administrator
                        </h6>
                        <p class="mb-2">This user has full system access with the following permissions:</p>
                        <ul class="mb-0">
                            <li>Full system administration</li>
                            <li>Manage all admin users</li>
                            <li>Create, edit, and delete admin accounts</li>
                            <li>Manage commodities, varieties, seed classes, and seed lots</li>
                            <li>Access all system features and settings</li>
                        </ul>
                    </div>
                @elseif($adminUser->role_id === 2)
                    <div class="alert alert-warning-subtle">
                        <h6 class="text-warning mb-2">
                            <i class="bx bx-shield me-2"></i>Administrator
                        </h6>
                        <p class="mb-2">This user has limited administrative access with the following permissions:</p>
                        <ul class="mb-0">
                            <li>Manage commodities and varieties</li>
                            <li>Manage seed classes and seed lots</li>
                            <li>View and edit product information</li>
                            <li>Cannot manage admin users</li>
                            <li>Cannot access user management features</li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">User Avatar</h5>
            </div>
            <div class="card-body text-center">
                <div class="avatar-xxl mx-auto mb-3">
                    <span class="avatar-title bg-primary text-white rounded-circle fs-1">
                        {{ strtoupper(substr($adminUser->name, 0, 2)) }}
                    </span>
                </div>
                <h5 class="mb-1">{{ $adminUser->name }}</h5>
                <p class="text-muted mb-3">{{ $adminUser->email }}</p>
                
                @if($adminUser->role_id === 1)
                    <span class="badge bg-danger-subtle text-danger">Super Admin</span>
                @elseif($adminUser->role_id === 2)
                    <span class="badge bg-warning-subtle text-warning">Admin</span>
                @endif
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.admin-users.edit', $adminUser) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-2"></i>Edit User
                    </a>
                    
                    @if($adminUser->id !== auth()->id())
                        <form action="{{ route('admin.admin-users.destroy', $adminUser) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100" 
                                    onclick="return confirm('Are you sure you want to delete this admin user? This action cannot be undone.')">
                                <i class="bx bx-trash me-2"></i>Delete User
                            </button>
                        </form>
                    @else
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            <small>You cannot delete your own account.</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Account Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h4 class="mb-1 text-primary">{{ $adminUser->created_at->diffInDays() }}</h4>
                            <p class="text-muted mb-0 small">Days Active</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="mb-1 text-success">{{ $adminUser->updated_at->diffInDays() }}</h4>
                        <p class="text-muted mb-0 small">Days Since Update</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection