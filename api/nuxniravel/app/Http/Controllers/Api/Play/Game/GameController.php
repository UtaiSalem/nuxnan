<?php

namespace App\Http\Controllers\Api\Play\Game;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GameController extends Controller
{
    public function guessing_number_game()
    {
        return response()->json(['success' => true]);
    }
    public function xo_game()
    {
        return response()->json(['success' => true]);
    }
    public function snake_game()
    {
        return response()->json(['success' => true]);
    }
    
    public function mental_match_game()
    {
        return response()->json(['success' => true]);
    }
}
