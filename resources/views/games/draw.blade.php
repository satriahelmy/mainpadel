@extends('layouts.app')

@section('content')
    <div class="py-6 sm:py-10">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Game setup</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950">{{ $tournament->name }}</h1>
            </div>
            <p class="text-sm text-stone-500">{{ $tournament->tournamentPlayers->count() }} players · {{ $tournament->number_of_courts }} {{ $tournament->number_of_courts === 1 ? 'court' : 'courts' }} · {{ $tournament->target_points }} points</p>
        </div>

        @if (! $round)
            <section class="mt-10 border-t border-stone-200 pt-8">
                <h2 class="text-2xl font-bold text-stone-950">Ready to draw</h2>
                <p class="mt-2 max-w-lg text-base leading-7 text-stone-600">MainPadel will build a fair first round from your active players.</p>
                <form method="POST" action="{{ route('games.draw.store', $tournament) }}" class="mt-6">
                    @csrf
                    <button type="submit" class="min-h-13 w-full rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950 sm:w-auto sm:px-8">Generate Draw</button>
                </form>
            </section>
        @else
            <section class="mt-8 border-t border-stone-200 pt-8">
                <div class="flex items-center gap-2 text-sm font-bold uppercase tracking-[0.16em] text-stone-500">
                    <span>Drawing ready</span><span class="text-lime-600">✓</span>
                </div>
                <div class="mt-7 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Round {{ $round->round_number }}</p>
                        <h2 class="mt-2 text-2xl font-bold text-stone-950">Who plays now?</h2>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach ($round->matches as $match)
                        <article class="rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Court {{ $match->court_number }}</p>
                            @php
                                $teamA = $match->matchPlayers->where('team.value', 'A');
                                $teamB = $match->matchPlayers->where('team.value', 'B');
                            @endphp
                            <div class="mt-6 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                                <div class="space-y-1 text-lg font-bold text-stone-950">
                                    @foreach ($teamA as $assignment)<p>{{ $assignment->player->name }}</p>@endforeach
                                </div>
                                <span class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">VS</span>
                                <div class="space-y-1 text-right text-lg font-bold text-stone-950">
                                    @foreach ($teamB as $assignment)<p>{{ $assignment->player->name }}</p>@endforeach
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @php
                    $playingIds = $round->matches->flatMap(fn ($match) => $match->matchPlayers->pluck('player_id'))->all();
                    $resting = $tournament->tournamentPlayers->whereNotIn('player_id', $playingIds);
                @endphp
                @if ($resting->isNotEmpty())
                    <section class="mt-8">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Resting</p>
                        <p class="mt-2 text-base font-semibold text-stone-900">{{ $resting->map(fn ($membership) => $membership->player->name)->join(' · ') }}</p>
                    </section>
                @endif

                <form method="POST" action="{{ route('games.start', $tournament) }}" class="mt-10">
                    @csrf
                    <button type="submit" class="min-h-13 w-full rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950 transition hover:bg-[#b8df00]">Start Game</button>
                </form>
                <div class="mt-4 flex flex-col items-center gap-2 text-sm font-bold text-stone-600 sm:flex-row sm:justify-center sm:gap-5">
                    <a href="{{ route('games.rounds', $tournament) }}" class="min-h-11 py-3 hover:text-stone-950">View all rounds</a>
                    <a href="{{ route('games.show', $tournament) }}" class="min-h-11 py-3 hover:text-stone-950">View current game</a>
                </div>
            </section>
        @endif
    </div>
@endsection
