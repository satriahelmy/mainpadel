@props(['state', 'kicker' => null, 'title', 'labelledby', 'closeAction', 'closeLabel' => 'Close dialog'])

<div x-show="{{ $state }}" x-cloak class="fixed inset-0 z-30 flex items-end bg-stone-950/30 p-4 sm:items-center sm:justify-center" role="presentation">
    <section x-show="{{ $state }}" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-4 opacity-0" role="dialog" aria-modal="true" aria-labelledby="{{ $labelledby }}" class="w-full rounded-2xl border border-stone-200 bg-[#f7f7f4] p-5 shadow-xl sm:max-w-md sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                @if ($kicker)
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-500">{{ $kicker }}</p>
                @endif
                <h2 id="{{ $labelledby }}" class="mt-2 text-xl font-bold text-stone-950">{{ $title }}</h2>
            </div>
            <button type="button" @click="{{ $closeAction }}" class="min-h-11 min-w-11 rounded-xl border border-stone-300 bg-white text-xl text-stone-600" aria-label="{{ $closeLabel }}">×</button>
        </div>
        {{ $slot }}
    </section>
</div>
