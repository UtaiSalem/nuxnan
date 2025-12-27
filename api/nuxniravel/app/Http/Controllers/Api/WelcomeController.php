<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Models\Course;
use App\Models\Donate;
use App\Models\Lesson;
use App\Models\VisitorCounter;
use App\Http\Resources\UserResource;
use App\Http\Resources\Earn\DonateResource;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function index()
    {
        // Increment visitor counter
        $visitorCounterModel = VisitorCounter::first();
        if ($visitorCounterModel) {
            $visitorCounterModel->increment('visitor_counter');
            $visitorCounter = $visitorCounterModel->visitor_counter;
        } else {
            // Handle case where VisitorCounter might not exist yet
            $visitorCounter = 0; 
        }

        return response()->json([
            'usersCount'        => User::count(),
            'coursesCount'      => Course::count(),
            'lessonsCount'      => Lesson::count(),
            'postsCount'        => Post::count(),
            'visitorCounter'    => $visitorCounter,

            'donates'           => DonateResource::collection(Donate::whereNotIn('status', [2])->orderBy('remaining_points', 'DESC')->latest()->paginate(8)),
            'donateRecipients'  => UserResource::collection(User::whereNotIn('id', [1])->orderBy('pp', 'DESC')->latest()->paginate(12)),

            'ceo'               => new UserResource(User::find(1)),
        ]);
    }
}
