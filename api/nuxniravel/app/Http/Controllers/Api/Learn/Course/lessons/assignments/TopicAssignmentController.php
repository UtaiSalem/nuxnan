<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons\assignments;

use App\Models\Topic;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\AssignmentResource;

class TopicAssignmentController extends \App\Http\Controllers\Controller
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
     */
    public function store(Topic $topic, Request $request)
    {
        $assignment = $topic->assignments()->create([
            'title' => $request->title,
            'points' => $request->points,
        ]);

        if($request->hasFile('images')) {
            $images = $request->file('images');
            $fileNames = [];
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/topics/assignments', $image, $fileName);
                $fileNames[] = $fileName;

                $assignment->images()->create([
                    'image_url' => $image_url
                ]);
            }
        }
        return response()->json([
            'assignment' => new AssignmentResource($assignment),
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Topic $topic, Assignment $assignment, Request $request)
    {
        $assignment->update([
            'title' => $request->title,
            'points' => $request->points,
        ]);

        if($request->hasFile('images')) {
            $images = $request->file('images');
            $fileNames = [];
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/topics/assignments', $image, $fileName);
                $fileNames[] = $fileName;

                $assignment->images()->create([
                    'image_url' => $image_url
                ]);
            }
        }
        return response()->json([
            'assignment' => new AssignmentResource($assignment),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Topic $topic, Assignment $assignment)
    {
        foreach ($assignment->images as $image) {
            Storage::disk('public')->delete($image->image_url);
        }
        $assignment->images()->delete();
        $assignment->delete();

        return response()->json(['success' => true], 200);
    }
}
