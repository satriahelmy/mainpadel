@extends('layouts.app')

@section('content')
    <div class="py-6 pb-28 sm:py-10 sm:pb-10">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $tournament->status->value === 'completed' ? 'Game complete' : 'Now playing' }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950">{{ $tournament->name }}</h1>
            </div>
            @if ($round)<p class="text-sm font-bold uppercase tracking-[0.16em] text-stone-500">Round {{ $round->round_number }}</p>@endif
        </div>

        @if (! $round)
            <p class="mt-10 text-stone-600">No round has been generated yet.</p>
        @else
            <div class="mt-8 space-y-4">
                @foreach ($round->matches as $match)
                    <article class="rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Court {{ $match->court_number }}</p>
                        @php
                            $teamA = $match->matchPlayers->where('team.value', 'A');
                            $teamB = $match->matchPlayers->where('team.value', 'B');
                        @endphp
                        <div class="mt-6 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                            <div class="space-y-1 text-lg font-bold text-stone-950">@foreach ($teamA as $assignment)<p>{{ $assignment->player->name }}</p>@endforeach</div>
                            <span class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">VS</span>
                            <div class="space-y-1 text-right text-lg font-bold text-stone-950">@foreach ($teamB as $assignment)<p>{{ $assignment->player->name }}</p>@endforeach</div>
                        </div>
                        @if ($match->status->value === 'completed')
                            <div class="mt-7 flex items-center justify-between border-t border-stone-100 pt-4">
                                <span class="text-xs font-bold uppercase tracking-[0.16em] text-stone-500">Final</span>
                                <span class="text-3xl font-bold tracking-tight text-stone-950">{{ $match->team_a_score }} — {{ $match->team_b_score }}</span>
                            </div>
                        @else
                            <a href="{{ route('games.score.edit', [$tournament, $match]) }}" class="mt-7 flex min-h-12 items-center justify-center rounded-xl bg-[#c7f000] text-sm font-bold text-stone-950">Enter Result</a>
                        @endif
                    </article>
                @endforeach
            </div>

            @php
                $playingIds = $round->matches->flatMap(fn ($match) => $match->matchPlayers->pluck('player_id'))->all();
                $resting = $tournament->tournamentPlayers
                    ->filter(fn ($membership) => $membership->status->value === 'active' && ! in_array($membership->player_id, $playingIds, true));
            @endphp
            @if ($resting->isNotEmpty())
                <section class="border-t border-stone-200 pt-6">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Resting this round</p>
                    <p class="mt-2 text-base font-semibold text-stone-900">{{ $resting->map(fn ($membership) => $membership->player->name)->join(' · ') }}</p>
                </section>
            @endif

            @if ($round->status->value === 'completed' && $tournament->status->value === 'ongoing')
                <form method="POST" action="{{ route('games.next', $tournament) }}" class="mt-8">
                    @csrf
                    <button type="submit" class="min-h-13 w-full rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950">Continue to next round</button>
                </form>
            @elseif ($tournament->status->value === 'completed')
                <a href="{{ route('games.standings', $tournament) }}" class="mt-8 flex min-h-13 items-center justify-center rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950">View final standings</a>
            @endif

            @include('games.partials.navigation', ['active' => 'play'])
        @endif
    </div>
@endsection
