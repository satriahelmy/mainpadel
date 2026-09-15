<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'MainPadel' }} · MainPadel</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen font-sans antialiased">
        <div class="mx-auto min-h-screen w-full max-w-3xl px-4 pb-10 sm:px-6">
            <header class="flex items-center justify-between py-5 sm:py-7">
                <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight">MAINPADEL</a>
                @if (isset($tournament) && $tournament->exists)
                    <a href="{{ route('games.show', $tournament) }}" class="text-sm font-semibold text-stone-600 hover:text-stone-950">Back to game</a>
                @endif
            </header>

            @if (session('success'))
                <div class="mb-5 rounded-xl border border-lime-200 bg-lime-100 px-4 py-3 text-sm font-medium text-stone-900" role="status">
                    {{ session('success') }}
                </div>
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
