<?php

namespace App\Http\Controllers\Api\Play\Typing\Admin;

use App\Http\Controllers\Controller;
use App\Models\TypingSentence;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminTypingSentenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sentences = TypingSentence::when($request->search, function($q, $search) {
                $q->where('text', 'like', "%{$search}%");
            })
            ->when($request->language, fn($q, $l) => $q->where('language', $l))
            ->when($request->difficulty, fn($q, $d) => $q->where('difficulty', $d))
            ->latest()
            ->paginate($request->get('limit', 15));

        return response()->json([
            'success' => true,
            'data' => $sentences
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'text' => 'required|string',
            'language' => 'required|in:en,th,ar',
            'difficulty' => 'required|in:beginner,easy,normal,hard,expert',
            'word_count' => 'required|integer|min:1',
            'source' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ]);

        $sentence = TypingSentence::create([
            ...$data,
            'created_by' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $sentence
        ]);
    }

    public function update(Request $request, TypingSentence $sentence): JsonResponse
    {
        $data = $request->validate([
            'text' => 'string',
            'language' => 'in:en,th,ar',
            'difficulty' => 'in:beginner,easy,normal,hard,expert',
            'word_count' => 'integer|min:1',
            'source' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ]);

        $sentence->update($data);

        return response()->json([
            'success' => true,
            'data' => $sentence
        ]);
    }

    public function destroy(TypingSentence $sentence): JsonResponse
    {
        $sentence->delete();
        return response()->json(['success' => true]);
    }
}
