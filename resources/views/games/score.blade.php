@extends('layouts.app')

@section('content')
    @php
        $teamA = $match->matchPlayers->where('team.value', 'A');
        $teamB = $match->matchPlayers->where('team.value', 'B');
    @endphp
    <div class="py-6 sm:py-10">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Court {{ $match->court_number }} · Round {{ $match->round->round_number }}</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950">Enter result</h1>
        <p class="mt-2 text-sm text-stone-500">Scores must total {{ $tournament->target_points }} points. You can correct a saved result without changing the draw.</p>

        <form method="POST" action="{{ route('games.score.update', [$tournament, $match]) }}" x-data="{ scoreA: @js(old('team_a_score', $match->team_a_score)), scoreB: @js(old('team_b_score', $match->team_b_score)), target: {{ $tournament->target_points }}, submitting: false, updateOpponent() { if (this.scoreA !== '' && Number(this.scoreA) >= 0 && Number(this.scoreA) <= this.target) this.scoreB = this.target - Number(this.scoreA) } }" @submit="submitting = true" class="mt-8">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-[1fr_auto_1fr] items-end gap-3">
                <x-games.score-input team="A" :players="$teamA" model="scoreA" input-handler="updateOpponent()" />
                <span class="pb-7 text-sm font-bold uppercase tracking-[0.16em] text-stone-400">VS</span>
                <x-games.score-input team="B" :players="$teamB" model="scoreB" align="right" />
            </div>
            @error('team_a_score')<p class="mt-4 text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>@enderror
            @error('team_b_score')<p class="mt-4 text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>@enderror
            <button type="submit" :disabled="submitting || scoreA === '' || scoreB === '' || Number(scoreA) + Number(scoreB) !== target" class="mt-8 min-h-13 w-full rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950 disabled:cursor-wait disabled:bg-stone-200 disabled:text-stone-500"><span x-show="!submitting">{{ $match->status->value === 'completed' ? 'Update Result' : 'Save Result' }}</span><span x-show="submitting" x-cloak>Saving…</span></button>
        </form>
    </div>
@endsection
