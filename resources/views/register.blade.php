@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="auth-wrap">
    <div class="auth-box">

        <div class="auth-brand">
            <div class="brand-mark">F</div>
            <span class="brand-name">FinTask</span>
        </div>

        <h2>Create account</h2>
        <p class="sub">Start tracking your finances & tasks today</p>

        @if ($errors->any())
            <div class="err-msg">
                <i class="fas fa-exclamation-circle"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}">
            @csrf

            <div class="fg">
                <label>Full Name</label>
                <input type="text" name="name" class="fc"
                       placeholder="Jane Wanjiku"
                       value="{{ old('name') }}" required autofocus />
            </div>

            <div class="fg">
                <label>Email Address</label>
                <input type="email" name="email" class="fc"
                       placeholder="you@example.com"
                       value="{{ old('email') }}" required />
            </div>

            <div class="fg">
                <label>Monthly Budget (KES)</label>
                <input type="number" name="monthly_budget" class="fc"
                       placeholder="e.g. 50000"
                       value="{{ old('monthly_budget', 50000) }}" min="0" />
            </div>

            <div class="two-fg">
                <div class="fg">
                    <label>Password</label>
                    <input type="password" name="password" class="fc"
                           placeholder="Min. 8 characters" required />
                </div>
                <div class="fg">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" class="fc"
                           placeholder="Repeat password" required />
                </div>
            </div>

            <button type="submit" class="btn btn-primary"
                    style="width:100%;justify-content:center;margin-top:4px;">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="auth-link">
            Already have an account? <a href="{{ route('login') }}">Sign in</a>
        </div>

    </div>
</div>
@endsection