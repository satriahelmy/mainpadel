<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\Drawing\DrawingException;
use App\Services\TournamentDrawingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DrawController extends Controller
{
    public function __construct(private readonly TournamentDrawingService $drawingService) {}

    public function show(Tournament $tournament): View
    {
        $tournament->load(['tournamentPlayers.player', 'rounds.matches.matchPlayers.player']);

        return view('games.draw', [
            'tournament' => $tournament,
            'round' => $tournament->rounds->first(),
        ]);
    }

    public function store(Tournament $tournament): RedirectResponse
    {
        try {
            $this->drawingService->generateInitial($tournament);
        } catch (DrawingException $exception) {
            return back()->withErrors(['draw' => $exception->getMessage()]);
        }

        return redirect()->route('games.draw', $tournament)->with('success', 'Drawing ready.');
    }

    public function start(Tournament $tournament): RedirectResponse
    {
        try {
            $this->drawingService->start($tournament);
        } catch (DrawingException $exception) {
            return back()->withErrors(['draw' => $exception->getMessage()]);
        }

        return redirect()->route('games.show', $tournament);
    }
}
