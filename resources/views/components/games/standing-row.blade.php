@props(['standing'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-4 py-4']) }}>
    <span class="w-7 text-center text-lg font-bold {{ $standing['rank'] <= 3 ? 'text-lime-700' : 'text-stone-400' }}">{{ $standing['rank'] }}</span>
    <div class="min-w-0 flex-1">
        <p class="truncate font-bold text-stone-950">{{ $standing['name'] }}</p>
        <p class="mt-1 text-sm text-stone-500">{{ $standing['played'] }} played · {{ $standing['point_difference'] >= 0 ? '+' : '' }}{{ $standing['point_difference'] }}</p>
    </div>
    <div class="text-right">
        <p class="text-2xl font-bold tracking-tight text-stone-950">{{ $standing['points'] }}</p>
        <p class="text-xs font-bold uppercase tracking-[0.12em] text-stone-500">points</p>
    </div>
</div>
