use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollVoteController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'poll_id' => 'required|exists:polls,id',
            'option_id' => 'required|exists:question_options,id',
        ]);

        $user = $request->user();
        $poll = Poll::findOrFail($request->poll_id);
        $option = QuestionOption::findOrFail($request->option_id);

        // Check if poll is ended
        if ($poll->end_date && now()->isAfter($poll->end_date)) {
            return response()->json([
                'success' => false,
                'message' => 'โพลนี้สิ้นส่วนแล้ว'
            ], 422);
        }

        // Check if user already voted
        $existingVote = PollVote::where('poll_id', $poll->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingVote) {
            return response()->json([
                'success' => false,
                'message' => 'คุณได้โหวตในโพลนี้ไปแล้ว'
            ], 422);
        }

        try {
            return DB::transaction(function () use ($poll, $option, $user) {
                // Calculate points if pool exists
                $pointsEarned = 0;
                if ($poll->points_pool > 0 && $poll->points_distributed < $poll->points_pool) {
                    $pointsEarned = $poll->points_per_vote;
                    
                    // Don't distribute more than pool
                    if (($poll->points_distributed + $pointsEarned) > $poll->points_pool) {
                        $pointsEarned = $poll->points_pool - $poll->points_distributed;
                    }

                    if ($pointsEarned > 0) {
                        $user->increment('pp', $pointsEarned);
                        $poll->increment('points_distributed', $pointsEarned);
                    }
                }

                // Create vote
                $vote = PollVote::create([
                    'poll_id' => $poll->id,
                    'poll_option_id' => $option->id,
                    'user_id' => $user->id,
                    'points_earned' => $pointsEarned,
                ]);

                // Increment option votes
                $option->increment('votes');
                $poll->increment('total_votes');

                return response()->json([
                    'success' => true,
                    'message' => $pointsEarned > 0 
                        ? "โหวตสำเร็จ! คุณได้รับ {$pointsEarned} แต้ม" 
                        : "โหวตสำเร็จ!",
                    'vote' => $vote,
                    'points_earned' => $pointsEarned
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโหวต: ' . $e->getMessage()
            ], 500);
        }
    }
}
