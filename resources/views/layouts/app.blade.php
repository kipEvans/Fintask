<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-name" content="{{ session('user_name', 'User') }}">
    <meta name="auth-token" content="{{ session('jwt_token', '') }}">
    <title>@yield('title', config('app.name', 'FinTask'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" integrity="sha512-Fo3rlrZj/k7ujTTXRXUafFa6NybVz8sQbK6xN4X4nVNQsuWnGeIXX43upZKBQEmkkmYhZ5NAB7g2T2WT8qN9Yw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @php $viteManifest = public_path('build/manifest.json'); @endphp
    @if (file_exists($viteManifest))
        @vite(['resources/css/fintask.css', 'resources/js/app.js'])
        <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    @else
        <link href="{{ asset('css/fintask.css') }}" rel="stylesheet">
        <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
        <script src="{{ asset('js/fintask.js') }}"></script>
    @endif
</head>
<body>
    @yield('content')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>
</html>
