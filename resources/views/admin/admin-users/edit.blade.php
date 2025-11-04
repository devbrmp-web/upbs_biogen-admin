@extends('layouts.vertical', ['title' => 'Edit Admin User', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Edit Admin User</h4>
                <p class="text-muted mb-0">Update administrator account information and permissions.</p>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.admin-users.update', $adminUser) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $adminUser->name) }}" 
                                       placeholder="Enter full name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $adminUser->email) }}" 
                                       placeholder="Enter email address" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" placeholder="Leave blank to keep current password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bx bx-show"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Leave blank to keep current password. Must be at least 8 characters if changed.</div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                                           id="password_confirmation" name="password_confirmation" 
                                           placeholder="Confirm new password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                        <i class="bx bx-show"></i>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $adminUser->role_id) == $role->id ? 'selected' : '' }}>
                                    {{ $role->name === 'super_admin' ? 'Super Admin' : ucfirst($role->name) }}
                                    @if($role->description)
                                        - {{ $role->description }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            <strong>Super Admin:</strong> Full system access including user management.<br>
                            <strong>Admin:</strong> Limited administrative access without user management.
                        </div>
                    </div>
                    
                    @if($adminUser->id === auth()->id())
                        <div class="alert alert-warning">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note:</strong> You are editing your own account. Be careful when changing your role or password.
                        </div>
                    @endif
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.admin-users.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Admin User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">User Information</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-lg me-3">
                        <span class="avatar-title bg-primary text-white rounded-circle fs-3">
                            {{ strtoupper(substr($adminUser->name, 0, 2)) }}
                        </span>
                    </div>
                    <div>
                        <h6 class="mb-1">{{ $adminUser->name }}</h6>
                        <p class="text-muted mb-0">{{ $adminUser->email }}</p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Current Role:</small>
                    <div>
                        @if($adminUser->role_id === 1)
                            <span class="badge bg-danger-subtle text-danger">Super Admin</span>
                        @elseif($adminUser->role_id === 2)
                            <span class="badge bg-warning-subtle text-warning">Admin</span>
                        @endif
                    </div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Created:</small>
                    <div>{{ $adminUser->created_at->format('M d, Y \a\t H:i') }}</div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Last Updated:</small>
                    <div>{{ $adminUser->updated_at->format('M d, Y \a\t H:i') }}</div>
                </div>
                
                <div class="alert alert-info">
                    <i class="bx bx-info-circle me-2"></i>
                    <strong>Tip:</strong> Leave password fields blank to keep the current password unchanged.
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const password = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('bx-show');
            icon.classList.add('bx-hide');
        } else {
            password.type = 'password';
            icon.classList.remove('bx-hide');
            icon.classList.add('bx-show');
        }
    });
    
    document.getElementById('togglePasswordConfirm').addEventListener('click', function() {
        const password = document.getElementById('password_confirmation');
        const icon = this.querySelector('i');
        
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('bx-show');
            icon.classList.add('bx-hide');
        } else {
            password.type = 'password';
            icon.classList.remove('bx-hide');
            icon.classList.add('bx-show');
        }
    });
    
    // Password confirmation validation
    document.getElementById('password_confirmation').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
        
        if (password && password !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Clear confirmation when password is cleared
    document.getElementById('password').addEventListener('input', function() {
        const confirmPassword = document.getElementById('password_confirmation');
        if (!this.value) {
            confirmPassword.value = '';
            confirmPassword.setCustomValidity('');
        }
    });
</script>
@endsection