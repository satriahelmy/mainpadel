@props(['label' => null, 'id', 'name', 'value' => 1, 'min' => 1, 'max' => 20])

<div>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-semibold text-stone-800">{{ $label }}</label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="number" value="{{ $value }}" min="{{ $min }}" max="{{ $max }}" {{ $attributes->merge(['class' => ($label ? 'mt-2 ' : '').'min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-center text-lg font-bold text-stone-950']) }}>
</div>
