<?php

namespace App\Http\Controllers\Api\Learn\Student\Card;

use Carbon\Carbon;
use App\Models\Academy;
use App\Models\StudentCard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class StudentCardController extends Controller
{
    /**
     * Get academy-scoped query builder
     */
    private function academyQuery($academy)
    {
        $academyId = $academy instanceof Academy ? $academy->id : $academy;
        return StudentCard::where('academy_id', $academyId);
    }

    /**
     * Main index page
     */
    public function index($academy) 
    {
        return response()->json(['success' => true]);
    }

    /**
     * Dashboard - show overview
     */
    public function dashboard($academy) 
    {
        $query = $this->academyQuery($academy);
        $totalStudents = $query->count();
        $levels = $this->academyQuery($academy)->distinct()->pluck('class_level')->sort()->values();
        
        return response()->json([
            'success' => true,
            'totalStudents' => $totalStudents,
            'levels' => $levels
        ]);
    }

    /**
     * Get statistics for student cards - for academy admin dashboard
     */
    public function statistics($academy)
    {
        $query = $this->academyQuery($academy);
        $totalStudents = $query->count();
        $withPhoto = $this->academyQuery($academy)
            ->whereNotNull('profile_image')
            ->where('profile_image', '!=', '')
            ->count();
        $withoutPhoto = $totalStudents - $withPhoto;
        
        $byLevel = $this->academyQuery($academy)
            ->selectRaw('class_level, COUNT(*) as count')
            ->groupBy('class_level')
            ->pluck('count', 'class_level')
            ->toArray();

        // Sections per level
        $sectionsByLevel = $this->academyQuery($academy)
            ->selectRaw('class_level, class_section')
            ->distinct()
            ->orderBy('class_level')
            ->orderBy('class_section')
            ->get()
            ->groupBy('class_level')
            ->map(fn($items) => $items->pluck('class_section')->sort()->values())
            ->toArray();
        
        return response()->json([
            'success' => true,
            'statistics' => [
                'totalStudents' => $totalStudents,
                'withPhoto' => $withPhoto,
                'withoutPhoto' => $withoutPhoto,
                'byLevel' => $byLevel,
                'sectionsByLevel' => $sectionsByLevel,
            ]
        ]);
    }

    /**
     * Get all class levels
     */
    public function getLevels($academy)
    {
        $levels = $this->academyQuery($academy)->distinct()->pluck('class_level')->sort()->values();
        
        return response()->json([
            'success' => true,
            'levels' => $levels
        ]);
    }

    /**
     * Get all sections/rooms
     */
    public function getSections($academy)
    {
        $sections = $this->academyQuery($academy)->distinct()->pluck('class_section')->sort()->values();
        
        return response()->json([
            'success' => true,
            'sections' => $sections
        ]);
    }

    /**
     * Search functionality
     */
    public function search($academy, Request $request)
    {
        $query = $this->academyQuery($academy);
        
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name_thai', 'like', '%' . $search . '%')
                  ->orWhere('last_name_thai', 'like', '%' . $search . '%')
                  ->orWhere('student_number', 'like', '%' . $search . '%')
                  ->orWhere('national_id', 'like', '%' . $search . '%');
            });
        }
        
        if ($request->level) {
            $query->where('class_level', $request->level);
        }
        
        if ($request->section) {
            $query->where('class_section', $request->section);
        }
        
        $students = $query->orderBy('class_level')
            ->orderBy('class_section')
            ->orderBy('order_no')
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'students' => $students,
            'filters' => $request->only(['search', 'level', 'section'])
        ]);
    }

    /**
     * Student profile view
     */
    public function profile($academy, StudentCard $student_card)
    {
        return response()->json([
            'success' => true,
            'student' => $student_card
        ]);
    }
 
    /**
     * Admin main page
     */
    public function adminIndex($academy) 
    {
        return response()->json(['success' => true]);
    }

    /**
     * Admin student management with search/filter
     */
    public function adminStudents($academy, Request $request)
    {
        $query = $this->academyQuery($academy);
        
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name_thai', 'like', '%' . $search . '%')
                  ->orWhere('last_name_thai', 'like', '%' . $search . '%')
                  ->orWhere('student_number', 'like', '%' . $search . '%')
                  ->orWhere('national_id', 'like', '%' . $search . '%');
            });
        }
        
        if ($request->level) {
            $query->where('class_level', $request->level);
        }

        if ($request->section) {
            $query->where('class_section', $request->section);
        }
        
        $students = $query->orderBy('class_level')
            ->orderBy('class_section')
            ->orderBy('order_no')
            ->orderBy('first_name_thai')
            ->paginate(50);
        
        return response()->json([
            'success' => true,
            'students' => $students,
            'filters' => $request->only(['search', 'level', 'section'])
        ]);
    }

    /**
     * Get students by level and room
     */
    public function getStudentByRoom($academy, $level, $room)
    {
        $students = $this->academyQuery($academy)
            ->where('class_level', $level)
            ->where('class_section', $room)
            ->orderBy('order_no')
            ->orderBy('first_name_thai')
            ->get();

        return response()->json([
            'success' => true,
            'students' => $students,
            'level' => $level,
            'room' => $room,
        ]);
    }

    /**
     * Admin: Get students by level and room
     */
    public function adminGetStudentByRoom($academy, $level, $room)
    {
        $students = $this->academyQuery($academy)
            ->where('class_level', $level)
            ->where('class_section', $room)
            ->orderBy('order_no')
            ->orderBy('first_name_thai')
            ->get();

        return response()->json([
            'success' => true,
            'students' => $students,
            'level' => $level,
            'room' => $room,
        ]);
    }

    /**
     * Upload/update student photo
     */
    public function updateImage($academy, StudentCard $student_card, Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        try {
            if ($request->hasFile('photo')) {
                // Delete old image if exists
                if ($student_card->profile_image) {
                    Storage::disk('public')->delete(
                        'images/students/'.$student_card->class_level.'/'.$student_card->class_section.'/'.$student_card->profile_image
                    );
                }

                $fileName = $student_card->student_number . '.' . $request->file('photo')->getClientOriginalExtension();
                
                $path = Storage::disk('public')->putFileAs(
                    'images/students/'.$student_card->class_level.'/'.$student_card->class_section, 
                    $request->file('photo'), 
                    $fileName
                );

                $student_card->update([
                    'profile_image' => $fileName
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'อัพโหลดรูปภาพสำเร็จ',
                    'photo' => $fileName,
                    'path' => Storage::url($path)
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'ไม่พบไฟล์รูปภาพ'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัพโหลดรูปภาพได้',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update student number/code
     */
    public function updateStudentID($academy, StudentCard $student_card, Request $request)
    {
        $request->validate([
            'student_number' => 'required|string|max:255',
        ]);

        try {
            $student_card->update([
                'student_number' => $request->input('student_number'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทรหัสนักเรียนสำเร็จ'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัพเดทรหัสนักเรียนได้',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update student Thai name
     */
    public function updateStudentNameTh($academy, StudentCard $student_card, Request $request)
    {
        $request->validate([
            'title_name' => 'nullable|string|max:50',
            'first_name_thai' => 'nullable|string|max:255',
            'last_name_thai' => 'nullable|string|max:255',
        ]);

        try {
            $titleName = $request->input('title_name', $student_card->title_name);
            $firstName = $request->input('first_name_thai', $student_card->first_name_thai);
            $lastName = $request->input('last_name_thai', $student_card->last_name_thai);

            $student_card->update([
                'title_name' => $titleName,
                'first_name_thai' => $firstName,
                'last_name_thai' => $lastName,
                'full_name_thai' => trim($titleName . ' ' . $firstName . ' ' . $lastName),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทชื่อสำเร็จ'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัพเดทชื่อได้',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update student English name
     */
    public function updateStudentNameEn($academy, StudentCard $student_card, Request $request)
    {
        $request->validate([
            'first_name_english' => 'nullable|string|max:255',
        ]);

        try {
            $student_card->update([
                'first_name_english' => $request->input('first_name_english'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทชื่อภาษาอังกฤษสำเร็จ'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัพเดทชื่อภาษาอังกฤษได้',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update full student card info
     */
    public function update($academy, StudentCard $student_card, Request $request)
    {
        $request->validate([
            'student_number' => 'nullable|string|max:255',
            'title_name' => 'nullable|string|max:50',
            'first_name_thai' => 'nullable|string|max:255',
            'last_name_thai' => 'nullable|string|max:255',
            'first_name_english' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'class_level' => 'nullable|string|max:10',
            'class_section' => 'nullable|string|max:10',
            'card_issue_date' => 'nullable|date',
            'card_expiry_date' => 'nullable|date',
        ]);
 
        try {
            $data = $request->only([
                'student_number', 'title_name', 'first_name_thai', 'last_name_thai',
                'first_name_english', 'national_id', 'birth_date', 'class_level',
                'class_section', 'card_issue_date', 'card_expiry_date',
            ]);

            // Auto-generate full_name_thai
            $title = $data['title_name'] ?? $student_card->title_name;
            $first = $data['first_name_thai'] ?? $student_card->first_name_thai;
            $last = $data['last_name_thai'] ?? $student_card->last_name_thai;
            $data['full_name_thai'] = trim($title . ' ' . $first . ' ' . $last);

            // Auto-generate birth_date_string
            if (!empty($data['birth_date'])) {
                $data['birth_date_string'] = $this->convertDateToThaiFormat($data['birth_date']);
            }

            $student_card->update($data);
            
            return response()->json([
                'success' => true,
                'message' => 'บันทึกข้อมูลสำเร็จ',
                'student' => $student_card->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new student card
     */
    public function store($academy, Request $request)
    {
        $request->validate([
            'student_number' => 'required|string|max:255',
            'first_name_thai' => 'required|string|max:255',
            'last_name_thai' => 'required|string|max:255',
            'class_level' => 'required|string|max:10',
            'class_section' => 'required|string|max:10',
            'title_name' => 'nullable|string|max:50',
            'first_name_english' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
        ]);

        $academyId = $academy instanceof Academy ? $academy->id : $academy;

        try {
            $data = $request->only([
                'student_number', 'title_name', 'first_name_thai', 'last_name_thai',
                'first_name_english', 'national_id', 'birth_date', 'class_level', 'class_section',
            ]);
            
            $data['academy_id'] = $academyId;
            $data['full_name_thai'] = trim(
                ($data['title_name'] ?? '') . ' ' . $data['first_name_thai'] . ' ' . $data['last_name_thai']
            );
            $data['student_status'] = 'active';

            if (!empty($data['birth_date'])) {
                $data['birth_date_string'] = $this->convertDateToThaiFormat($data['birth_date']);
            }

            $studentCard = StudentCard::create($data);

            return response()->json([
                'success' => true,
                'message' => 'สร้างบัตรนักเรียนสำเร็จ',
                'student' => $studentCard,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถสร้างบัตรนักเรียนได้',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function convertDateToThaiFormat($date)
    {
        if (!$date) {
            return null;
        }

        try {
            $carbonDate = Carbon::parse($date);
            return $carbonDate->format('d/m/Y');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Delete student photo
     */
    public function destroyPhoto($academy, StudentCard $student_card)
    {
        try {
            if ($student_card->profile_image) {
                Storage::disk('public')->delete(
                    'images/students/'.$student_card->class_level.'/'.$student_card->class_section.'/'.$student_card->profile_image
                );

                $student_card->update(['profile_image' => null]);

                return response()->json([
                    'success' => true,
                    'message' => 'ลบรูปภาพสำเร็จ'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรูปภาพ'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบรูปภาพได้',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import student cards from CSV/Excel
     */
    public function import($academy, Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'ฟีเจอร์นำเข้าข้อมูลยังอยู่ระหว่างการพัฒนา'
        ], 501);
    }

    /**
     * Export student cards to CSV/Excel
     */
    public function export($academy, Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'ฟีเจอร์ส่งออกข้อมูลยังอยู่ระหว่างการพัฒนา'
        ], 501);
    }

    /**
     * Bulk upload photos
     */
    public function bulkUploadPhotos($academy, Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'ฟีเจอร์อัพโหลดรูปภาพแบบกลุ่มยังอยู่ระหว่างการพัฒนา'
        ], 501);
    }

    /**
     * Bulk update student cards
     */
    public function bulkUpdate($academy, Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'ฟีเจอร์อัพเดทข้อมูลแบบกลุ่มยังอยู่ระหว่างการพัฒนา'
        ], 501);
    }
}
