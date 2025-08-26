<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\View\View;

class TradesController extends Controller
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
        return view('trades.index');
    }
}