@extends('layouts.app')

@section('title', config('app.name', 'FinTask'))

@section('content')
<div class="welcome-page">
    <div class="welcome-card">
        <h1>Welcome to {{ config('app.name', 'FinTask') }}</h1>
        <p>Track tasks, manage transactions, and plan your budget seamlessly.</p>
        <div class="welcome-actions">
            @if (Route::has('login'))
                <a href="{{ route('login') }}" class="btn btn-primary">Log in</a>
            @endif
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="btn btn-secondary">Register</a>
            @endif
        </div>
    </div>
</div>
@endsection
