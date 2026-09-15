@props(['membership', 'played', 'rests', 'unavailable' => false])

<div {{ $attributes->merge(['class' => 'flex items-center gap-4 py-4']) }}>
    <div class="min-w-0 flex-1">
        <p class="truncate font-bold text-stone-950">{{ $membership->player->name }}</p>
        <p class="mt-1 text-sm text-stone-500">{{ $played }} matches · {{ $rests }} rests{{ $unavailable ? ' · unavailable' : '' }}{{ $membership->status->value === 'withdrawn' ? ' · withdrawn' : '' }}</p>
    </div>
    {{ $slot }}
</div>
