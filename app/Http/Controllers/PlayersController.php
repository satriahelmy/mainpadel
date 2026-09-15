<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlayerRequest;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
use App\Services\Drawing\DrawingException;
use App\Services\PlayerManagementService;
use App\Services\TournamentDrawingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlayersController extends Controller
{
    public function __construct(
        private readonly PlayerManagementService $playerService,
        private readonly TournamentDrawingService $drawingService,
    ) {}

    public function index(Tournament $tournament): View
    {
        $tournament->load(['tournamentPlayers.player', 'rounds.matches.matchPlayers']);
        $participation = $this->participation($tournament);
        $redrawSummary = session('redraw_summary');

        return view('games.players', compact('tournament', 'participation', 'redrawSummary'));
    }

    public function store(StorePlayerRequest $request, Tournament $tournament): RedirectResponse
    {
        try {
            $this->playerService->add($tournament, $request->validated('name'));
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['name' => $exception->getMessage()])->withInput();
        }

        $tournament->refresh();

        return redirect()->route('games.players', $tournament)
            ->with('success', 'Player added. Review the future redraw before applying it.')
            ->with('redraw_summary', $this->drawingService->redrawSummary($tournament));
    }

    public function withdraw(Tournament $tournament, TournamentPlayer $membership): RedirectResponse
    {
        abort_unless($membership->tournament_id === $tournament->id, 404);

        try {
            $this->playerService->withdraw($tournament, $membership);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['player' => $exception->getMessage()]);
        }

        $tournament->refresh();

        return redirect()->route('games.players', $tournament)
            ->with('success', 'Player marked as withdrawn. Completed results remain unchanged.')
            ->with('redraw_summary', $this->drawingService->redrawSummary($tournament));
    }

    public function redraw(Tournament $tournament): RedirectResponse
    {
        try {
            $round = $this->drawingService->redrawFuture($tournament);
        } catch (DrawingException $exception) {
            return back()->withErrors(['redraw' => $exception->getMessage()]);
        }

        return redirect()->route('games.show', $tournament)->with('success', $round ? 'Future rounds redrawn.' : 'There are no future rounds to redraw.');
    }

    private function participation(Tournament $tournament): array
    {
        $rows = [];

        foreach ($tournament->tournamentPlayers as $membership) {
            $played = 0;
            $rests = 0;

            foreach ($tournament->rounds as $round) {
                $roundPlayerIds = $round->matches->flatMap(fn ($match) => $match->matchPlayers->pluck('player_id'))->all();
                if (in_array($membership->player_id, $roundPlayerIds, true)) {
                    $played++;
                } elseif ($membership->joined_at_round <= $round->round_number && ($membership->left_at_round === null || $membership->left_at_round > $round->round_number)) {
                    $rests++;
                }
            }

            $rows[$membership->player_id] = [
                'membership' => $membership,
                'played' => $played,
                'rests' => $rests,
            ];
        }

        return $rows;
    }
}
