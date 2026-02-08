<?php

namespace App\Http\Controllers;

use App\Models\Player\Player;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\View\View;

class PlayersController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('view_players');
        return view('players.index');
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Player $player): View
    {
        $this->authorize('view_players');
        
        // Eager load relationships to prevent N+1 queries
        $player->load(['tag', 'ignoredPlayers']);
        
        return view('players.show')->with('player', $player);
    }

    /**
     * @throws AuthorizationException
     */
    public function details(string $uuid, string $server): View
    {
        $this->authorize('view_players');
        
        // Eager load relationships to prevent N+1 queries
        $player = Player::with(['tag', 'ignoredPlayers'])
            ->where('uuid', $uuid)
            ->firstOrFail();
            
        return view('players.details')->with(['player' => $player, 'server' => $server]);
    }
}
