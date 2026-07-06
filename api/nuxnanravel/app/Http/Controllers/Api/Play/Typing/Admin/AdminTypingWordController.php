<?php

namespace App\Http\Controllers\Api\Play\Typing\Admin;

use App\Http\Controllers\Controller;
use App\Models\TypingWord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminTypingWordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $words = TypingWord::when($request->search, function ($q, $search) {
            $q->where('text', 'like', "%{$search}%");
        })
            ->when($request->language, fn ($q, $l) => $q->where('language', $l))
            ->when($request->difficulty, fn ($q, $d) => $q->where('difficulty', $d))
            ->latest()
            ->paginate($request->get('limit', 20));

        return response()->json([
            'success' => true,
            'data' => $words,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'text' => 'required|string|max:255',
            'language' => 'required|in:en,th,ar',
            'difficulty' => 'required|in:beginner,easy,normal,hard,expert',
            'category' => 'nullable|string|max:50',
            'pronunciation' => 'nullable|string|max:255',
            'meaning' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $word = TypingWord::create([
            ...$data,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $word,
        ]);
    }

    public function update(Request $request, TypingWord $word): JsonResponse
    {
        $data = $request->validate([
            'text' => 'string|max:255',
            'language' => 'in:en,th,ar',
            'difficulty' => 'in:beginner,easy,normal,hard,expert',
            'category' => 'nullable|string|max:50',
            'pronunciation' => 'nullable|string|max:255',
            'meaning' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $word->update($data);

        return response()->json([
            'success' => true,
            'data' => $word,
        ]);
    }

    public function destroy(TypingWord $word): JsonResponse
    {
        $word->delete();

        return response()->json(['success' => true]);
    }

    public function import(Request $request): JsonResponse
    {
        // Simple import from JSON/CSV could be here
        return response()->json(['success' => true, 'message' => 'Import functionality coming soon.']);
    }
}
