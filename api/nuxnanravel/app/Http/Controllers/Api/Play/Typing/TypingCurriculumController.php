<?php

namespace App\Http\Controllers\Api\Play\Typing;

use App\Http\Controllers\Controller;
use App\Services\TypingCurriculumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TypingCurriculumController extends Controller
{
    protected TypingCurriculumService $curriculumService;

    public function __construct(TypingCurriculumService $curriculumService)
    {
        $this->curriculumService = $curriculumService;
    }

    /**
     * Get curriculum overview with stages, user unlock status, and stars
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'language' => 'nullable|in:th,en',
            'level' => 'nullable|integer|min:1|max:6',
        ]);

        $user = Auth::user();
        $language = $request->query('language', 'th');
        $level = (int) $request->query('level', 1);

        $curriculum = $this->curriculumService->getCurriculum($user, $language, $level);

        return response()->json([
            'success' => true,
            'data' => $curriculum,
        ]);
    }

    /**
     * Get single stage details (target keys, exercise pool, criteria)
     */
    public function show(Request $request, int $stageId): JsonResponse
    {
        $user = Auth::user();
        $stageData = $this->curriculumService->getStage($user, $stageId);

        return response()->json([
            'success' => true,
            'data' => $stageData,
        ]);
    }

    /**
     * Submit an attempt for a stage
     */
    public function submit(Request $request, int $stageId): JsonResponse
    {
        $data = $request->validate([
            'session_token' => 'nullable|string|max:64',
            'wpm' => 'required|integer|min:0',
            'accuracy' => 'required|numeric|min:0|max:100',
            'time_elapsed' => 'required|integer|min:1',
            'correct_chars' => 'required|integer|min:0',
            'total_chars' => 'required|integer|min:0',
            'mistakes' => 'required|integer|min:0',
            'max_combo' => 'nullable|integer|min:0',
            'correct_words' => 'nullable|integer|min:0',
            'total_words' => 'nullable|integer|min:0',
            'score' => 'nullable|integer|min:0',
            'weak_keys' => 'nullable|array',
            'weak_keys.*' => 'string|max:8',
        ]);

        $user = Auth::user();
        $result = $this->curriculumService->submitAttempt($user, $stageId, $data);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get weak keys for targeted practice
     */
    public function weakKeys(Request $request): JsonResponse
    {
        $language = $request->query('language', 'th');
        $user = Auth::user();

        $weakKeys = $this->curriculumService->getWeakKeys($user, $language);

        return response()->json([
            'success' => true,
            'data' => [
                'language' => $language,
                'weak_keys' => $weakKeys,
            ],
        ]);
    }
}
