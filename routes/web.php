<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DrawController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\PlayersController;
use App\Http\Controllers\RoundController;
use App\Http\Controllers\ScoreController;
use App\Http\Requests\StoreTournamentRequest;
use App\Services\Drawing\DrawingException;
use App\Services\GameCreationService;
use App\Services\TournamentDrawingService;
use Illuminate\Support\Facades\Route;

Route::get('/', [GameController::class, 'index'])->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/games/create', [GameController::class, 'create'])->name('games.create');
    Route::post('/games', function (StoreTournamentRequest $request, GameCreationService $creationService, TournamentDrawingService $drawingService) {
        $tournament = $creationService->create($request->user(), $request->validated());

        try {
            $drawingService->generateInitial($tournament);
        } catch (DrawingException $exception) {
            return redirect()->route('games.draw', $tournament)->withErrors(['draw' => $exception->getMessage()]);
        }

        return redirect()->route('games.draw', $tournament);
    })->name('games.store');

    Route::middleware('game.owner')->group(function (): void {
        Route::get('/games/{tournament}', [GameController::class, 'show'])->name('games.show');
        Route::get('/games/{tournament}/draw', [DrawController::class, 'show'])->name('games.draw');
        Route::post('/games/{tournament}/draw', [DrawController::class, 'store'])->name('games.draw.store');
        Route::post('/games/{tournament}/start', [DrawController::class, 'start'])->name('games.start');
        Route::post('/games/{tournament}/next', [RoundController::class, 'next'])->name('games.next');
        Route::get('/games/{tournament}/matches/{match}/score', [ScoreController::class, 'edit'])->name('games.score.edit');
        Route::put('/games/{tournament}/matches/{match}/score', [ScoreController::class, 'update'])->name('games.score.update');
        Route::get('/games/{tournament}/standings', [GameController::class, 'standings'])->name('games.standings');
        Route::get('/games/{tournament}/rounds', [GameController::class, 'rounds'])->name('games.rounds');
        Route::get('/games/{tournament}/players', [PlayersController::class, 'index'])->name('games.players');
        Route::post('/games/{tournament}/players', [PlayersController::class, 'store'])->name('games.players.store');
        Route::post('/games/{tournament}/players/{membership}/unavailable', [PlayersController::class, 'markUnavailable'])->name('games.players.unavailable');
        Route::post('/games/{tournament}/players/{membership}/available', [PlayersController::class, 'markAvailable'])->name('games.players.available');
        Route::post('/games/{tournament}/players/{membership}/withdraw', [PlayersController::class, 'withdraw'])->name('games.players.withdraw');
        Route::post('/games/{tournament}/redraw', [PlayersController::class, 'redraw'])->name('games.redraw');
    });
});
