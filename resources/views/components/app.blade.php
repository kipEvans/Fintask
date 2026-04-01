{{-- ============================================================
     resources/views/layouts/app.blade.php
     Master layout — wraps every authenticated page.
     ============================================================ --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    {{-- CSRF token (used by the JS for non-JWT CSRF protection) --}}
    <meta name="csrf-token"  content="{{ csrf_token() }}" />

    {{-- Auth token (JWT) stored server-side in session after login --}}
    <meta name="auth-token"  content="{{ session('jwt_token', '') }}" />

    {{-- Logged-in user name for the sidebar --}}
    <meta name="user-name"   content="{{ auth()->user()->name ?? 'User' }}" />

    <title>FinTask – @yield('title', 'Finance & Task Manager')</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@400;500&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    {{-- FinTask CSS --}}
    <link rel="stylesheet" href="{{ asset('css/fintask.css') }}" />

    @stack('styles')
</head>
<body>

    @yield('content')

    {{-- Vue 3 (production build) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.4.21/vue.global.prod.min.js"></script>

    {{-- FinTask JS --}}
    <script src="{{ asset('js/fintask.js') }}"></script>

    @stack('scripts')
</body>
</html>