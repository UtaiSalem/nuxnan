<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\MemberTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Member Tag Controller - ระบบแท็กสมาชิก
 */
class MemberTagController extends Controller
{
    /**
     * Get all tags for the academy
     */
    public function index(Academy $academy, Request $request)
    {
        $query = MemberTag::forAcademy($academy->id)->ordered();

        // Include member counts if requested
        if ($request->boolean('with_counts', true)) {
            $query->withCount('members');
        }

        // Filter active only
        if ($request->boolean('active_only', false)) {
            $query->active();
        }

        $tags = $query->get();

        return response()->json([
            'success' => true,
            'tags' => $tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'color' => $tag->color,
                    'description' => $tag->description,
                    'is_active' => $tag->is_active,
                    'member_count' => $tag->members_count ?? $tag->member_count,
                    'sort_order' => $tag->sort_order,
                ];
            }),
        ], 200);
    }

    /**
     * Create a new tag
     */
    public function store(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('member_tags')->where(function ($query) use ($academy) {
                    return $query->where('academy_id', $academy->id);
                }),
            ],
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:200',
        ]);

        $maxOrder = MemberTag::forAcademy($academy->id)->max('sort_order') ?? 0;

        $tag = MemberTag::create([
            'academy_id' => $academy->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6366f1',
            'description' => $validated['description'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างแท็กเรียบร้อยแล้ว',
            'tag' => $tag,
        ], 201);
    }

    /**
     * Update a tag
     */
    public function update(Academy $academy, MemberTag $tag, Request $request)
    {
        if ($tag->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบแท็ก',
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('member_tags')->where(function ($query) use ($academy) {
                    return $query->where('academy_id', $academy->id);
                })->ignore($tag->id),
            ],
            'color' => 'sometimes|string|max:20',
            'description' => 'nullable|string|max:200',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $tag->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทแท็กเรียบร้อยแล้ว',
            'tag' => $tag->fresh(),
        ], 200);
    }

    /**
     * Delete a tag
     */
    public function destroy(Academy $academy, MemberTag $tag)
    {
        if ($tag->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบแท็ก',
            ], 404);
        }

        $tag->members()->detach();
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบแท็กเรียบร้อยแล้ว',
        ], 200);
    }

    /**
     * Get members with a specific tag
     */
    public function getTagMembers(Academy $academy, MemberTag $tag, Request $request)
    {
        if ($tag->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบแท็ก',
            ], 404);
        }

        $perPage = min($request->get('per_page', 20), 100);

        $members = $tag->members()
            ->with(['user:id,name,profile_photo_path,email', 'role:id,name,display_name'])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'tag' => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ],
            'members' => $members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user' => $member->user ? [
                        'id' => $member->user->id,
                        'name' => $member->user->name,
                        'avatar' => $member->user->profile_photo_path,
                        'email' => $member->user->email,
                    ] : null,
                    'role' => $member->role ? [
                        'id' => $member->role->id,
                        'name' => $member->role->name,
                        'display_name' => $member->role->display_name,
                    ] : null,
                    'status' => $member->status,
                    'assigned_at' => $member->pivot->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ], 200);
    }

    /**
     * Add tags to a member
     */
    public function addTagsToMember(Academy $academy, AcademyMember $member, Request $request)
    {
        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสมาชิก',
            ], 404);
        }

        $request->validate([
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'exists:member_tags,id',
        ]);

        // Validate all tags belong to academy
        $validTagIds = MemberTag::forAcademy($academy->id)
            ->whereIn('id', $request->tag_ids)
            ->pluck('id');

        $syncData = [];
        foreach ($validTagIds as $tagId) {
            $syncData[$tagId] = ['assigned_by' => $request->user()->id];
        }

        $member->tags()->syncWithoutDetaching($syncData);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มแท็กเรียบร้อยแล้ว',
            'tags' => $member->tags,
        ], 200);
    }

    /**
     * Remove tags from a member
     */
    public function removeTagsFromMember(Academy $academy, AcademyMember $member, Request $request)
    {
        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสมาชิก',
            ], 404);
        }

        $request->validate([
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'exists:member_tags,id',
        ]);

        $member->tags()->detach($request->tag_ids);

        return response()->json([
            'success' => true,
            'message' => 'ลบแท็กออกเรียบร้อยแล้ว',
        ], 200);
    }

    /**
     * Bulk add tag to multiple members
     */
    public function bulkAddTag(Academy $academy, Request $request)
    {
        $request->validate([
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'exists:academy_members,id',
            'tag_id' => 'required|exists:member_tags,id',
        ]);

        $tag = MemberTag::find($request->tag_id);
        if ($tag->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'แท็กไม่ถูกต้อง',
            ], 422);
        }

        $validMemberIds = AcademyMember::where('academy_id', $academy->id)
            ->whereIn('id', $request->member_ids)
            ->pluck('id');

        $syncData = [];
        foreach ($validMemberIds as $memberId) {
            $syncData[$memberId] = ['assigned_by' => $request->user()->id];
        }

        // Use syncWithoutDetaching on the inverse relationship
        DB::table('academy_member_tag')->insertOrIgnore(
            array_map(function ($memberId) use ($tag, $request) {
                return [
                    'academy_member_id' => $memberId,
                    'member_tag_id' => $tag->id,
                    'assigned_by' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $validMemberIds->toArray())
        );

        return response()->json([
            'success' => true,
            'message' => "เพิ่มแท็ก '{$tag->name}' ให้สมาชิก {$validMemberIds->count()} คน",
            'count' => $validMemberIds->count(),
        ], 200);
    }

    /**
     * Get tags for a specific member
     */
    public function getMemberTags(Academy $academy, AcademyMember $member)
    {
        if ($member->academy_id !== $academy->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสมาชิก',
            ], 404);
        }

        $tags = $member->tags()->get();

        return response()->json([
            'success' => true,
            'tags' => $tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                    'assigned_at' => $tag->pivot->created_at,
                ];
            }),
        ], 200);
    }

    /**
     * Get available colors
     */
    public function getAvailableColors()
    {
        return response()->json([
            'success' => true,
            'colors' => MemberTag::getAvailableColors(),
        ], 200);
    }
}
