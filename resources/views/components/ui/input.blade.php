@props(['label', 'id', 'name', 'type' => 'text', 'value' => null])

<div>
    <label for="{{ $id }}" class="block text-sm font-semibold text-stone-800">{{ $label }}</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value ?? old($name) }}" {{ $attributes->merge(['class' => 'mt-2 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950 placeholder:text-stone-400']) }}>
</div>
