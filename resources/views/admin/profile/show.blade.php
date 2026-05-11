@extends('layouts.vertical', ['title' => $title, 'subTitle' => $subTitle])

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-user-circle me-2"></i>User Profile
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="avatar-xl mx-auto mb-4">
                            <span class="avatar-title bg-primary text-white rounded-circle fs-1">
                                {{ $user->initials }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold text-muted" style="width: 30%;">Name:</td>
                                        <td>{{ $user->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Email:</td>
                                        <td>{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Role:</td>
                                        <td>
                                            <span class="badge bg-primary">
                                                {{ $user->role->name ?? 'Admin' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Member Since:</td>
                                        <td>{{ $user->created_at->format('F d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Last Login:</td>
                                        <td>{{ $user->updated_at->format('F d, Y H:i') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-cog me-2"></i>Account Settings
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-grid">
                            <a href="{{ route('admin.admin-users.edit', $user) }}" class="btn btn-primary">
                                <i class="bx bx-edit me-2"></i>Edit Profile
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-secondary" disabled>
                                <i class="bx bx-lock me-2"></i>Change Password
                            </button>
                            <small class="text-muted mt-1">Contact system administrator to change password</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection