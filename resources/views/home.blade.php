@extends('layouts.app')

@section('title', 'MainPadel — Fair Padel Game Draws & Live Standings')
@section('meta_description', 'Run fair padel games without a spreadsheet. MainPadel creates balanced draws, tracks scores, and keeps individual standings live.')
@section('robots', auth()->check() ? 'noindex, nofollow' : 'index, follow')

@push('head')
    @unless (auth()->check())
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'WebApplication',
                'name' => 'MainPadel',
                'url' => route('home'),
                'description' => 'A simple padel game organizer for fair draws, fast score entry, and live individual standings.',
                'applicationCategory' => 'SportsApplication',
                'operatingSystem' => 'Web browser',
                'inLanguage' => 'en',
                'featureList' => [
                    'Fair padel game draws',
                    'Fast score entry',
                    'Live individual standings',
                    'Player rotation management',
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endunless
@endpush

@section('content')
    @guest
        <div class="py-6 sm:py-10">
            <section class="grid gap-10 border-b border-stone-200 py-10 sm:grid-cols-[1.15fr_0.85fr] sm:items-center sm:py-16">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Padel rotation, made easy</p>
                    <h1 class="mt-3 max-w-xl text-4xl font-bold tracking-tight text-stone-950 sm:text-5xl">Fair padel game draws, without the spreadsheet.</h1>
                    <p class="mt-5 max-w-lg text-base leading-7 text-stone-600">MainPadel helps you create a session, balance the rotation, record scores, and keep individual standings live—without the spreadsheet.</p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex min-h-13 items-center justify-center rounded-xl bg-[#c7f000] px-6 text-base font-bold text-stone-950 transition hover:bg-[#b8df00]">Create free account</a>
                        <a href="{{ route('login') }}" class="inline-flex min-h-13 items-center justify-center rounded-xl border border-stone-300 bg-white px-6 text-base font-bold text-stone-800 transition hover:border-stone-950 hover:text-stone-950">Sign in</a>
                    </div>
                </div>

                <div class="border border-stone-300 bg-white p-5 sm:p-6" aria-label="MainPadel game preview">
                    <div class="flex items-center justify-between border-b border-stone-200 pb-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-500">Friday Padel</p>
                            <p class="mt-1 text-lg font-bold text-stone-950">Round 3 · Live</p>
                        </div>
                        <span class="inline-flex min-h-8 items-center bg-[#c7f000] px-3 text-xs font-bold text-stone-950">2 courts</span>
                    </div>
                    <div class="space-y-3 py-5">
                        <div class="border-l-4 border-[#c7f000] bg-stone-50 px-4 py-3">
                            <div class="flex items-center justify-between text-xs font-bold uppercase tracking-[0.12em] text-stone-500"><span>Court 1</span><span>Ready</span></div>
                            <p class="mt-2 text-sm font-semibold text-stone-950">Helmy · Andi</p>
                            <p class="mt-1 text-sm text-stone-600">vs. Budi · Rizky</p>
                        </div>
                        <div class="border-l-4 border-stone-300 bg-stone-50 px-4 py-3">
                            <div class="flex items-center justify-between text-xs font-bold uppercase tracking-[0.12em] text-stone-500"><span>Court 2</span><span>Ready</span></div>
                            <p class="mt-2 text-sm font-semibold text-stone-950">Fajar · Dimas</p>
                            <p class="mt-1 text-sm text-stone-600">vs. Galih · Nisa</p>
                        </div>
                    </div>
                    <div class="border-t border-stone-200 pt-4 text-sm text-stone-600"><span class="font-semibold text-stone-950">2 resting</span> · Next action: enter result</div>
                </div>
            </section>

            <section class="border-b border-stone-200 py-10 sm:py-14">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Built for the court side</p>
                <h2 class="mt-2 max-w-2xl text-2xl font-bold tracking-tight text-stone-950 sm:text-3xl">Everything you need to keep a rotating game moving.</h2>
                <div class="mt-8 grid gap-8 sm:grid-cols-3 sm:gap-6">
                    <div>
                        <span class="text-sm font-bold text-stone-400">01</span>
                        <h3 class="mt-3 text-lg font-bold text-stone-950">Create a session</h3>
                        <p class="mt-2 text-sm leading-6 text-stone-600">Add players, courts, points, and the number of rounds in one quick setup.</p>
                    </div>
                    <div>
                        <span class="text-sm font-bold text-stone-400">02</span>
                        <h3 class="mt-3 text-lg font-bold text-stone-950">Draw fair teams</h3>
                        <p class="mt-2 text-sm leading-6 text-stone-600">The draw considers rests, partners, opponents, and the history of the game.</p>
                    </div>
                    <div>
                        <span class="text-sm font-bold text-stone-400">03</span>
                        <h3 class="mt-3 text-lg font-bold text-stone-950">Play and score</h3>
                        <p class="mt-2 text-sm leading-6 text-stone-600">Enter results quickly and see individual standings update as matches finish.</p>
                    </div>
                </div>
            </section>

            <section class="py-10 sm:flex sm:items-center sm:justify-between sm:gap-8 sm:py-14">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-stone-950">Less organizing. More playing.</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-stone-600">Made for friends, casual clubs, and anyone who wants a clean rotation without doing the math between points.</p>
                </div>
                <a href="{{ route('register') }}" class="mt-6 inline-flex min-h-12 items-center justify-center rounded-xl bg-stone-950 px-6 text-sm font-bold text-white transition hover:bg-stone-800 sm:mt-0">Start with MainPadel</a>
            </section>
        </div>
    @else
        <section class="py-8 sm:py-14">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Your padel rotation</p>
            <h1 class="mt-3 max-w-xl text-4xl font-bold tracking-tight text-stone-950 sm:text-5xl">Your games, without the spreadsheet.</h1>
            <p class="mt-4 max-w-lg text-base leading-7 text-stone-600">Open, draw, play, score, and keep everyone moving.</p>
            <a href="{{ route('games.create') }}" class="mt-8 inline-flex min-h-12 items-center justify-center rounded-xl bg-[#c7f000] px-6 text-base font-bold text-stone-950 transition hover:bg-[#b8df00]">+ New Game</a>
        </section>

        <section class="border-t border-stone-200 pt-7">
            <div class="flex items-baseline justify-between gap-4">
                <h2 class="text-sm font-bold uppercase tracking-[0.16em] text-stone-500">Recent Games</h2>
                @if ($games->isNotEmpty())
                    <span class="text-sm text-stone-500">{{ $games->count() }} saved</span>
                @endif
            </div>

            @if ($games->isEmpty())
                <div class="py-12">
                    <p class="text-xl font-semibold text-stone-900">No games yet.</p>
                    <p class="mt-2 max-w-sm text-sm leading-6 text-stone-600">Create your first game and MainPadel will handle the draw.</p>
                </div>
            @else
                <div class="mt-6 divide-y divide-stone-200 border-y border-stone-200">
                    @foreach ($games as $game)
                        <a href="{{ route('games.show', $game) }}" class="flex min-h-20 items-center justify-between gap-4 py-4 hover:bg-white/60">
                            <span>
                                <span class="block font-semibold text-stone-950">{{ $game->name }}</span>
                                <span class="mt-1 block text-sm text-stone-500">{{ $game->played_at->format('d M') }} · {{ $game->tournamentPlayers()->count() }} players</span>
                            </span>
                            <span class="text-sm font-semibold capitalize text-stone-600">{{ $game->status->value }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endguest
@endsection
