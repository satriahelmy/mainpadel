@props(['message', 'tone' => 'success'])

@php
    $classes = $tone === 'error'
        ? 'border-red-200 bg-red-50 text-red-800'
        : 'border-lime-200 bg-lime-100 text-stone-900';
@endphp

<div {{ $attributes->merge(['class' => 'mb-5 rounded-xl border px-4 py-3 text-sm font-medium '.$classes]) }} role="status">
    {{ $message }}
</div>
