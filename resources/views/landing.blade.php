@extends('layouts.guest')
@section('title', 'Welcome to CK')
@section('content')
<div class="container-fluid min-vh-100 d-flex flex-column justify-content-center align-items-center bg-light">
    <div class="row w-100 justify-content-center">
        <div class="col-lg-6 col-md-8 col-12">
            <div class="text-center mb-5">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="CRM Logo" width="80" class="mb-3">
                <h1 class="display-4 fw-bold text-primary mb-3">Welcome to</h1> <h1 class= "display-4 fw-bold text-danger">CLiX</h1>
                <p class="lead">Manage your business, sales, and accounting in one place.<br>Simple. Powerful. Secure.</p>
            </div>
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h3 class="mb-4 text-center">Sign In</h3>
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="{{ route('password.request') }}">Forgot your password?</a>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4 text-muted small">
                &copy; {{ date('Y') }} CRM-ERP. All rights reserved.
            </div>
        </div>
    </div>
</div>
@endsection 