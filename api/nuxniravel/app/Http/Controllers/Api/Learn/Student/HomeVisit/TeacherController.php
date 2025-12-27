<?php

namespace App\Http\Controllers\Api\Learn\Student\HomeVisit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentHomeVisit;
use App\Models\HomeVisitImage;
use App\Models\HomeVisitZone;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    /**
     * Show teacher dashboard
     */
    public function dashboard()
    {
        if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'teacher') {
            return redirect()->route('homevisit.login');
        }

        // Get stats for dashboard
        $stats = [
            'total_students' => Student::count(),
            'total_visits' => StudentHomeVisit::count(),
            'visits_this_month' => StudentHomeVisit::whereMonth('visit_date', now()->month)
                ->whereYear('visit_date', now()->year)
                ->count(),
        ];

        // Get unique classrooms for filter
        $classrooms = Student::join('student_academic_info', 'students.id', '=', 'student_academic_info.student_id')
            ->where('student_academic_info.is_current', true)
            ->distinct()
            ->orderBy('student_academic_info.current_class')
            ->pluck('student_academic_info.current_class')
            ->filter()
            ->values(); // Convert to array

        return response()->json([
            'students' => (object)['data' => []], // Empty initial state
            'classrooms' => $classrooms,
            'zones' => HomeVisitZone::active()->ordered()->get(),
            'stats' => $stats,
            'filters' => [],
        ]);
    }

    /**
     * Search for students
     */
    public function searchStudents(Request $request)
    {
        if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'teacher') {
            return redirect()->route('homevisit.login');
        }

        $query = Student::with([
            'academicInfo', 
            'addresses',
            'contacts', 
            'guardians.contacts', 
            'healthInfo',
            'documents',
            'homeVisits' => function($q) {
                $q->orderBy('visit_date', 'desc')->with(['images', 'participants']);
            }
        ]);

        // Search functionality - student ID only
        if ($request->search) {
            $query->where('student_id', 'like', "%{$request->search}%");
        }

        // Filter by classroom through academic info
        if ($request->classroom) {
            $query->whereHas('academicInfo', function($q) use ($request) {
                $q->where('current_class', $request->classroom);
            });
        }

        $students = $query->orderBy('first_name_th')
            ->orderBy('last_name_th')
            ->paginate(15)
            ->withQueryString();

        // Get unique classrooms for filter
        $classrooms = Student::join('student_academic_info', 'students.id', '=', 'student_academic_info.student_id')
            ->where('student_academic_info.is_current', true)
            ->distinct()
            ->orderBy('student_academic_info.current_class')
            ->pluck('student_academic_info.current_class')
            ->filter()
            ->values(); // Convert to array

        // Get active zones for dropdown
        $zones = HomeVisitZone::active()->ordered()->get();

        // Get stats for dashboard
        $stats = [
            'total_students' => Student::count(),
            'total_visits' => StudentHomeVisit::count(),
            'visits_this_month' => StudentHomeVisit::whereMonth('visit_date', now()->month)
                ->whereYear('visit_date', now()->year)
                ->count(),
        ];

        return response()->json([
            'students' => $students,
            'classrooms' => $classrooms,
            'zones' => $zones,
            'stats' => $stats,
            'filters' => $request->only(['search', 'classroom']),
        ]);
    }

    /**
     * Show student management page
     */
    public function manageStudent($studentId)
    {
        if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'teacher') {
            return redirect()->route('homevisit.login');
        }

        $student = Student::with(['academicInfo', 'addresses', 'contacts', 'guardians.contacts', 'healthInfo'])
            ->findOrFail($studentId);
        
        // Get student's home visit records
        $homeVisits = StudentHomeVisit::where('student_id', $studentId)
            ->with(['zone', 'images', 'participants'])
            ->orderBy('visit_date', 'desc')
            ->get();

        // Add home visits to student object
        $student->home_visits = $homeVisits;

        // Get active zones for dropdown
        $zones = HomeVisitZone::active()->ordered()->get();

        return response()->json([
            'student' => $student,
            'zones' => $zones,
        ]);
    }

    /**
     * Create new home visit record
     */
    public function createHomeVisit(Request $request, $studentId)
    {
        if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'teacher') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $student = Student::with(['academicInfo', 'addresses', 'contacts'])->findOrFail($studentId);

        $validatedData = $request->validate([
            'visit_date' => 'required|date',
            'visit_purpose' => 'required|string|max:255',
            'visit_notes' => 'nullable|string',
            'teacher_name' => 'required|string|max:255',
        ]);

        try {
            // Create home visit record
            $homeVisit = StudentHomeVisit::create([
                'student_id' => $studentId,
                'visit_date' => $validatedData['visit_date'],
                'visit_purpose' => $validatedData['visit_purpose'],
                'visit_notes' => $validatedData['visit_notes'],
                'teacher_name' => $validatedData['teacher_name'],
                'visit_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'บันทึกข้อมูลการเยี่ยมบ้านสำเร็จแล้ว');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Update existing home visit record
     */
    public function updateHomeVisit(Request $request, $homeVisitId)
    {
        // if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'teacher') {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }

        $homeVisit = StudentHomeVisit::findOrFail($homeVisitId);
        
        // Similar validation and update logic as createHomeVisit
        $validatedData = $request->validate([
            'recommendations' => 'nullable|string',
            'follow_up' => 'nullable|string',
            'next_visit' => 'nullable|date',
            // Add other fields as needed
        ]);

        try {
            $homeVisit->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'อัปเดตข้อมูลการเยี่ยมบ้านสำเร็จแล้ว',
                'homeVisit' => $homeVisit
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete home visit record
     */
    public function deleteHomeVisit($homeVisitId)
    {
        // if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'teacher') {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }

        $homeVisit = StudentHomeVisit::findOrFail($homeVisitId);
        
        try {
            // Delete associated photos
            if ($homeVisit->photos) {
                $photos = json_decode($homeVisit->photos, true);
                foreach ($photos as $photoPath) {
                    Storage::disk('public')->delete($photoPath);
                }
            }

            $homeVisit->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบข้อมูลการเยี่ยมบ้านสำเร็จแล้ว'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update student information
     */
    public function updateStudent(Request $request, $studentId)
    {
        // if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'teacher') {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }

        $student = Student::findOrFail($studentId);
        
        try {
            // Update student basic info
            $student->update($request->only([
                'title_prefix_th',
                'first_name_th', 
                'last_name_th',
                'nickname',
                'citizen_id',
                'class_level',
                'class_section'
            ]));

            // Update academic info if provided
            if ($request->academic_info) {
                $student->academicInfo()->updateOrCreate(
                    ['student_id' => $student->id],
                    $request->academic_info
                );
            }

            // Update contacts if provided
            if ($request->contacts) {
                // Simple approach: delete and recreate contacts
                $student->contacts()->delete();
                foreach ($request->contacts as $contact) {
                    if (!empty($contact['contact_value'])) {
                        $student->contacts()->create($contact);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทข้อมูลนักเรียนสำเร็จแล้ว'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัพเดทข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new home visit with images
     */
    public function createHomeVisitWithImages(Request $request, $studentId)
    {
        // if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'teacher') {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }

        $request->validate([
            'visit_date' => 'required|date',
            'visit_time' => 'nullable',
            'zone_id' => 'nullable|exists:home_visit_zones,id',
            'observations' => 'nullable|string',
            'notes' => 'nullable|string',
            'participants' => 'nullable|array',
            'participants.*.name' => 'required|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480'
        ]);

        $student = Student::findOrFail($studentId);
        
        try {
            // Create home visit record using the smart method
            $homeVisit = StudentHomeVisit::createFromStudent($student, [
                'visit_date' => $request->visit_date,
                'visit_time' => $request->visit_time,
                'zone_id' => $request->zone_id,
                'observations' => $request->observations,
                'notes' => $request->notes,
                'visitor_name' => session('homevisit_user_name', 'ครู'), // Keep for backward compatibility
                'created_by' => session('homevisit_user_id'),
            ]);

            // Add participants
            if ($request->has('participants') && is_array($request->participants)) {
                foreach ($request->participants as $participantData) {
                    \App\Models\HomeVisitParticipant::create([
                        'home_visit_id' => $homeVisit->id,
                        'participant_name' => $participantData['name'],
                    ]);
                }
            } else {
                // Fallback: Add current user if no participants provided
                \App\Models\HomeVisitParticipant::create([
                    'home_visit_id' => $homeVisit->id,
                    'participant_name' => session('homevisit_user_name', 'ครู'),
                ]);
            }

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $imageName = time() . '_' . $index . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('home_visit_images/' . $homeVisit->id, $imageName, 'public');
                    
                    // Save using HomeVisitImage model
                    HomeVisitImage::create([
                        'home_visit_id' => $homeVisit->id,
                        'image_path' => $imagePath,
                        'image_name' => $imageName,
                        'image_type' => 'evidence',
                        'description' => 'รูปภาพหลักฐานการเยี่ยมบ้าน',
                        'uploaded_by' => session('homevisit_user_id', 1)
                    ]);
                }
            }

            return redirect()->back()->with('success', 'บันทึกการเยี่ยมบ้านสำเร็จแล้ว');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage());
        }
    }

    /**
     * Update existing home visit record with full form data
     */
    public function updateHomeVisitWithImages(Request $request, $homeVisitId)
    {
        // if (!session('homevisit_authenticated') || session('homevisit_user_type') !== 'teacher') {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }

        $request->validate([
            'visit_date' => 'required|date',
            'visit_time' => 'nullable',
            'zone_id' => 'nullable|exists:home_visit_zones,id',
            'observations' => 'nullable|string',
            'notes' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'follow_up' => 'nullable|string',
            'next_visit' => 'nullable|date',
            'participants' => 'required|array|min:1',
            'participants.*.participant_name' => 'required|string',
            'new_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
            'images_to_delete' => 'nullable|array'
        ]);

        $homeVisit = StudentHomeVisit::findOrFail($homeVisitId);
        
        try {
            DB::beginTransaction();

            // Update basic visit information
            $homeVisit->update([
                'visit_date' => $request->visit_date,
                'visit_time' => $request->visit_time,
                'zone_id' => $request->zone_id,
                'observations' => $request->observations,
                'notes' => $request->notes,
                'recommendations' => $request->recommendations,
                'follow_up' => $request->follow_up,
                'next_visit' => $request->next_visit,
            ]);

            // Update participants
            if ($request->has('participants')) {
                // Delete existing participants
                $homeVisit->participants()->delete();
                
                // Add new participants
                foreach ($request->participants as $participantData) {
                    \App\Models\HomeVisitParticipant::create([
                        'home_visit_id' => $homeVisit->id,
                        'participant_name' => $participantData['participant_name'],
                    ]);
                }
            }

            // Handle image deletions
            if ($request->has('images_to_delete') && is_array($request->images_to_delete)) {
                foreach ($request->images_to_delete as $imageId) {
                    $image = \App\Models\HomeVisitImage::find($imageId);
                    if ($image && $image->home_visit_id == $homeVisit->id) {
                        // Delete file from storage
                        if ($image->image_path) {
                            Storage::disk('public')->delete($image->image_path);
                        }
                        // Delete from database
                        $image->delete();
                    }
                }
            }

            // Handle new image uploads
            if ($request->hasFile('new_images')) {
                foreach ($request->file('new_images') as $index => $image) {
                    $imageName = time() . '_' . $index . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('home_visit_images/' . $homeVisit->id, $imageName, 'public');
                    
                    // Save using HomeVisitImage model
                    \App\Models\HomeVisitImage::create([
                        'home_visit_id' => $homeVisit->id,
                        'image_path' => $imagePath,
                        'image_name' => $imageName,
                        'image_type' => 'evidence',
                        'description' => 'รูปภาพหลักฐานการเยี่ยมบ้าน',
                        'uploaded_by' => session('homevisit_user_id', 1)
                    ]);
                }
            }

            DB::commit();

            // Load updated relationships
            $homeVisit->load(['participants', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'อัปเดตข้อมูลการเยี่ยมบ้านเรียบร้อยแล้ว',
                'visit' => $homeVisit
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }
}
