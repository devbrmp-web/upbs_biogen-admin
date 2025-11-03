@extends('layouts.auth', ['title' => 'Sign In'])

@section('content')

<div class="col-xl-5">
    <div class="card auth-card">
        <div class="card-body px-3 py-5">
            {{-- Logo --}}
            <div class="mx-auto mb-4 text-center auth-logo">
                <a href="{{ route('login') }}" class="d-flex align-items-center justify-content-center text-decoration-none">
                    <img src="/images/Logo_Kementerian_Pertanian_Republik_Indonesia.svg.png" height="40" class="me-2 auth-logo-img" alt="Kementan Logo" />
                    <span class="fw-bold fs-16 auth-logo-text">UPBS BRMP Biogen</span>
                </a>
            </div>

            <h2 class="fw-bold text-center fs-18">Sign In</h2>
            <p class="text-muted text-center mt-1 mb-4">
                Enter your email address and password to access admin panel.
            </p>

            <div class="px-4">
                <form method="POST" action="{{ route('login.store') }}" class="authentication-form">
                    @csrf

                    @if ($errors->any())
                        @foreach ($errors->all() as $error)
                            <p class="text-danger mb-3">{{ $error }}</p>
                        @endforeach
                    @endif

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="Enter your email"
                            autocomplete="email"
                            required
                        />
                    </div>

                    <div class="mb-3">
                        {{-- Reset password link is intentionally disabled per project rules --}}
                        {{-- <a href="{{ route('password.request') }}" class="float-end text-muted text-unline-dashed ms-1">Reset password</a> --}}
                        <label class="form-label" for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        />
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" />
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                    </div>

                    <div class="mb-1 text-center d-grid">
                        <button class="btn btn-primary" type="submit">Sign In</button>
                    </div>
                </form>

                {{-- SSO buttons intentionally disabled per project rules --}}
                {{-- 
                <p class="mt-3 fw-semibold no-span">OR sign with</p>
                <div class="text-center">
                    <a href="javascript:void(0);" class="btn btn-light shadow-none"><i class="bx bxl-google fs-20"></i></a>
                    <a href="javascript:void(0);" class="btn btn-light shadow-none"><i class="bx bxl-facebook fs-20"></i></a>
                    <a href="javascript:void(0);" class="btn btn-light shadow-none"><i class="bx bxl-github fs-20"></i></a>
                </div>
                --}}
            </div>
        </div>
        <!-- end card-body -->
    </div>
    <!-- end card -->

    {{-- Registration link intentionally disabled per project rules --}}
    {{-- 
    <p class="text-white mb-0 text-center">
        New here?
        <a href="{{ route('register') }}" class="text-white fw-bold ms-1">Sign Up</a>
    </p>
    --}}
</div>

@endsection
