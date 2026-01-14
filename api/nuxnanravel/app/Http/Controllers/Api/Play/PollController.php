<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;

use App\Models\Poll;
use App\Models\Activity;
use App\Enums\ActivityType;
use App\Http\Requests\StorePollRequest;
use App\Http\Requests\UpdatePollRequest;
use App\Http\Resources\Play\PollResource;
use App\Http\Resources\Play\ActivityResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * Creates a standalone Poll with its own Activity.
     */
    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|min:5|max:500',
            'options' => 'required|array|min:2|max:6',
            'options.*' => 'required|string|max:100',
            'duration' => 'required|integer|min:1|max:720',
            'is_multiple' => 'boolean',
            'points_pool' => 'integer|min:0',
            'max_votes' => 'integer|min:1',
            'privacy_settings' => 'integer|in:1,2,3',
        ]);

        $user = auth()->user();
        $pollPointsPool = (int)$request->input('points_pool', 0);
        $totalPointsNeeded = 180 + $pollPointsPool;

        if ($user->pp < $totalPointsNeeded) {
            return response()->json([
                'success' => false,
                'message' => "คุณมีแต้มสะสมไม่พอ (ต้องการ {$totalPointsNeeded} แต้ม)",
            ], 403);
        }

        try {
            DB::beginTransaction();

            $maxVotes = (int)$request->input('max_votes', 100);
            $pointsPerVote = $pollPointsPool > 0 ? floor($pollPointsPool / $maxVotes) : 0;
            $duration = (int)$request->input('duration', 24);

            $poll = Poll::create([
                'user_id' => $user->id,
                'title' => $request->input('question'),
                'description' => $request->input('description'),
                'start_date' => now(),
                'end_date' => now()->addHours($duration),
                'is_active' => true,
                'is_public' => $request->input('privacy_settings', 3) == 3,
                'is_multiple_choice' => $request->boolean('is_multiple', false),
                'max_votes' => $maxVotes,
                'points_pool' => $pollPointsPool,
                'points_per_vote' => $pointsPerVote,
                'points_distributed' => 0,
            ]);

            $options = $request->input('options', []);
            foreach ($options as $index => $optionText) {
                if (trim($optionText)) {
                    $poll->options()->create([
                        'text' => trim($optionText),
                        'position' => $index,
                    ]);
                }
            }

            $activity = new Activity();
            $activity->user_id = $user->id;
            $activity->activity_type = ActivityType::CREATE_POLL->value;
            $activity->activityable()->associate($poll);
            $activity->save();

            $user->decrement('pp', $totalPointsNeeded);

            DB::commit();

            $poll->load(['user', 'options']);

            // Refresh the activity from database to ensure activityable_type is set correctly
            // Then eager load with nested relationships for Poll
            $activity = Activity::with([
                'user',
                'activityable.user',
                'activityable.options'
            ])->find($activity->id);

            return response()->json([
                'success' => true,
                'message' => 'สร้างโพลสำเร็จ!',
                'poll' => new PollResource($poll),
                'activity' => new ActivityResource($activity),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถสร้างโพลได้: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Poll $poll)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Poll $poll)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePollRequest $request, Poll $poll)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Poll $poll)
    {
        // Check if user owns this poll
        if ($poll->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this poll.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Delete poll votes
            $poll->votes()->delete();

            // Delete poll options
            $poll->options()->delete();

            // Delete poll likes
            $poll->likes()->delete();

            // Delete poll dislikes
            $poll->dislikes()->delete();

            // Delete poll comments
            $poll->comments()->delete();

            // Update any posts that reference this poll
            \App\Models\Post::where('poll_id', $poll->id)->update(['poll_id' => null]);
            \App\Models\CoursePost::where('poll_id', $poll->id)->update(['poll_id' => null]);

            // Delete activities associated with this poll
            $poll->activities()->delete();

            // Delete the poll itself
            $poll->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Poll deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete poll: ' . $e->getMessage(),
            ], 500);
        }
    }
}
