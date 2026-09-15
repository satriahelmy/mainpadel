<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'MainPadel')</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <meta name="description" content="@yield('meta_description', 'MainPadel is a simple padel game organizer for fair draws, fast score entry, and live individual standings.')">
        <meta name="robots" content="@yield('robots', 'noindex, nofollow')">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta name="theme-color" content="#f7f7f5">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="MainPadel">
        <meta property="og:title" content="@yield('title', 'MainPadel')">
        <meta property="og:description" content="@yield('meta_description', 'MainPadel is a simple padel game organizer for fair draws, fast score entry, and live individual standings.')">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="@yield('title', 'MainPadel')">
        <meta name="twitter:description" content="@yield('meta_description', 'MainPadel is a simple padel game organizer for fair draws, fast score entry, and live individual standings.')">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="min-h-screen font-sans antialiased">
        <div class="mx-auto min-h-screen w-full max-w-3xl px-4 pb-10 sm:px-6">
            <header class="flex items-center justify-between py-5 sm:py-7">
                <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight">MAINPADEL</a>
                <div class="flex items-center gap-4">
                    @if (isset($tournament) && $tournament->exists)
                        <a href="{{ route('games.show', $tournament) }}" class="text-sm font-semibold text-stone-600 hover:text-stone-950">Back to game</a>
                    @endif
                    @auth
                        <span class="hidden text-sm text-stone-500 sm:inline">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="min-h-11 text-sm font-semibold text-stone-600 underline decoration-stone-300 underline-offset-4 hover:text-stone-950">Sign out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-stone-600 hover:text-stone-950">Sign in</a>
                        <a href="{{ route('register') }}" class="text-sm font-semibold text-stone-950 underline decoration-stone-300 underline-offset-4">Register</a>
                    @endauth
                </div>
            </header>

            @if (session('success'))
                <x-ui.flash :message="session('success')" />
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <p class="font-semibold">Please check the highlighted fields.</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <main>
                @yield('content')
            </main>
        </div>
    </body>
</html>
