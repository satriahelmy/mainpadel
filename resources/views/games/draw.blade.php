@extends('layouts.app')

@section('content')
    <div x-data="{ showRedraw: false, submitting: false }" @keydown.escape.window="showRedraw = false" class="py-6 pb-28 sm:py-10 sm:pb-10">
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
                <form method="POST" action="{{ route('games.draw.store', $tournament) }}" x-data="{ submitting: false }" @submit="submitting = true" class="mt-6">
                    @csrf
                    <button type="submit" :disabled="submitting" class="min-h-13 w-full rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950 disabled:cursor-wait disabled:opacity-60 sm:w-auto sm:px-8"><span x-show="!submitting">Generate Draw</span><span x-show="submitting" x-cloak>Creating a fair draw…</span></button>
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
                        <x-games.match-card :match="$match" :tournament="$tournament" variant="preview" />
                    @endforeach
                </div>

                @php
                    $playingIds = $round->matches->flatMap(fn ($match) => $match->matchPlayers->pluck('player_id'))->all();
                    $resting = $tournament->tournamentPlayers->filter(fn ($membership): bool => $membership->status->value === 'active'
                        && ($membership->joined_at_round === null || $membership->joined_at_round <= $round->round_number)
                        && ($membership->left_at_round === null || $membership->left_at_round > $round->round_number)
                        && ! in_array($membership->player_id, $playingIds, true));
                @endphp
                @if ($resting->isNotEmpty())
                    <section class="mt-8">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Resting</p>
                        <p class="mt-2 text-base font-semibold text-stone-900">{{ $resting->map(fn ($membership) => $membership->player->name)->join(' · ') }}</p>
                    </section>
                @endif

                <form method="POST" action="{{ route('games.start', $tournament) }}" x-data="{ submitting: false }" @submit="submitting = true" class="mt-10">
                    @csrf
                    <button type="submit" :disabled="submitting" class="min-h-13 w-full rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950 transition hover:bg-[#b8df00] disabled:cursor-wait disabled:opacity-60"><span x-show="!submitting">Start Game</span><span x-show="submitting" x-cloak>Starting…</span></button>
                </form>
                @if ($redrawSummary['has_future'] && $tournament->status->value !== 'completed')
                    <button type="button" @click="showRedraw = true" class="mt-4 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-5 text-sm font-bold text-stone-800 hover:border-stone-950 hover:text-stone-950">Regenerate this draw</button>
                @endif
                <div class="mt-4 flex flex-col items-center gap-2 text-sm font-bold text-stone-600 sm:flex-row sm:justify-center sm:gap-5">
                    <a href="{{ route('games.rounds', $tournament) }}" class="min-h-11 py-3 hover:text-stone-950">View all rounds</a>
                    <a href="{{ route('games.show', $tournament) }}" class="min-h-11 py-3 hover:text-stone-950">View current game</a>
                    <a href="{{ route('games.standings', $tournament) }}" class="min-h-11 py-3 hover:text-stone-950">View live standings</a>
                </div>
            </section>
        @endif

        <x-games.bottom-navigation :tournament="$tournament" active="play" />

        @if ($round && $redrawSummary['has_future'] && $tournament->status->value !== 'completed')
            <x-ui.dialog state="showRedraw" kicker="Confirm change" title="Regenerate this draw?" labelledby="draw-redraw-title" close-action="showRedraw = false" close-label="Close redraw dialog">
                <p class="mt-3 text-sm leading-6 text-stone-600">Rounds {{ $redrawSummary['from'] }}–{{ $redrawSummary['to'] }} will be regenerated. No match has been played yet, so the current drawing can still change.</p>
                <form method="POST" action="{{ route('games.redraw', $tournament) }}" @submit="submitting = true" class="mt-5">
                    @csrf
                    <input type="hidden" name="confirmed" value="1">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" @click="showRedraw = false" class="min-h-12 rounded-xl border border-stone-300 bg-white px-5 text-sm font-bold text-stone-800">Cancel</button>
                        <button type="submit" :disabled="submitting" class="min-h-12 rounded-xl bg-[#c7f000] px-5 text-sm font-bold text-stone-950 disabled:cursor-wait disabled:opacity-60"><span x-show="!submitting">Regenerate draw</span><span x-show="submitting" x-cloak>Regenerating…</span></button>
                    </div>
                </form>
            </x-ui.dialog>
        @endif
    </div>
@endsection
