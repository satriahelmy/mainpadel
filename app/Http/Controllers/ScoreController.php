<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScoreRequest;
use App\Models\MatchModel;
use App\Models\Tournament;
use App\Services\ScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScoreController extends Controller
{
    public function __construct(private readonly ScoreService $scoreService) {}

    public function edit(Tournament $tournament, MatchModel $match): View
    {
        abort_unless($match->round()->where('tournament_id', $tournament->id)->exists(), 404);
        $match->load(['round.tournament', 'matchPlayers.player']);

        return view('games.score', compact('tournament', 'match'));
    }

    public function update(StoreScoreRequest $request, Tournament $tournament, MatchModel $match): RedirectResponse
    {
        abort_unless($match->round()->where('tournament_id', $tournament->id)->exists(), 404);

        try {
            $this->scoreService->save($match, (int) $request->validated('team_a_score'), (int) $request->validated('team_b_score'));
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['team_a_score' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('games.show', $tournament)->with('success', 'Result saved.');
    }
}
