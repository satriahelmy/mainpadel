@extends('layouts.app')

@section('content')
    <div x-data="{ showAddPlayer: false, showRedraw: false, submitting: false }" @keydown.escape.window="showAddPlayer = false; showRedraw = false" class="py-6 pb-28 sm:py-10 sm:pb-10">
        @php
            $unplayedCurrentRound = $tournament->rounds->first(fn ($round): bool => $round->status->value === 'ongoing'
                && ! $round->matches->contains(fn ($match): bool => $match->status->value === 'completed'));
        @endphp
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Players</p>
        <div class="mt-2 flex items-end justify-between gap-4">
            <h1 class="text-3xl font-bold tracking-tight text-stone-950">{{ $tournament->name }}</h1>
            <span class="text-sm font-semibold text-stone-500">{{ $tournament->tournamentPlayers->where('status.value', 'active')->count() }} active</span>
        </div>

        <div class="mt-8 divide-y divide-stone-200 border-y border-stone-200">
            @foreach ($participation as $row)
                @php($membership = $row['membership'])
                @php($openAbsence = $membership->absences->first(fn ($absence): bool => $absence->available_again_round === null))
                <x-games.player-row :membership="$membership" :played="$row['played']" :rests="$row['rests']" :unavailable="$openAbsence !== null">
                    @if ($membership->status->value === 'active' && $tournament->status->value !== 'completed')
                        <div class="flex flex-wrap justify-end gap-2">
                            @if ($unplayedCurrentRound !== null && $membership->joined_at_round > $unplayedCurrentRound->round_number)
                                <form method="POST" action="{{ route('games.players.include.current', [$tournament, $membership]) }}" onsubmit="return confirm('Include this late joiner in the current unplayed round? The current drawing will be replaced after confirmation.')">
                                    @csrf
                                    <button type="submit" class="min-h-11 px-2 text-sm font-bold text-stone-600 underline decoration-stone-300 underline-offset-4 hover:text-stone-950">Include in round</button>
                                </form>
                            @endif
                            @if ($openAbsence !== null && $unplayedCurrentRound !== null && $openAbsence->unavailable_from_round > $unplayedCurrentRound->round_number)
                                <form method="POST" action="{{ route('games.players.unavailable.current', [$tournament, $membership]) }}" onsubmit="return confirm('Apply this pause to the current unplayed round? The current drawing will be replaced after confirmation.')">
                                    @csrf
                                    <button type="submit" class="min-h-11 px-2 text-sm font-bold text-stone-600 underline decoration-stone-300 underline-offset-4 hover:text-stone-950">Apply to round</button>
                                </form>
                            @endif
                            @if ($openAbsence !== null)
                                <form method="POST" action="{{ route('games.players.available', [$tournament, $membership]) }}" onsubmit="return confirm('Make this player available from the next unplayed round? Future rounds will need a redraw.')">
                                    @csrf
                                    <button type="submit" class="min-h-11 px-2 text-sm font-bold text-stone-600 underline decoration-stone-300 underline-offset-4 hover:text-stone-950">Resume</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('games.players.unavailable', [$tournament, $membership]) }}" onsubmit="return confirm('Mark this player unavailable from the next unplayed round? The current locked round will not change.')">
                                    @csrf
                                    <button type="submit" class="min-h-11 px-2 text-sm font-bold text-stone-600 underline decoration-stone-300 underline-offset-4 hover:text-stone-950">Pause</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('games.players.withdraw', [$tournament, $membership]) }}" onsubmit="return confirm('Stop playing permanently? Completed results stay saved and future rounds will change.')">
                                @csrf
                                <button type="submit" class="min-h-11 px-2 text-sm font-bold text-stone-500 underline decoration-stone-300 underline-offset-4 hover:text-red-700">Stop playing</button>
                            </form>
                        </div>
                    @endif
                </x-games.player-row>
            @endforeach
        </div>

        @if ($tournament->status->value !== 'completed')
            <section class="mt-8 border-t border-stone-200 pt-6">
                <h2 class="text-lg font-bold text-stone-950">Add player</h2>
                <p class="mt-1 text-sm leading-6 text-stone-500">Add a player or pause someone who is temporarily unavailable. Changes apply from the next unplayed round.</p>
                <button type="button" @click="showAddPlayer = true" class="mt-4 min-h-12 rounded-xl bg-[#c7f000] px-5 text-sm font-bold text-stone-950">+ Add player</button>
            </section>
        @endif

        @if ($redrawSummary && $redrawSummary['has_future'])
            <section class="mt-6 border-t border-stone-200 pt-6">
                <h2 class="text-lg font-bold text-stone-950">Roster changed</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">Future rounds {{ $redrawSummary['from'] }}–{{ $redrawSummary['to'] }} will be regenerated. Completed rounds will not change.</p>
                <button type="button" @click="showRedraw = true" class="mt-4 min-h-12 w-full rounded-xl bg-[#c7f000] px-5 text-sm font-bold text-stone-950">Review future redraw</button>
            </section>
        @endif

        <x-games.bottom-navigation :tournament="$tournament" active="players" />

        <x-ui.dialog state="showAddPlayer" kicker="Roster" title="Add player" labelledby="add-player-title" close-action="showAddPlayer = false" close-label="Close add player dialog">
                <p class="mt-3 text-sm leading-6 text-stone-600">This player joins from round {{ $tournament->rounds->firstWhere('status.value', 'scheduled')?->round_number ?? (($tournament->rounds->max('round_number') ?? 0) + 1) }}. Completed results will not change.</p>
                <form method="POST" action="{{ route('games.players.store', $tournament) }}" @submit="submitting = true" class="mt-5">
                    @csrf
                    <label for="new-player-name" class="block text-sm font-semibold text-stone-800">Player name</label>
                    <input id="new-player-name" name="name" value="{{ old('name') }}" required maxlength="100" autofocus class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4" placeholder="Player name">
                    <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" @click="showAddPlayer = false" class="min-h-12 rounded-xl border border-stone-300 bg-white px-5 text-sm font-bold text-stone-800">Cancel</button>
                        <button type="submit" :disabled="submitting" class="min-h-12 rounded-xl bg-[#c7f000] px-5 text-sm font-bold text-stone-950 disabled:cursor-wait disabled:opacity-60"><span x-show="!submitting">Add player</span><span x-show="submitting" x-cloak>Adding…</span></button>
                    </div>
                </form>
        </x-ui.dialog>

        @if ($redrawSummary && $redrawSummary['has_future'])
            <x-ui.dialog state="showRedraw" kicker="Confirm change" title="Redraw future rounds?" labelledby="redraw-title" close-action="showRedraw = false" close-label="Close redraw dialog">
                    <p class="mt-3 text-sm leading-6 text-stone-600">Rounds {{ $redrawSummary['from'] }}–{{ $redrawSummary['to'] }} will be regenerated. Completed rounds remain unchanged. This cannot be undone after the new drawing is saved.</p>
                    <form method="POST" action="{{ route('games.redraw', $tournament) }}" @submit="submitting = true" class="mt-5">
                        @csrf
                        <input type="hidden" name="confirmed" value="1">
                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <button type="button" @click="showRedraw = false" class="min-h-12 rounded-xl border border-stone-300 bg-white px-5 text-sm font-bold text-stone-800">Cancel</button>
                            <button type="submit" :disabled="submitting" class="min-h-12 rounded-xl bg-[#c7f000] px-5 text-sm font-bold text-stone-950 disabled:cursor-wait disabled:opacity-60"><span x-show="!submitting">Redraw future rounds</span><span x-show="submitting" x-cloak>Redrawing…</span></button>
                        </div>
                    </form>
            </x-ui.dialog>
        @endif
    </div>
@endsection
