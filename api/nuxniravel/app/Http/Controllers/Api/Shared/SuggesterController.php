<?php

namespace App\Http\Controllers\Api\Shared;

use App\Models\User;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;

class SuggesterController extends \App\Http\Controllers\Controller
{
    public function index(User $user)
    {
        if ($user) {
            return response()->json([
                'isSuggesterActive' => true,
                'suggester'         => new UserResource($user),
                'error'             => null,
            ]);           
        }else {
            return response()->json([
                'isSuggesterActive' => false,
                'suggester' => null,
                'error' => 'Suggester not found/ไม่พบผู้แนะนำ',
            ]);
        }
    }

    public function checkSuggesterExist(User $user)
    {
        if (($user->id === 1)||($user->no_of_ref < 5)) {
            return response()->json([
                'isSuggesterActive' => true,
                'suggester' => new UserResource($user),
            ]);
        }else {
            return response()->json([
                'isSuggesterActive' => false,
                'suggester' => new UserResource($user),
                'error' => 'Suggester has reached the maximum number of referrals/ผู้แนะนำได้รับจำนวนการแนะนำสูงสุดแล้ว',
            ]);
        }
    } 
    
}
