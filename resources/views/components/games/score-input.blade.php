@props(['team', 'players', 'model', 'inputHandler' => null, 'align' => 'left'])

<label class="block {{ $align === 'right' ? 'text-right' : '' }}">
    <span class="block text-lg font-bold text-stone-950">{{ $players->map(fn ($assignment) => $assignment->player->name)->join(' + ') }}</span>
    <span class="mt-1 block text-xs font-bold uppercase tracking-[0.16em] text-stone-500">Team {{ $team }}</span>
    <input name="team_{{ strtolower($team) }}_score" type="number" inputmode="numeric" min="0" max="100" x-model="{{ $model }}" @if ($inputHandler) @input="{{ $inputHandler }}" @endif class="mt-4 min-h-20 w-full rounded-2xl border border-stone-300 bg-white px-3 text-center text-5xl font-bold text-stone-950" aria-label="Team {{ $team }} score" required>
</label>
