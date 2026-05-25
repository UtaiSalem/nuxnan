<?php

namespace App\Http\Controllers\Api\Play\Typing;

use App\Events\TypingRaceStarted;
use App\Events\TypingRaceFinished;
use App\Http\Controllers\Controller;
use App\Models\TypingRaceRoom;
use App\Models\TypingRaceParticipant;
use App\Models\TypingWord;
use App\Models\TypingSentence;
use App\Models\TypingSession;
use App\Services\TypingScoreService;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TypingRaceController extends Controller
{
    protected $scoreService;
    protected $pointsService;

    public function __construct(TypingScoreService $scoreService, PointsService $pointsService)
    {
        $this->scoreService = $scoreService;
        $this->pointsService = $pointsService;
    }

    /**
     * Create a new race room.
     */
    public function createRoom(Request $request): JsonResponse
    {
        $data = $request->validate([
            'game_mode'  => 'required|in:word_typing,time_attack,sentence_typing',
            'language'   => 'required|in:th,en,ar',
            'difficulty' => 'required|in:beginner,easy,normal,hard,expert',
            'time_limit' => 'required|integer|min:30|max:300',
            'academy_id' => 'nullable|exists:academies,id',
            'max_players' => 'nullable|integer|min:2|max:50',
        ]);

        $user = Auth::user();

        // Fetch content IDs based on game mode
        if ($data['game_mode'] === 'sentence_typing') {
            $contentIds = TypingSentence::where('language', $data['language'])
                ->where('difficulty', $data['difficulty'])
                ->where('is_active', true)
                ->inRandomOrder()->take(10)->pluck('id')->toArray();
        } else {
            // Roughly 1 word every 2 seconds for time limit
            $count = ceil($data['time_limit'] / 1.5); 
            $contentIds = TypingWord::where('language', $data['language'])
                ->where('difficulty', $data['difficulty'])
                ->inRandomOrder()->take($count)->pluck('id')->toArray();
        }

        $room = TypingRaceRoom::create([
            'room_code'    => $this->generateRoomCode(),
            'host_user_id' => $user->id,
            'academy_id'   => $data['academy_id'] ?? null,
            'status'       => 'waiting',
            'game_mode'    => $data['game_mode'],
            'language'     => $data['language'],
            'difficulty'   => $data['difficulty'],
            'time_limit'   => $data['time_limit'],
            'content_ids'  => $contentIds,
            'max_players'  => $data['max_players'] ?? 10,
        ]);

        // Host joins automatically
        TypingRaceParticipant::create([
            'room_id'     => $room->id,
            'user_id'     => $user->id,
            'player_name' => $user->name,
            'status'      => 'waiting',
        ]);

        return response()->json([
            'success'   => true,
            'room_code' => $room->room_code,
            'room'      => $room->load('participants.user:id,name,profile_photo_url,avatar'),
        ]);
    }

    /**
     * Join an existing race room.
     */
    public function joinRoom(string $roomCode): JsonResponse
    {
        $room = TypingRaceRoom::where('room_code', strtoupper($roomCode))
            ->where('status', 'waiting')
            ->firstOrFail();

        if ($room->participants()->count() >= $room->max_players) {
            return response()->json(['success' => false, 'message' => 'Room is full'], 422);
        }

        $user = Auth::user();

        TypingRaceParticipant::firstOrCreate(
            ['room_id' => $room->id, 'user_id' => $user->id],
            ['player_name' => $user->name, 'status' => 'waiting']
        );

        return response()->json([
            'success' => true,
            'room'    => $room->load('participants.user:id,name,profile_photo_url,avatar'),
        ]);
    }

    /**
     * Start the race (Host only).
     */
    public function startRace(string $roomCode): JsonResponse
    {
        $room = TypingRaceRoom::where('room_code', strtoupper($roomCode))
            ->where('host_user_id', Auth::id())
            ->where('status', 'waiting')
            ->firstOrFail();

        $room->update(['status' => 'racing', 'started_at' => now()]);
        $room->participants()->update(['status' => 'racing']);

        // Broadcast race started
        broadcast(new TypingRaceStarted($room));

        return response()->json(['success' => true]);
    }

    /**
     * Submit results for a participant.
     */
    public function submitResult(Request $request, string $roomCode): JsonResponse
    {
        $room = TypingRaceRoom::where('room_code', strtoupper($roomCode))
            ->where('status', 'racing')
            ->firstOrFail();

        $data = $request->validate([
            'correct_chars' => 'required|integer|min:0',
            'total_chars'   => 'required|integer|min:0',
            'correct_words' => 'required|integer|min:0',
            'total_words'   => 'required|integer|min:0',
            'mistakes'      => 'required|integer|min:0',
            'max_combo'     => 'required|integer|min:0',
            'time_elapsed'  => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        $wpm      = $this->scoreService->calculateWpm($data['correct_chars'], $data['time_elapsed']);
        $accuracy = $this->scoreService->calculateAccuracy($data['correct_chars'], $data['total_chars']);
        $scores   = $this->scoreService->calculateScore([...$data, 'wpm' => $wpm, 'accuracy' => $accuracy, 'difficulty' => $room->difficulty]);
        $xp       = $this->scoreService->calculateXp($scores['score'], $wpm, $accuracy, $room->difficulty);

        // Save session
        $session = TypingSession::create([
            'session_token'  => \Illuminate\Support\Str::uuid()->toString(),
            'user_id'        => $user->id,
            'game_mode'      => 'classroom_race',
            'language'       => $room->language,
            'difficulty'     => $room->difficulty,
            'wpm'            => $wpm,
            'accuracy'       => $accuracy,
            'score'          => $scores['score'],
            'speed_bonus'    => $scores['speed_bonus'],
            'combo_bonus'    => $scores['combo_bonus'],
            'accuracy_bonus' => $scores['accuracy_bonus'],
            'xp_earned'      => $xp,
            'completed'      => true,
            'completed_at'   => now(),
            'correct_chars'  => $data['correct_chars'],
            'total_chars'    => $data['total_chars'],
            'correct_words'  => $data['correct_words'],
            'total_words'    => $data['total_words'],
            'mistakes'       => $data['mistakes'],
            'max_combo'      => $data['max_combo'],
            'time_elapsed'   => $data['time_elapsed'],
        ]);

        // Award XP
        $this->pointsService->addXp($user, $xp);

        // Update participant
        $finishedCount = $room->participants()->where('status', 'finished')->count();
        $participant = $room->participants()->where('user_id', $user->id)->firstOrFail();
        $participant->update([
            'session_id'  => $session->id,
            'status'      => 'finished',
            'progress'    => 100,
            'wpm'         => $wpm,
            'accuracy'    => $accuracy,
            'score'       => $scores['score'],
            'rank'        => $finishedCount + 1,
            'finished_at' => now(),
        ]);

        // Check if all finished
        $totalParticipants = $room->participants()->count();
        $totalFinished     = $room->participants()->where('status', 'finished')->count();

        if ($totalFinished >= $totalParticipants) {
            $this->finalizeRace($room);
        }

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'wpm' => $wpm,
            'score' => $scores['score'],
            'xp_earned' => $xp,
            'rank' => $participant->rank
        ]);
    }

    /**
     * Finalize race and broadcast rankings.
     */
    protected function finalizeRace(TypingRaceRoom $room): void
    {
        $room->update(['status' => 'finished', 'finished_at' => now()]);

        $rankings = $room->participants()
            ->with('user:id,name,profile_photo_url,avatar')
            ->orderBy('score', 'desc')
            ->get()
            ->map(fn($p, $i) => [
                'rank'     => $i + 1,
                'user_id'  => $p->user_id,
                'name'     => $p->user->name,
                'avatar'   => $p->user->profile_photo_url ?? $p->user->avatar,
                'wpm'      => $p->wpm,
                'accuracy' => $p->accuracy,
                'score'    => $p->score,
            ]);

        broadcast(new TypingRaceFinished($room, $rankings->toArray()));
    }

    /**
     * Get room status.
     */
    public function roomStatus(string $roomCode): JsonResponse
    {
        $room = TypingRaceRoom::where('room_code', strtoupper($roomCode))
            ->with(['participants.user:id,name,profile_photo_url,avatar'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'room'    => $room,
        ]);
    }

    /**
     * Generate a unique 6-character room code.
     */
    protected function generateRoomCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (TypingRaceRoom::where('room_code', $code)->exists());
        return $code;
    }
}
