<?php

namespace App\Http\Controllers\Api\Play\Typing;

use App\Http\Controllers\Controller;
use App\Models\TypingSentence;
use App\Models\TypingWord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypingWordController extends Controller
{
    /**
     * Get a list of words for typing
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'language' => 'nullable|in:en,th,ar',
            'difficulty' => 'nullable|in:beginner,easy,normal,hard,expert',
            'category' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        // Support fetching by specific IDs (for Race shared content)
        if ($request->has('ids')) {
            $ids = array_filter(array_map('intval', explode(',', $request->ids)));
            $words = TypingWord::whereIn('id', $ids)->get();

            return response()->json(['success' => true, 'data' => $words]);
        }

        $query = TypingWord::active();

        if ($request->has('language')) {
            $query->where('language', $request->language);
        }

        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $limit = $request->get('limit', 20);
        $words = $query->inRandomOrder()->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $words,
        ]);
    }

    /**
     * Get a list of sentences for typing
     */
    public function sentences(Request $request): JsonResponse
    {
        $request->validate([
            'language' => 'nullable|in:en,th,ar',
            'difficulty' => 'nullable|in:beginner,easy,normal,hard,expert',
            'limit' => 'nullable|integer|min:1|max:20',
            'ids' => 'nullable|string',
        ]);

        $query = TypingSentence::active();

        if ($request->has('ids')) {
            $ids = explode(',', $request->ids);
            $sentences = TypingSentence::whereIn('id', $ids)->get();

            return response()->json([
                'success' => true,
                'data' => $sentences,
            ]);
        }

        if ($request->has('language')) {
            $query->where('language', $request->language);
        }

        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        $limit = $request->get('limit', 5);
        $sentences = $query->inRandomOrder()->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $sentences,
        ]);
    }
}
