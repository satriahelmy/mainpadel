@props(['tournament', 'active'])

<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-stone-200 bg-[#f7f7f4] pb-[env(safe-area-inset-bottom)]" aria-label="Game navigation">
    <div class="mx-auto grid max-w-3xl grid-cols-4 px-4 py-4 text-center text-xs font-bold text-stone-500 sm:px-6">
        @foreach ([
            'play' => ['label' => 'Play', 'route' => 'games.show'],
            'rounds' => ['label' => 'Rounds', 'route' => 'games.rounds'],
            'standings' => ['label' => 'Standings', 'route' => 'games.standings'],
            'players' => ['label' => 'Players', 'route' => 'games.players'],
        ] as $key => $item)
            <a href="{{ route($item['route'], $tournament) }}" @class(['min-h-11 py-3 text-stone-950' => $active === $key, 'min-h-11 py-3' => $active !== $key]) @if ($active === $key) aria-current="page" @endif>{{ $item['label'] }}</a>
        @endforeach
    </div>
</nav>
