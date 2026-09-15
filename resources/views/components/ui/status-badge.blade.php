@props(['label', 'tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'border-stone-200 bg-stone-100 text-stone-600',
        'current' => 'border-lime-200 bg-lime-100 text-stone-900',
        'danger' => 'border-red-200 bg-red-50 text-red-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex min-h-8 items-center rounded-full border px-3 text-xs font-bold uppercase tracking-[0.12em] '.($tones[$tone] ?? $tones['neutral'])]) }}>{{ $label }}</span>
