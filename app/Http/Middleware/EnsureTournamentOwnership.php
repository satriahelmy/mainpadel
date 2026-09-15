<?php

namespace App\Http\Middleware;

use App\Models\MatchModel;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTournamentOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $tournament = $request->route('tournament');

        if ($tournament instanceof Tournament && (int) $tournament->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        $match = $request->route('match');
        if ($match instanceof MatchModel) {
            abort_unless($tournament instanceof Tournament && $match->round()->where('tournament_id', $tournament->id)->exists(), 404);
        }

        $membership = $request->route('membership');
        if ($membership instanceof TournamentPlayer) {
            abort_unless($tournament instanceof Tournament && (int) $membership->tournament_id === (int) $tournament->id, 404);
        }

        return $next($request);
    }
}
