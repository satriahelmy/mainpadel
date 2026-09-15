@extends('layouts.app')

@section('content')
    <div x-data="{
        players: @js(old('players', ['', '', '', ''])),
        points: @js((string) old('target_points', config('mainpadel.defaults.target_points'))),
        mode: @js(old('round_mode', config('mainpadel.defaults.round_mode'))),
        courts: @js((int) old('number_of_courts', 1)),
        addPlayer() { this.players.push('') },
        removePlayer(index) { this.players.splice(index, 1) },
        validPlayerCount() { return this.players.filter(player => player.trim().length > 0).length },
    }" class="py-6 sm:py-10">
        <div class="mb-8">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Start a session</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950">New Game</h1>
        </div>

        <form method="POST" action="{{ route('games.store') }}" class="space-y-8">
            @csrf

            <div class="space-y-5">
                <div>
                    <label for="name" class="block text-sm font-semibold text-stone-800">Game name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required maxlength="120" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950 placeholder:text-stone-400" placeholder="Friday Padel">
                </div>
                <div>
                    <label for="played_at" class="block text-sm font-semibold text-stone-800">Date</label>
                    <input id="played_at" type="date" name="played_at" value="{{ old('played_at', now()->toDateString()) }}" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950">
                </div>
            </div>

            <section>
                <div class="flex items-baseline justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-stone-950">Players</h2>
                        <p class="mt-1 text-sm text-stone-500">Add everyone who is playing today.</p>
                    </div>
                    <span class="text-sm font-semibold text-stone-500" x-text="players.length + ' players'"></span>
                </div>

                <div class="mt-4 space-y-3">
                    <template x-for="(player, index) in players" :key="index">
                        <div class="flex items-center gap-2">
                            <label class="sr-only" :for="'player-' + index">Player <span x-text="index + 1"></span></label>
                            <input :id="'player-' + index" name="players[]" x-model="players[index]" @keydown.enter.prevent="index === players.length - 1 ? addPlayer() : null" maxlength="100" class="min-h-12 min-w-0 flex-1 rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950 placeholder:text-stone-400" :placeholder="'Player ' + (index + 1)">
                            <button type="button" @click="removePlayer(index)" class="min-h-12 min-w-12 rounded-xl border border-stone-300 bg-white text-xl text-stone-600 hover:bg-stone-100" aria-label="Remove player">×</button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addPlayer()" class="mt-3 min-h-11 text-sm font-bold text-stone-900 underline decoration-stone-300 underline-offset-4 hover:decoration-stone-900">+ Add player</button>
            </section>

            <section class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="number_of_courts" class="block text-sm font-semibold text-stone-800">Courts</label>
                    <div class="mt-2 flex items-center gap-3">
                        <x-ui.number-stepper id="number_of_courts" name="number_of_courts" :value="(int) old('number_of_courts', 1)" min="1" max="20" x-model.number="courts" required />
                        <p x-show="validPlayerCount() >= 4 && courts > Math.floor(validPlayerCount() / 4)" x-cloak class="mt-2 text-sm leading-6 text-stone-600" x-text="validPlayerCount() + ' players can fill ' + Math.floor(validPlayerCount() / 4) + ' court' + (Math.floor(validPlayerCount() / 4) === 1 ? '' : 's') + ' per round.'"></p>
                    </div>
                </div>
                <div>
                    <label for="target_points" class="block text-sm font-semibold text-stone-800">Points per match</label>
                    <div class="mt-2 flex gap-2">
                        @foreach ([15, 21] as $pointsOption)
                            <button type="button" @click="points = '{{ $pointsOption }}'" :class="points === '{{ $pointsOption }}' ? 'border-stone-950 bg-stone-950 text-white' : 'border-stone-300 bg-white text-stone-800'" class="min-h-12 flex-1 rounded-xl border px-3 text-sm font-bold">{{ $pointsOption }}</button>
                        @endforeach
                        <button type="button" @click="points = ''" :class="points !== '15' && points !== '21' ? 'border-stone-950 bg-stone-950 text-white' : 'border-stone-300 bg-white text-stone-800'" class="min-h-12 flex-1 rounded-xl border px-3 text-sm font-bold">Custom</button>
                    </div>
                    <input id="target_points" type="number" name="target_points" x-model="points" min="1" max="100" required class="mt-3 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950" placeholder="Target points">
                </div>
            </section>

            <section>
                <h2 class="text-lg font-bold text-stone-950">Rounds</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border border-stone-300 bg-white px-4">
                        <input type="radio" name="round_mode" value="auto" x-model="mode" class="h-5 w-5 accent-lime-500">
                        <span><span class="block font-semibold text-stone-900">Auto</span><span class="text-sm text-stone-500">Fair default rotation</span></span>
                    </label>
                    <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border border-stone-300 bg-white px-4">
                        <input type="radio" name="round_mode" value="custom" x-model="mode" class="h-5 w-5 accent-lime-500">
                        <span><span class="block font-semibold text-stone-900">Custom</span><span class="text-sm text-stone-500">Choose number of rounds</span></span>
                    </label>
                </div>
                <div x-show="mode === 'custom'" x-cloak class="mt-3">
                    <label for="number_of_rounds" class="block text-sm font-semibold text-stone-800">Number of rounds</label>
                    <input id="number_of_rounds" type="number" name="number_of_rounds" min="1" max="50" value="{{ old('number_of_rounds') }}" :required="mode === 'custom'" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950">
                </div>
            </section>

            <div class="border-t border-stone-200 pt-6">
                <p x-show="validPlayerCount() < 4" x-cloak class="mb-3 text-sm font-semibold text-red-700">Add at least 4 players to start.</p>
                <button type="submit" :disabled="validPlayerCount() < 4" class="min-h-13 w-full rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950 transition hover:bg-[#b8df00] disabled:cursor-not-allowed disabled:bg-stone-200 disabled:text-stone-500">Generate Draw</button>
            </div>
        </form>
    </div>
@endsection
