<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UsageEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserUsageEventController extends Controller
{
    /**
     * Store a new usage event from frontend.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'required|string|max:64',
            'source_type' => 'nullable|string|max:64',
            'source_id' => 'nullable|integer',
            'context' => 'nullable|array',
        ]);

        UsageEventService::fire(
            auth()->user(),
            $validated['event_type'],
            $validated['source_type'],
            $validated['source_id'],
            $validated['context'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Event recorded',
        ]);
    }
}
