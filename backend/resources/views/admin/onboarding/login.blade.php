@extends('layouts.admin-standalone')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card admin-card">
                <div class="card-body p-4 p-lg-5">
                    <h2 class="h4 fw-semibold mb-3 text-center">Restricted Access</h2>
                    <p class="text-muted text-center mb-4">Sign in to view the onboarding overview.</p>

                    @if($errors->any())
                        <div class="alert alert-danger small">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.onboarding.authenticate') }}" class="vstack gap-3">
                        @csrf
                        <div>
                            <label for="onboardingEmail" class="form-label small text-uppercase text-muted mb-1">Email</label>
                            <input
                                type="email"
                                id="onboardingEmail"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control form-control-lg"
                                placeholder="you@example.com"
                                required
                                autofocus
                            >
                        </div>
                        <div>
                            <label for="onboardingPassword" class="form-label small text-uppercase text-muted mb-1">Password</label>
                            <input
                                type="password"
                                id="onboardingPassword"
                                name="password"
                                class="form-control form-control-lg"
                                placeholder="Enter password"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">
                            Continue
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
