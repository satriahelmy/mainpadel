@extends('layouts.app')

@section('content')
    <div class="py-6 pb-28 sm:py-10 sm:pb-10">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">All rounds</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950">{{ $tournament->name }}</h1>
        <div class="mt-8 space-y-4">
            @foreach ($tournament->rounds as $round)
                <section class="rounded-2xl border border-stone-200 bg-white p-5">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="font-bold text-stone-950">Round {{ $round->round_number }}</h2>
                        <span class="text-xs font-bold uppercase tracking-[0.14em] text-stone-500">{{ $round->status->value }}</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach ($round->matches as $match)
                            @php
                                $teamA = $match->matchPlayers->where('team.value', 'A')->map(fn ($assignment) => $assignment->player->name)->join(' + ');
                                $teamB = $match->matchPlayers->where('team.value', 'B')->map(fn ($assignment) => $assignment->player->name)->join(' + ');
                            @endphp
                            <div class="flex items-center justify-between gap-3 border-t border-stone-100 pt-3 text-sm">
                                <span class="w-16 text-xs font-bold uppercase tracking-[0.12em] text-stone-500">C{{ $match->court_number }}</span>
                                <span class="min-w-0 flex-1 font-semibold text-stone-900">{{ $teamA }} <span class="px-1 text-stone-400">vs</span> {{ $teamB }}</span>
                                @if ($match->status->value === 'completed')<span class="font-bold text-stone-950">{{ $match->team_a_score }}–{{ $match->team_b_score }}</span>@endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
        @include('games.partials.navigation', ['active' => 'rounds'])
    </div>
@endsection
