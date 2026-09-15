<?php

namespace App\Http\Controllers;

use App\Enums\RoundStatus;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Services\StandingsService;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(): View
    {
        $games = request()->user()->tournaments()
            ->latest('played_at')
            ->latest('id')
            ->take(8)
            ->get();

        return view('home', compact('games'));
    }

    public function create(): View
    {
        return view('games.create');
    }

    public function show(Tournament $tournament): View
    {
        $tournament->load(['tournamentPlayers.player', 'rounds.matches.matchPlayers.player']);

        if ($tournament->status === TournamentStatus::Draft) {
            return view('games.draw', ['tournament' => $tournament, 'round' => $tournament->rounds->first()]);
        }

        $round = $tournament->rounds->first(fn ($round): bool => $round->status === RoundStatus::Ongoing)
            ?? $tournament->rounds->filter(fn ($round): bool => $round->status === RoundStatus::Completed)->sortByDesc('round_number')->first()
            ?? $tournament->rounds->first(fn ($round): bool => $round->status === RoundStatus::Scheduled);

        return view('games.play', compact('tournament', 'round'));
    }

    public function standings(Tournament $tournament, StandingsService $standingsService): View
    {
        $standings = $standingsService->forTournament($tournament);

        return view('games.standings', compact('tournament', 'standings'));
    }

    public function rounds(Tournament $tournament): View
    {
        $tournament->load(['rounds.matches.matchPlayers.player']);

        return view('games.rounds', compact('tournament'));
    }
}
