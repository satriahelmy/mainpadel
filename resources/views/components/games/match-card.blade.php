@props(['match', 'tournament', 'variant' => 'play'])

@php
    $teamA = $match->matchPlayers->where('team.value', 'A');
    $teamB = $match->matchPlayers->where('team.value', 'B');
    $isCompleted = $match->status->value === 'completed';
@endphp

<article {{ $attributes->merge(['class' => 'rounded-2xl border border-stone-200 bg-white p-5 sm:p-6']) }}>
    <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Court {{ $match->court_number }}</p>
    <div class="mt-6 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
        <div class="space-y-1 text-lg font-bold text-stone-950">
            @foreach ($teamA as $assignment)
                <p>{{ $assignment->player->name }}</p>
            @endforeach
        </div>
        <span class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">VS</span>
        <div class="space-y-1 text-right text-lg font-bold text-stone-950">
            @foreach ($teamB as $assignment)
                <p>{{ $assignment->player->name }}</p>
            @endforeach
        </div>
    </div>

    @if ($variant === 'play')
        @if ($isCompleted)
            <div class="mt-7 flex items-center justify-between border-t border-stone-100 pt-4">
                <span class="text-xs font-bold uppercase tracking-[0.16em] text-stone-500">Final</span>
                <div class="flex items-center gap-4">
                    <span class="text-3xl font-bold tracking-tight text-stone-950">{{ $match->team_a_score }} — {{ $match->team_b_score }}</span>
                    <a href="{{ route('games.score.edit', [$tournament, $match]) }}" class="min-h-11 py-3 text-xs font-bold text-stone-500 underline decoration-stone-300 underline-offset-4 hover:text-stone-950">Edit</a>
                </div>
            </div>
        @else
            <a href="{{ route('games.score.edit', [$tournament, $match]) }}" class="mt-7 flex min-h-12 items-center justify-center rounded-xl bg-[#c7f000] text-sm font-bold text-stone-950 transition hover:bg-[#b8df00]">Enter Result</a>
        @endif
    @endif
</article>
