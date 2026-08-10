<?php

namespace App\Http\Controllers\Api\Learn\Course\lessons;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\lessons\LessonAttachmentResource;
use App\Models\CourseMember;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Topic;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class LessonAttachmentController extends Controller
{
    private function resolveAttachable(Request $request): array
    {
        $lesson = $request->route('lesson');
        if ($lesson) {
            $lesson = $lesson instanceof Lesson ? $lesson : Lesson::findOrFail($lesson);

            return [$lesson, $lesson->course];
        }

        $topic = $request->route('topic');
        if ($topic) {
            $topic = $topic instanceof Topic ? $topic : Topic::findOrFail($topic);

            return [$topic, $topic->course];
        }

        abort(404, 'ไม่พบบทเรียนหรือหัวข้อที่ระบุ');
    }

    private function canReadAccess($user, $course)
    {
        if ($course->isAdmin($user)) {
            return true;
        }

        $isMember = CourseMember::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('status', 1) // status = 1 = approved/active member; 0 = pending approval or unpaid
            ->exists();

        return $isMember;
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            [$attachable, $course] = $this->resolveAttachable($request);

            if (! $this->canReadAccess($user, $course)) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์เข้าถึงเนื้อหานี้',
                ], 403);
            }

            $attachments = $attachable->attachments;

            return LessonAttachmentResource::collection($attachments);

        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโหลดไฟล์แนบ',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            [$attachable, $course] = $this->resolveAttachable($request);

            if (! $course->isAdmin($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์อัปโหลดไฟล์ในรายวิชานี้',
                ], 403);
            }

            $validated = $request->validate([
                'files' => 'required|array|max:5',
                'files.*' => 'required|file|max:20480|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,csv,zip',
            ]);

            $maxOrder = $attachable->attachments()->max('order') ?? 0;
            $newAttachments = collect();

            foreach ($validated['files'] as $file) {
                $maxOrder++;
                $extension = $file->getClientOriginalExtension();
                $filename = (string) Str::uuid().'.'.$extension;

                Storage::disk('local')->putFileAs('course-materials/lessons', $file, $filename);

                $attachment = $attachable->attachments()->create([
                    'course_id' => $course->id,
                    'uploaded_by' => $user->id,
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'extension' => $extension,
                    'size' => $file->getSize(),
                    'order' => $maxOrder,
                ]);

                $newAttachments->push($attachment);
            }

            return response()->json([
                'success' => true,
                'message' => 'อัปโหลดไฟล์สำเร็จ',
                'attachments' => LessonAttachmentResource::collection($newAttachments),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error uploading attachment: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, $attachment)
    {
        $attachment = $attachment instanceof LessonAttachment
            ? $attachment
            : LessonAttachment::findOrFail($attachment);

        try {
            $user = auth()->user();
            [$attachable, $course] = $this->resolveAttachable($request);

            if (! $course->isAdmin($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ลบไฟล์ในรายวิชานี้',
                ], 403);
            }

            if ($attachment->attachable_id !== $attachable->id || $attachment->attachable_type !== get_class($attachable)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไฟล์แนบนี้ไม่ได้อยู่ในบทเรียน/หัวข้อที่ระบุ',
                ], 404);
            }

            Storage::disk('local')->delete('course-materials/lessons/'.$attachment->filename);
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบไฟล์แนบสำเร็จ',
            ], 200);

        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบไฟล์',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function download(Request $request, $attachment)
    {
        $attachment = $attachment instanceof LessonAttachment
            ? $attachment
            : LessonAttachment::findOrFail($attachment);

        try {
            $user = auth()->user();
            [$attachable, $course] = $this->resolveAttachable($request);

            if (! $this->canReadAccess($user, $course)) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ดาวน์โหลดไฟล์นี้',
                ], 403);
            }

            if ($attachment->attachable_id !== $attachable->id || $attachment->attachable_type !== get_class($attachable)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไฟล์แนบนี้ไม่ได้อยู่ในบทเรียน/หัวข้อที่ระบุ',
                ], 404);
            }

            $path = 'course-materials/lessons/'.$attachment->filename;

            if (! Storage::disk('local')->exists($path)) {
                // Return 404 directly? Spec says: "on failure return 403 with a Thai message, never 404-leak the filename"
                // But if the row exists but file is missing? We'll return 403 with Thai message to be safe as per spec.
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบไฟล์ หรือคุณไม่มีสิทธิ์ดาวน์โหลดไฟล์นี้',
                ], 403);
            }

            $attachment->increment('download_count');

            return Storage::disk('local')->download($path, $attachment->original_name);

        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดาวน์โหลดไฟล์',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
