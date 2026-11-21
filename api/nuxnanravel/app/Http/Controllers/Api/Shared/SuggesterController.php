<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicUserResource;
use App\Models\User;

class SuggesterController extends Controller
{
    public function index(User $user)
    {
        if ($user) {
            return response()->json([
                'isSuggesterActive' => true,
                'suggester' => new PublicUserResource($user),
                'error' => null,
            ]);
        } else {
            return response()->json([
                'isSuggesterActive' => false,
                'suggester' => null,
                'error' => 'Suggester not found/ไม่พบผู้แนะนำ',
            ]);
        }
    }

    public function checkSuggesterExist(User $user)
    {
        if ($user->canAcceptReferral()) {
            return response()->json([
                'isSuggesterActive' => true,
                'suggester' => new PublicUserResource($user),
            ]);
        } else {
            return response()->json([
                'isSuggesterActive' => false,
                'suggester' => new PublicUserResource($user),
                'error' => 'Suggester has reached the maximum number of referrals/ผู้แนะนำได้รับจำนวนการแนะนำสูงสุดแล้ว',
            ]);
        }
    }
}
