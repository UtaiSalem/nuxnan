<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Academy\AcademyResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\User;
use App\Services\AcademySettingsAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;

class AcademyController extends Controller
{
    // Middleware is now handled in route definitions (routes/learn/academy.php)

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(['success' => true]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['success' => true]);
    }

    public function create_course(Academy $academy)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();

        return response()->json([
            'academy' => new AcademyResource($academy),
            'courses' => CourseResource::collection($academy->courses()->paginate()),
            'isAcademyAdmin' => $isAcademyAdmin,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        if (auth()->user()->pp < 1000000) {
            return response()->json([
                'success' => false,
                'message' => 'แต้มสะสมไม่เพียงพอ, กรุณาเพิ่มแต้มสะสม',
            ], 200);
        }

        $validated = $request->validate([
            // SET-S8 — กันชื่อซ้ำตั้งแต่ตอนสร้าง ไม่งั้น UNIQUE index จะโยน QueryException ออกไปเป็น 500
            'name' => 'required|string|unique:academies,name',
            'slogan' => 'required|string',
            'address' => 'required|string',
            'autoAcceptMember' => 'required|string',
            'membershipFees' => 'required|integer',
            'logo' => 'image|mimes:jpg,jpeg,png,gif',
            'cover' => 'image|mimes:jpg,jpeg,png,gif',
        ]);

        // return $validated['autoAcceptMember'] === true ? true: false;

        try {
            $authUser = auth()->user();

            if ($request->hasFile('logo')) {
                // $logo = $request->file('logo');
                $logo = $validated['logo'];
                $logo_name = uniqid().'.'.$logo->getClientOriginalExtension();

                // $logo_image = Image::make($logo->getRealPath());
                // $logo_image->resize(300, 300, function ($constraint) {
                //     $constraint->aspectRatio();
                // });

                $logo_url = Storage::disk('public')->putFileAs('images/academies/logos', $logo, $logo_name);
            }

            if ($request->hasFile('cover')) {
                $cover = $validated['cover'];
                $cover_name = uniqid().'.'.$cover->getClientOriginalExtension();

                $cover_url = Storage::disk('public')->putFileAs('images/academies/covers', $cover, $cover_name);
            }

            $academy = new Academy;
            $academy->user_id = auth()->id();
            $academy->name = $validated['name'];
            $academy->slogan = $validated['slogan'];
            $academy->address = $validated['address'];
            $academy->membership_fees_points = $validated['membershipFees'];
            $academy->director = auth()->id();
            $academy->logo = $logo_name ?? null;
            $academy->cover = $cover_name ?? null;

            $academy->save();

            $academy->academySetting()->create([
                'join_mode' => $validated['autoAcceptMember'] === 'true' ? 'open' : 'approval',
            ]);

            auth()->user()->decrement('pp', 860000);

            return response()->json([
                'success' => true,
                'academy' => $academy->id,
            ], 200);

        } catch (\Throwable $th) {
            return throw $th;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Academy $academy)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();

        // Get user's membership and role in this academy
        $membership = null;
        $myRole = null;

        if (auth()->check()) {
            $membership = AcademyMember::where('academy_id', $academy->id)
                ->where('user_id', auth()->id())
                ->with('academyRole')
                ->first();

            if ($membership && $membership->academyRole) {
                $myRole = [
                    'id' => $membership->academyRole->id,
                    'name' => $membership->academyRole->name,
                    'display_name' => $membership->academyRole->display_name_th,
                    'display_name_en' => $membership->academyRole->display_name_en,
                    'permissions' => $membership->academyRole->permissions,
                    'color' => $membership->academyRole->color,
                    'icon' => $membership->academyRole->icon,
                    'is_system' => $membership->academyRole->is_system,
                ];
            } elseif ($isAcademyAdmin) {
                // Academy owner automatically has owner role
                $myRole = [
                    'name' => 'owner',
                    'display_name' => 'เจ้าของสถาบัน',
                    'display_name_en' => 'Owner',
                    'permissions' => ['*'], // Full access
                    'color' => 'bg-purple-100 text-purple-700',
                    'icon' => 'fluent:crown-24-filled',
                    'is_system' => true,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'academy' => new AcademyResource($academy),
            'isAcademyAdmin' => $isAcademyAdmin,
            'membership' => $membership ? [
                'id' => $membership->id,
                'status' => $membership->status,
                'role' => $membership->role,
                'joined_at' => $membership->created_at,
            ] : null,
            'myRole' => $myRole,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Academy $academy)
    {
        //
    }

    public function joinAcademy(Academy $academy)
    {
        $academy->members()->attach(auth()->id());

        return redirect()->back();
    }

    public function leaveAcademy(Academy $academy)
    {
        $academy->members()->detach(auth()->id());

        return redirect()->back();
    }

    public function acceptMember(Academy $academy, $memberId)
    {
        $academy->members()->updateExistingPivot($memberId, ['status' => 'accepted']);

        return redirect()->back();
    }

    public function rejectMember(Academy $academy, $memberId)
    {
        $academy->members()->updateExistingPivot($memberId, ['status' => 'rejected']);

        return redirect()->back();
    }

    public function removeMember(Academy $academy, $memberId)
    {
        $academy->members()->detach($memberId);

        return redirect()->back();
    }

    public function updateMembershipFees(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'membershipFees' => 'required|integer',
        ]);

        $academy->update([
            'membership_fees_points' => $validated['membershipFees'],
        ]);

        return redirect()->back();
    }

    public function updateAcademyLogo(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,gif',
        ]);

        if ($academy->logo && ($academy->logo !== 'default_logo.png')) {
            Storage::disk('public')->delete($academy->logo);
        }

        $logo = $validated['logo'];

        $logo_name = uniqid().'.'.$logo->getClientOriginalExtension();
        $logo_path = Storage::disk('public')->putFileAs('images/academies/logos', $logo, $logo_name);
        $academy->logo = $logo_name;

        $academy->update();

        return redirect()->back();
    }

    public function updateAcademyCover(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'cover' => 'required|image|mimes:jpg,jpeg,png,gif',
        ]);

        if ($academy->cover && ($academy->cover !== 'default_cover.png')) {
            Storage::disk('public')->delete($academy->cover);
        }

        $cover = $validated['cover'];

        $cover_name = uniqid().'.'.$cover->getClientOriginalExtension();
        $cover_path = Storage::disk('public')->putFileAs('images/academies/covers', $cover, $cover_name);
        $academy->cover = $cover_name;

        $academy->update();

        return redirect()->back();
    }

    public function searchAcademies(Request $request)
    {
        // SET-S2 — ซ่อนโรงเรียนที่ถูกเก็บถาวรออกจากรายการ
        $academies = Academy::notArchived()->where('name', 'like', '%'.$request->search.'%')->get();

        return response()->json([
            'academies' => AcademyResource::collection($academies),
        ], 200);
    }

    public function searchAcademiesMembers(Academy $academy, Request $request)
    {
        $members = $academy->members()->where('name', 'like', '%'.$request->search.'%')->get();

        return response()->json([
            'members' => $members,
        ], 200);
    }

    public function searchAcademiesCourses(Academy $academy, Request $request)
    {
        $courses = $academy->courses()->where('name', 'like', '%'.$request->search.'%')->get();

        return response()->json([
            'courses' => CourseResource::collection($courses),
        ], 200);
    }

    public function searchAcademiesCourseStudents(Academy $academy, $courseId, Request $request)
    {
        $students = $academy->courses()->find($courseId)->students()->where('name', 'like', '%'.$request->search.'%')->get();

        return response()->json([
            'students' => $students,
        ], 200);
    }

    public function searchAcademiesCourseTeachers(Academy $academy, $courseId, Request $request)
    {
        $teachers = $academy->courses()->find($courseId)->teachers()->where('name', 'like', '%'.$request->search.'%')->get();

        return response()->json([
            'teachers' => $teachers,
        ], 200);
    }

    public function getMyAcademies()
    {
        try {
            // $academiesAuthMember = AcademyMember::where('user_id', auth()->id())->get('academy_id');

            // SET-S2 — ซ่อนโรงเรียนที่ถูกเก็บถาวรออกจากรายการ
            return response()->json([
                'success' => true,
                'academies' => AcademyResource::collection(auth()->user()->academies()->notArchived()->paginate(10)),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 200);
        }
    }

    public function getAuthMemberedAcademies(User $user)
    {
        if (auth()->id() !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        try {
            // Get academies where user is a member with pending or approved status
            // Status: 1 = Pending, 2 = Approved, 3 = Rejected, 4 = Invited, 5 = Suspended
            $memberships = AcademyMember::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereIn('status', [1, 2, 'pending', 'accepted', 'approved']);
                })
                ->get(['academy_id', 'status', 'role']);

            $academyIds = $memberships->pluck('academy_id');

            // Get academies with pagination
            // SET-S2 — ซ่อนโรงเรียนที่ถูกเก็บถาวรออกจากรายการ
            $academies = Academy::notArchived()->whereIn('id', $academyIds)->paginate(10);

            // Map member status to each academy
            $academiesWithStatus = $academies->getCollection()->map(function ($academy) use ($memberships) {
                $membership = $memberships->firstWhere('academy_id', $academy->id);
                $academy->memberStatus = $membership ? $membership->status : null;
                $academy->memberRole = $membership ? $membership->role : null;

                return $academy;
            });

            $academies->setCollection($academiesWithStatus);

            return response()->json([
                'success' => true,
                'academies' => AcademyResource::collection($academies),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 200);
        }
    }

    public function getAllAcademies()
    {
        try {
            // SET-S2 — ซ่อนโรงเรียนที่ถูกเก็บถาวรออกจากรายการ
            return response()->json([
                'success' => true,
                'academies' => AcademyResource::collection(Academy::notArchived()->paginate(10)),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 200);
        }
    }

    /**
     * SET-S2 — เก็บถาวรโรงเรียน (ไม่ลบข้อมูลจริง กู้คืนได้)
     *
     * สิทธิ์: เจ้าของโรงเรียน หรือ super admin เท่านั้น (Academy::canManageArchive)
     * admin/director ของโรงเรียนทำไม่ได้ แม้ถือ settings.manage
     */
    public function archive(Academy $academy, Request $request)
    {
        if (! $academy->canManageArchive($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะเจ้าของโรงเรียนหรือผู้ดูแลระบบเท่านั้นที่เก็บถาวรโรงเรียนได้',
            ], 403);
        }

        if ($academy->isArchived()) {
            return response()->json([
                'success' => false,
                'code' => 'already_archived',
                'message' => 'โรงเรียนนี้ถูกเก็บถาวรอยู่แล้ว',
            ], 409);
        }

        // เขียนผ่านโมเดล (ไม่ใช่ DB::table) เพื่อให้ trait Auditable บันทึก audit log ให้อัตโนมัติ
        $academy->archived_at = now();
        $academy->save();

        return response()->json([
            'success' => true,
            'message' => 'เก็บถาวรโรงเรียนเรียบร้อยแล้ว',
            'archived_at' => $academy->archived_at,
        ], 200);
    }

    /**
     * SET-S2 — กู้คืนโรงเรียนที่ถูกเก็บถาวร
     */
    public function restore(Academy $academy, Request $request)
    {
        if (! $academy->canManageArchive($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะเจ้าของโรงเรียนหรือผู้ดูแลระบบเท่านั้นที่กู้คืนโรงเรียนได้',
            ], 403);
        }

        if (! $academy->isArchived()) {
            return response()->json([
                'success' => false,
                'code' => 'not_archived',
                'message' => 'โรงเรียนนี้ไม่ได้ถูกเก็บถาวร',
            ], 409);
        }

        $academy->archived_at = null;
        $academy->save();

        return response()->json([
            'success' => true,
            'message' => 'กู้คืนโรงเรียนเรียบร้อยแล้ว',
        ], 200);
    }

    /**
     * SET-S2 — รายการโรงเรียนที่ถูกเก็บถาวร
     *
     * คืนเฉพาะโรงเรียนที่ผู้เรียก "กู้คืนได้" — คือของตัวเอง
     * super admin เห็นทั้งหมด (นิยามเดียวกับ Academy::canManageArchive)
     */
    public function archivedIndex(Request $request)
    {
        $user = $request->user();

        $query = Academy::query()->archived();

        if (! $user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        return response()->json([
            'success' => true,
            'academies' => AcademyResource::collection($query->latest('archived_at')->get()),
        ], 200);
    }

    /**
     * Update academy settings
     */
    public function updateSettings(Academy $academy, Request $request)
    {
        // สิทธิ์ถูกตรวจที่ middleware `academy.permission:settings.manage` (CheckAcademyPermission)
        // ซึ่งครอบ superadmin, เจ้าของโรงเรียน, สถานะสมาชิก APPROVED และสิทธิ์ที่ได้จากฝ่าย/กลุ่ม

        $rules = [
            // SET-S8 — `academies.name` เป็น UNIQUE index ในฐาน ถ้าไม่กันที่นี่จะได้ 500
            // พร้อม SQL error ดิบแทน 422 (ด่านเดิมไล่หาชนผิดคอลัมน์)
            'name' => ['required', 'string', 'max:255', Rule::unique('academies', 'name')->ignore($academy->id)],
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|url|max:255',
            'address' => 'nullable|string',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'privacy' => 'nullable|string|in:public,private',
            'join_mode' => 'nullable|string|in:open,approval,invite_only',

            // SET-S6 — สวิตช์ที่ระบบบังคับใช้อยู่แล้วแต่เดิมตั้งค่าไม่ได้
            'card_request_flow_enabled' => 'nullable|boolean',
            'donation_enabled' => 'nullable|boolean',
            'student_editable_fields' => 'nullable|array',
            'student_editable_fields.mode' => 'required_with:student_editable_fields|string|in:blacklist,whitelist',
            'student_editable_fields.fields' => 'nullable|array',
            'student_editable_fields.fields.*' => ['string', Rule::in(Academy::STUDENT_EDITABLE_FIELD_CATALOG)],

            // SET-S7 — ฟิลด์อัตลักษณ์โรงเรียน (D15–D17)
            'slogan' => 'nullable|string|max:255',
            'type' => ['nullable', 'string', Rule::in(Academy::ACADEMY_TYPE_CATALOG)],
            // D16 — เก็บเป็น พ.ศ. ไม่ใช่ ค.ศ.
            'established_year' => 'nullable|integer|min:2400|max:'.(now()->year + 543),
            'director' => ['nullable', 'integer', function ($attribute, $value, $fail) use ($academy) {
                // D15 — ผอ. ต้องเป็นคนในโรงเรียนนี้จริง
                // เจ้าของโรงเรียนอาจไม่มีแถวใน academy_members จึงต้องยอมรับ user_id ของเจ้าของด้วย
                if ((int) $value === (int) $academy->user_id) {
                    return;
                }

                $isApprovedMember = AcademyMember::where('academy_id', $academy->id)
                    ->where('user_id', $value)
                    ->where('status', AcademyMember::STATUS_APPROVED)
                    ->exists();

                if (! $isApprovedMember) {
                    $fail('ผู้อำนวยการต้องเป็นสมาชิกของโรงเรียนนี้ที่ได้รับอนุมัติแล้ว');
                }
            }],
            'social_media_links' => ['nullable', 'array', function ($attribute, $value, $fail) {
                // D17 — คีย์นอกแคตตาล็อกต้องถูกปฏิเสธ (Laravel ไม่ตัดคีย์ส่วนเกินให้เอง)
                $unknown = array_diff(array_keys((array) $value), Academy::SOCIAL_LINK_CATALOG);
                if (! empty($unknown)) {
                    $fail('ช่องทางโซเชียลที่ไม่รองรับ: '.implode(', ', $unknown));
                }
            }],
        ];

        foreach (Academy::SOCIAL_LINK_CATALOG as $socialKey) {
            $rules["social_media_links.{$socialKey}"] = 'nullable|url|max:255';
        }

        $request->validate($rules);

        // SET-S9 — เก็บค่าก่อนแก้ไว้ทั้งสองตาราง เพื่อเขียน diff ลงประวัติกิจกรรมของโรงเรียน (D19/D20)
        $settingsAuditLogger = app(AcademySettingsAuditLogger::class);
        $auditBefore = $settingsAuditLogger->snapshot($academy, $academy->academySetting);

        try {
            // Update basic info
            $academy->fill($request->only([
                'name', 'name_en', 'description', 'description_en',
                'email', 'phone', 'website', 'address', 'province', 'country',
                // SET-S7 — ฟิลด์อัตลักษณ์
                'slogan', 'type', 'established_year', 'director',
            ]));

            // SET-S6 — เขียนค่า boolean ลงเสมอ ไม่ปล่อยให้เป็น NULL แล้วไป fallback config ของคอร์ส
            if ($request->has('donation_enabled')) {
                $academy->donation_enabled = $request->boolean('donation_enabled');
            }

            // student_editable_fields ไม่อยู่ใน $fillable โดยตั้งใจ จึงต้องเซ็ตตรง ๆ
            // ถ้าผู้ดูแลติ๊กออกหมด multipart จะไม่ส่งคีย์ fields มาเลย ⇒ ต้อง normalize เป็น []
            // ไม่ใช่ปล่อยคีย์หาย ไม่งั้นค่าที่เก็บจะกลายเป็นรูปทรงที่ needsApproval() อ่านไม่ตรงเจตนา
            if ($request->has('student_editable_fields')) {
                $editable = $request->input('student_editable_fields');
                $academy->student_editable_fields = [
                    'mode' => $editable['mode'],
                    'fields' => array_values($editable['fields'] ?? []),
                ];
            }

            // SET-S7 / D17 — เก็บเฉพาะคีย์ในแคตตาล็อกที่มีค่าจริง
            // ช่องที่ผู้ดูแลล้างทิ้งต้องหายไปจาก json ไม่ใช่ค้างเป็น null
            if ($request->has('social_media_links')) {
                $links = [];
                foreach (Academy::SOCIAL_LINK_CATALOG as $socialKey) {
                    $url = trim((string) $request->input("social_media_links.{$socialKey}", ''));
                    if ($url !== '') {
                        $links[$socialKey] = $url;
                    }
                }
                $academy->social_media_links = $links;
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $avatar_name = uniqid().'.'.$avatar->getClientOriginalExtension();
                $avatar_url = Storage::disk('public')->putFileAs('images/academies/logos', $avatar, $avatar_name);
                $academy->logo = Storage::disk('public')->url($avatar_url);
            }

            // Handle cover upload
            if ($request->hasFile('cover')) {
                $cover = $request->file('cover');
                $cover_name = uniqid().'.'.$cover->getClientOriginalExtension();
                $cover_url = Storage::disk('public')->putFileAs('images/academies/covers', $cover, $cover_name);
                $academy->cover = Storage::disk('public')->url($cover_url);
            }

            $academy->save();

            // Update academy settings
            $setting = $academy->academySetting;
            if (! $setting) {
                $setting = new AcademySetting(['academy_id' => $academy->id]);
            }

            if ($request->has('privacy')) {
                $setting->privacy = $request->privacy;
            }
            if ($request->has('join_mode')) {
                $setting->join_mode = $request->join_mode;
            }
            if ($request->has('show_member_list')) {
                $setting->show_member_list = $request->boolean('show_member_list');
            }
            if ($request->has('show_course_list')) {
                $setting->show_course_list = $request->boolean('show_course_list');
            }
            // SET-S6 — สวิตช์ระบบคำร้องทำบัตรนักเรียน (มีผู้อ่านไปใช้จริง 5 จุด)
            if ($request->has('card_request_flow_enabled')) {
                $setting->card_request_flow_enabled = $request->boolean('card_request_flow_enabled');
            }
            $setting->save();

            // SET-S9 — เขียนเฉพาะ diff · ไม่มีช่องไหนเปลี่ยน = ไม่มีแถว · ห้ามทำให้การบันทึกพัง
            $settingsAuditLogger->record($academy, $setting, $auditBefore);

            return response()->json([
                'success' => true,
                'message' => 'บันทึกการตั้งค่าเรียบร้อยแล้ว',
                'academy' => new AcademyResource($academy->fresh()),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
