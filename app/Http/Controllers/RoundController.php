<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\Drawing\DrawingException;
use App\Services\TournamentDrawingService;
use Illuminate\Http\RedirectResponse;

class RoundController extends Controller
{
    public function __construct(private readonly TournamentDrawingService $drawingService) {}

    public function next(Tournament $tournament): RedirectResponse
    {
        try {
            $round = $this->drawingService->advance($tournament);
        } catch (DrawingException $exception) {
            return back()->withErrors(['round' => $exception->getMessage()]);
        }

        return redirect()->route('games.show', $tournament)->with('success', $round ? "Round {$round->round_number} is ready." : 'Game complete.');
    }
}
