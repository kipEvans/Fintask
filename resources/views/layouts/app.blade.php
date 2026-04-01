{{--
  resources/views/layouts/app.blade.php
  Master layout — links CSS and JS, injects JWT token via meta tag.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    {{-- Laravel CSRF (used by Axios / fetch for non-API form posts) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    {{-- JWT token stored in session after login — read by fintask.js --}}
    <meta name="auth-token" content="{{ session('jwt_token', '') }}" />

    {{-- Logged-in user name — read by fintask.js for sidebar --}}
    <meta name="user-name"  content="{{ auth()->user()->name ?? session('user_name', 'User') }}" />

    <title>FinTask – @yield('title', 'Finance & Task Manager')</title>

    {{-- ── GOOGLE FONTS ── --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@400;500&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    {{-- ── FONT AWESOME ── --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    {{-- ── FINTASK CSS ── --}}
    <link rel="stylesheet" href="{{ asset('css/fintask.css') }}" />

    @stack('styles')
</head>
<body>

    @yield('content')

    {{-- ── VUE 3 (must load before fintask.js) ── --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.4.21/vue.global.prod.min.js"></script>

    {{-- ── FINTASK JS ── --}}
    <script src="{{ asset('js/fintask.js') }}"></script>

    @stack('scripts')
</body>
</html>