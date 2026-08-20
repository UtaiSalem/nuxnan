<?php

namespace App\Http\Controllers\Api\Learn\Course\scores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learn\Course\ImportExternalScoresRequest;
use App\Models\Course;
use App\Models\CourseExternalScore;
use App\Services\Import\ExternalScoreImportService;

class CourseExternalScoreImportController extends Controller
{
    protected ExternalScoreImportService $service;

    public function __construct(ExternalScoreImportService $service)
    {
        $this->service = $service;
    }

    public function template(Course $course, CourseExternalScore $externalScore, $groupId = null)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        if ($externalScore->course_id !== $course->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $path = $this->service->buildTemplate($course, $externalScore, $groupId ? (int) $groupId : null);
        $filename = 'external-scores-'.$externalScore->id.'-'.($groupId ?: 'all').'.xlsx';

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function preview(ImportExternalScoresRequest $request, Course $course, CourseExternalScore $externalScore)
    {
        if (! $course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        if ($externalScore->course_id !== $course->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $groupId = $request->input('group_id') ? (int) $request->input('group_id') : null;

        try {
            $parsed = $this->service->parse($request->file('file'));
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if ($parsed['meta']['external_score_id'] != $externalScore->id) {
            return response()->json(['success' => false, 'message' => 'ไฟล์นี้เป็นแบบฟอร์มของหัวข้อคะแนนอื่น กรุณาดาวน์โหลดแบบฟอร์มของหัวข้อนี้ใหม่'], 422);
        }
        if ($parsed['meta']['course_id'] != $course->id) {
            return response()->json(['success' => false, 'message' => 'ไฟล์นี้เป็นแบบฟอร์มของรายวิชาอื่น'], 422);
        }

        if (count($parsed['rows']) > 500) {
            return response()->json(['success' => false, 'message' => 'อัปโหลดได้สูงสุด 500 คนต่อครั้ง'], 422);
        }

        $rows = $this->service->validateRows($course, $externalScore, $groupId, $parsed['rows']);

        $total = count($rows);
        $set = 0;
        $clear = 0;
        $skip = 0;
        $invalid = 0;

        foreach ($rows as $row) {
            if (count($row['errors']) > 0) {
                $invalid++;
            } else {
                if ($row['action'] === 'set') {
                    $set++;
                } elseif ($row['action'] === 'clear') {
                    $clear++;
                } elseif ($row['action'] === 'skip') {
                    $skip++;
                }
            }
        }

        // Calculate missing
        $validMembersQuery = $course->courseMembers()->where('role', '!=', 4);
        if ($groupId) {
            $validMembersQuery->where('group_id', $groupId);
        }
        $validMemberIds = $validMembersQuery->pluck('id')->toArray();
        $importedMemberIds = array_filter(array_column($rows, 'course_member_id'));
        $missing = count(array_diff($validMemberIds, $importedMemberIds));

        return response()->json([
            'success' => true,
            'external_score' => [
                'id' => $externalScore->id,
                'title' => $externalScore->title,
                'max_score' => (float) $externalScore->max_score,
            ],
            'summary' => [
                'total' => $total,
                'set' => $set,
                'clear' => $clear,
                'skip' => $skip,
                'invalid' => $invalid,
                'missing' => $missing,
            ],
            'rows' => $rows,
        ]);
    }
}
