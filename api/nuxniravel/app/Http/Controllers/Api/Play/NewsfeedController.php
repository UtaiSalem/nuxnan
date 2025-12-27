<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Donate;
use App\Models\Support;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Play\FriendshipResource;
use App\Http\Resources\Earn\DonateResource;
use App\Http\Resources\Shared\SupportResource;
use App\Http\Resources\UserResource;

class NewsfeedController extends Controller
{
    /**
     * Get newsfeed data (excluding activities - use ActivityController for activities).
     */
    public function index()
    {
        $authFriends = auth()->user()->getFriends()->pluck('id')->toArray();

        $peopleMayKnow = User::where('id', '!=', Auth::id())
            ->whereNotIn('id', $authFriends)
            ->inRandomOrder()
            ->limit(15)
            ->get();

        $pendingFriends = auth()->user()->getFriendRequests();

        return response()->json([
            'peopleMayKnow'     => UserResource::collection($peopleMayKnow),
            'pendingFriends'    => FriendshipResource::collection($pendingFriends),
            'donates'           => DonateResource::collection(Donate::whereNotIn('status',[2])->orderBy('remaining_points', 'DESC')->latest()->paginate(5)),
            'advertises'        => SupportResource::collection(Support::where('status',1)->where('remaining_views', '>', 0)->orderBy('remaining_views', 'DESC')->latest()->paginate(5)),
        ]);
    }
}



