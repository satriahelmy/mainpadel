@extends('layouts.app')

@section('content')
    <div class="py-6 pb-28 sm:py-10 sm:pb-10">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Players</p>
        <div class="mt-2 flex items-end justify-between gap-4">
            <h1 class="text-3xl font-bold tracking-tight text-stone-950">{{ $tournament->name }}</h1>
            <span class="text-sm font-semibold text-stone-500">{{ $tournament->tournamentPlayers->where('status.value', 'active')->count() }} active</span>
        </div>

        <div class="mt-8 divide-y divide-stone-200 border-y border-stone-200">
            @foreach ($participation as $row)
                @php($membership = $row['membership'])
                <div class="flex items-center gap-4 py-4">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold text-stone-950">{{ $membership->player->name }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ $row['played'] }} matches · {{ $row['rests'] }} rests{{ $membership->status->value === 'withdrawn' ? ' · withdrawn' : '' }}</p>
                    </div>
                    @if ($membership->status->value === 'active' && $tournament->status->value !== 'completed')
                        <form method="POST" action="{{ route('games.players.withdraw', [$tournament, $membership]) }}" onsubmit="return confirm('Stop playing? Completed results stay saved and future rounds will change.')">
                            @csrf
                            <button type="submit" class="min-h-11 px-2 text-sm font-bold text-stone-500 underline decoration-stone-300 underline-offset-4 hover:text-red-700">Stop playing</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($tournament->status->value !== 'completed')
            <section class="mt-8 rounded-2xl border border-stone-200 bg-white p-5">
                <h2 class="text-lg font-bold text-stone-950">Add player</h2>
                <p class="mt-1 text-sm leading-6 text-stone-500">The player joins from the next unplayed round.</p>
                <form method="POST" action="{{ route('games.players.store', $tournament) }}" class="mt-4 flex gap-2">
                    @csrf
                    <label for="new-player-name" class="sr-only">Player name</label>
                    <input id="new-player-name" name="name" value="{{ old('name') }}" required maxlength="100" class="min-h-12 min-w-0 flex-1 rounded-xl border border-stone-300 px-4" placeholder="Player name">
                    <button type="submit" class="min-h-12 rounded-xl bg-[#c7f000] px-4 text-sm font-bold text-stone-950">Add</button>
                </form>
            </section>
        @endif

        @if ($redrawSummary && $redrawSummary['has_future'])
            <section class="mt-6 border-t border-stone-200 pt-6">
                <h2 class="text-lg font-bold text-stone-950">Roster changed</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">Future rounds {{ $redrawSummary['from'] }}–{{ $redrawSummary['to'] }} will be regenerated. Completed rounds will not change.</p>
                <form method="POST" action="{{ route('games.redraw', $tournament) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="min-h-12 w-full rounded-xl bg-[#c7f000] px-5 text-sm font-bold text-stone-950">Redraw future rounds</button>
                </form>
            </section>
        @endif

        @include('games.partials.navigation', ['active' => 'players'])
    </div>
@endsection
