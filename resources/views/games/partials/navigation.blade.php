<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-stone-200 bg-[#f7f7f4] pb-[env(safe-area-inset-bottom)]" aria-label="Game navigation">
    <div class="mx-auto grid max-w-3xl grid-cols-4 px-4 py-4 text-center text-xs font-bold text-stone-500 sm:px-6">
        <a href="{{ route('games.show', $tournament) }}" class="{{ $active === 'play' ? 'text-stone-950' : '' }}">Play</a>
        <a href="{{ route('games.rounds', $tournament) }}" class="{{ $active === 'rounds' ? 'text-stone-950' : '' }}">Rounds</a>
        <a href="{{ route('games.standings', $tournament) }}" class="{{ $active === 'standings' ? 'text-stone-950' : '' }}">Standings</a>
        <a href="{{ route('games.players', $tournament) }}" class="{{ $active === 'players' ? 'text-stone-950' : '' }}">Players</a>
    </div>
</nav>
