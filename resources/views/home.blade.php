@extends('layouts.app')

@section('content')
    <section class="py-8 sm:py-14">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Padel rotation, made easy</p>
        <h1 class="mt-3 max-w-xl text-4xl font-bold tracking-tight text-stone-950 sm:text-5xl">Your padel games, without the spreadsheet.</h1>
        <p class="mt-4 max-w-lg text-base leading-7 text-stone-600">Open, draw, play, score, and keep everyone moving.</p>
        <a href="{{ route('games.create') }}" class="mt-8 inline-flex min-h-12 items-center justify-center rounded-xl bg-[#c7f000] px-6 text-base font-bold text-stone-950 transition hover:bg-[#b8df00]">
            + New Game
        </a>
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
            <div class="mt-4 divide-y divide-stone-200 border-y border-stone-200">
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
@endsection
