@extends('layouts.auth', ['title' => 'Access Forbidden - 403'])

@section('content')

<div class="col-xl-12">
    <div class="card auth-card">
        <div class="card-body p-0">
            <div class="row align-items-center g-0">
                <div class="col-lg-6 d-none d-lg-inline-block border-end">
                    <div class="auth-page-sidebar">
                        <img src="https://cdn.dribbble.com/userupload/42148447/file/original-3035a79702582a605e1a495f71984a9b.gif" alt="403 animation" class="img-fluid" />
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="p-4">
                        <div class="mx-auto mb-4 text-center">
                            <div class="mx-auto text-center auth-logo">
                                <a href="{{ route('admin.dashboard')}}" class="logo-dark">
                                    <img src="/images/Logo_Kementerian_Pertanian_Republik_Indonesia.svg.png" height="40" class="me-2 auth-logo-img" alt="Kementan Logo" />
                                    <span class="fw-bold fs-16 auth-logo-text">UPBS BRMP Biogen</span>
                                </a>

                                <a href="{{ route('admin.dashboard')}}" class="logo-light">
                                    <img src="/images/Logo_Kementerian_Pertanian_Republik_Indonesia.svg.png" height="40" class="me-2 auth-logo-img" alt="Kementan Logo" />
                                    <span class="fw-bold fs-16 auth-logo-text">UPBS BRMP Biogen</span>
                                </a>
                            </div>

                            <h1 class="mt-5 mb-3 fw-bold fs-60 text-danger">
                                403
                            </h1>
                            <h2 class="fs-22 lh-base">
                                Access Forbidden!
                            </h2>
                            <p class="text-muted mt-1 mb-4">
                                You don't have permission to access this resource.
                                <br />
                                Please contact an administrator if you believe this is an error.
                            </p>

                            <div class="text-center">
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-lg shadow-lg">
                                    <i class="bx bx-home-alt me-1"></i> Back to Homepage
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>
        </div>
    </div>
</div>

@endsection