@extends('layouts.app')

@section('content')
    <div class="py-6 pb-28 sm:py-10 sm:pb-10">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $tournament->status->value === 'completed' ? 'Final standings' : 'Live standings' }}</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950">{{ $tournament->name }}</h1>

        <div class="mt-8 divide-y divide-stone-200 border-y border-stone-200">
            @forelse ($standings as $standing)
                <x-games.standing-row :standing="$standing" :class="$standing['rank'] <= 3 ? 'bg-lime-50' : ''" />
            @empty
                <p class="py-10 text-stone-600">Standings will appear after a result is saved.</p>
            @endforelse
        </div>

        <div class="mt-8 overflow-x-auto rounded-2xl border border-stone-200 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-stone-200 text-xs uppercase tracking-[0.12em] text-stone-500">
                    <tr><th class="px-4 py-3">Rank</th><th class="px-4 py-3">Player</th><th class="px-4 py-3">P</th><th class="px-4 py-3">W</th><th class="px-4 py-3">L</th><th class="px-4 py-3">PF</th><th class="px-4 py-3">PA</th><th class="px-4 py-3">+/-</th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($standings as $standing)
                        <tr><td class="px-4 py-3 font-semibold">{{ $standing['rank'] }}</td><td class="px-4 py-3 font-semibold">{{ $standing['name'] }}</td><td class="px-4 py-3">{{ $standing['played'] }}</td><td class="px-4 py-3">{{ $standing['wins'] }}</td><td class="px-4 py-3">{{ $standing['losses'] }}</td><td class="px-4 py-3">{{ $standing['points_for'] }}</td><td class="px-4 py-3">{{ $standing['points_against'] }}</td><td class="px-4 py-3">{{ $standing['point_difference'] >= 0 ? '+' : '' }}{{ $standing['point_difference'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-games.bottom-navigation :tournament="$tournament" active="standings" />
    </div>
@endsection
