@props(['variant' => 'primary', 'type' => 'button'])

@php
    $variants = [
        'primary' => 'bg-[#c7f000] text-stone-950 hover:bg-[#b8df00]',
        'secondary' => 'border border-stone-300 bg-white text-stone-800 hover:border-stone-950 hover:text-stone-950',
        'dark' => 'bg-stone-950 text-white hover:bg-stone-800',
        'text' => 'text-stone-700 underline decoration-stone-300 underline-offset-4 hover:text-stone-950',
    ];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'motion-quick inline-flex min-h-12 items-center justify-center rounded-xl px-5 text-sm font-bold transition disabled:cursor-wait disabled:opacity-60 '.($variants[$variant] ?? $variants['primary'])]) }}>
    {{ $slot }}
</button>
