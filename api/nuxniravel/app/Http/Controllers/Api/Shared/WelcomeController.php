<?php

namespace App\Http\Controllers\Api\Shared;

use App\Models\Post;
use App\Models\User;

use App\Models\Course;
use App\Models\Donate;
use App\Models\Lesson;
use App\Models\VisitorCounter;
use App\Http\Resources\Shared\UserResource;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\Earn\DonateResource;
use Illuminate\Foundation\Application;

class WelcomeController extends \App\Http\Controllers\Controller
{
    public function index(){
        VisitorCounter::first()->increment('visitor_counter');
        $visitorCounter = VisitorCounter::pluck('visitor_counter')->first();

        return response()->json([
            'canLogin'          => Route::has('login'),
            'canRegister'       => Route::has('register'),
            'laravelVersion'    => Application::VERSION,
            'phpVersion'        => PHP_VERSION,

            'usersCount'        => User::count(),
            'coursesCount'      => Course::count(),
            'lessonsCount'      => Lesson::count(),
            'postsCount'        => Post::count(),
            'visitorCounter'    => $visitorCounter,

            'donates'           => DonateResource::collection(Donate::whereNotIn('status',[2])->orderBy('remaining_points', 'DESC')->latest()->paginate(8)),
            'donateRecipients'  => UserResource::collection(User::whereNotIn('id',[1])->orderBy('pp', 'DESC')->latest()->paginate(12)),    

            'ceo'               => User::find(1) ? new UserResource(User::find(1)) : null,

            'meta' => [
                'title' => 'Welcome to Plearnd',
                'description' => 'Plearnd is a platform for learning and sharing knowledge. It is a place to learn and grow. It is a place to share and connect. It is a place to teach and inspire. It is a place to be you. It is a place to be us. It is a place to be.',
                'image' => '/storage/images/plearnd-logo.png',
                'url' => 'https://www.plearnd.com',
            ],
        ]);
    }
}
