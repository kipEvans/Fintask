@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="auth-wrap">
    <div class="auth-box">

        <div class="auth-brand">
            <div class="brand-mark">F</div>
            <span class="brand-name">FinTask</span>
        </div>

        <h2>Welcome back</h2>
        <p class="sub">Sign in to your account to continue</p>

        @if ($errors->any())
            <div class="err-msg">
                <i class="fas fa-exclamation-circle"></i>
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('error'))
            <div class="err-msg">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="fg">
                <label>Email Address</label>
                <input type="email" name="email" class="fc"
                       placeholder="you@example.com"
                       value="{{ old('email') }}" required autofocus />
            </div>

            <div class="fg">
                <label>Password</label>
                <input type="password" name="password" class="fc"
                       placeholder="Your password" required />
            </div>

            <button type="submit" class="btn btn-primary"
                    style="width:100%;justify-content:center;margin-top:8px;">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <div class="auth-link">
            Don't have an account? <a href="{{ route('register') }}">Create one</a>
        </div>

    </div>
</div>
@endsection