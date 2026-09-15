@extends('layouts.app')

@section('content')
    <div x-data="{ showRedraw: false, submitting: false }" @keydown.escape.window="showRedraw = false" class="py-6 pb-28 sm:py-10 sm:pb-10">
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
                    <x-games.match-card :match="$match" :tournament="$tournament" />
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

            @if ($redrawSummary['has_future'] && $tournament->status->value !== 'completed')
                <section class="mt-8 border-t border-stone-200 pt-6">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Need to adjust the rotation?</p>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Rounds {{ $redrawSummary['from'] }}–{{ $redrawSummary['to'] }} are still unplayed and can be generated again. Locked rounds will not change.</p>
                    <button type="button" @click="showRedraw = true" class="mt-4 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-5 text-sm font-bold text-stone-800 hover:border-stone-950 hover:text-stone-950">Regenerate future rounds</button>
                </section>
            @endif

            @if ($round->status->value === 'scheduled' && $tournament->status->value !== 'completed')
                <form method="POST" action="{{ route('games.start', $tournament) }}" x-data="{ submitting: false }" @submit="submitting = true" class="mt-8">
                    @csrf
                    <button type="submit" :disabled="submitting" class="min-h-13 w-full rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950 disabled:cursor-wait disabled:opacity-60"><span x-show="!submitting">Start round {{ $round->round_number }}</span><span x-show="submitting" x-cloak>Starting…</span></button>
                </form>
            @elseif ($round->status->value === 'completed' && $tournament->status->value === 'ongoing')
                <form method="POST" action="{{ route('games.next', $tournament) }}" x-data="{ submitting: false }" @submit="submitting = true" class="mt-8">
                    @csrf
                    <button type="submit" :disabled="submitting" class="min-h-13 w-full rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950 disabled:cursor-wait disabled:opacity-60"><span x-show="!submitting">Continue to next round</span><span x-show="submitting" x-cloak>Preparing next round…</span></button>
                </form>
            @elseif ($tournament->status->value === 'completed')
                <a href="{{ route('games.standings', $tournament) }}" class="mt-8 flex min-h-13 items-center justify-center rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950">View final standings</a>
            @endif

            <x-games.bottom-navigation :tournament="$tournament" active="play" />
        @endif

        @if ($redrawSummary['has_future'] && $tournament->status->value !== 'completed')
            <x-ui.dialog state="showRedraw" kicker="Confirm change" title="Regenerate future rounds?" labelledby="play-redraw-title" close-action="showRedraw = false" close-label="Close redraw dialog">
                <p class="mt-3 text-sm leading-6 text-stone-600">Rounds {{ $redrawSummary['from'] }}–{{ $redrawSummary['to'] }} will be regenerated. Completed rounds and matches remain unchanged.</p>
                <form method="POST" action="{{ route('games.redraw', $tournament) }}" @submit="submitting = true" class="mt-5">
                    @csrf
                    <input type="hidden" name="confirmed" value="1">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" @click="showRedraw = false" class="min-h-12 rounded-xl border border-stone-300 bg-white px-5 text-sm font-bold text-stone-800">Cancel</button>
                        <button type="submit" :disabled="submitting" class="min-h-12 rounded-xl bg-[#c7f000] px-5 text-sm font-bold text-stone-950 disabled:cursor-wait disabled:opacity-60"><span x-show="!submitting">Regenerate rounds</span><span x-show="submitting" x-cloak>Regenerating…</span></button>
                    </div>
                </form>
            </x-ui.dialog>
        @endif
    </div>
@endsection
