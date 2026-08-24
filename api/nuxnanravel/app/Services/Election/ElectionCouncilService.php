<?php

namespace App\Services\Election;

use App\Models\AcademyGroup;
use App\Models\AcademyGroupAdmin;
use App\Models\AcademyGroupMember;
use App\Models\Election;
use App\Models\MemberActivityLog;
use App\Services\AcademyGroupPermissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ElectionCouncilService
{
    public function __construct(private AcademyGroupPermissionService $permissions) {}

    public function create(Election $election, array $data, $actor): AcademyGroup
    {
        return DB::transaction(function () use ($election, $data, $actor) {
            if (! $election->published_at) {
                throw ValidationException::withMessages(['election' => 'ยังประกาศผลไม่เสร็จ ตั้งคณะกรรมการไม่ได้']);
            }

            $winners = $election->results()->where('is_winner', true)->with('party.members')->get();
            if ($winners->count() !== 1) {
                $details = $winners->map(fn ($r) => "{$r->party?->name} ({$r->votes} คะแนน)")->implode(', ');
                throw ValidationException::withMessages(['election' => $winners->isEmpty() ? 'ไม่พบผู้ชนะ ไม่สามารถตั้งคณะกรรมการได้' : "มีผู้ชนะเสมอกัน: {$details} กรุณาให้ กกต. ตัดสินเอง"]);
            }

            $existing = AcademyGroup::where('academy_id', $election->academy_id)
                ->where('settings->election_id', $election->id)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                throw ValidationException::withMessages(['election' => 'ตั้งสภานักเรียนจากการเลือกตั้งนี้ไปแล้ว', 'group_id' => $existing->id, 'group_name' => $existing->name]);
            }

            $winner = $winners->first();
            $leader = $winner->party->members->firstWhere('role', 'leader');
            if (! $leader) {
                throw ValidationException::withMessages(['election' => 'พรรคที่ชนะไม่มีสมาชิกหัวหน้าพรรค']);
            }

            $group = AcademyGroup::create([
                'academy_id' => $election->academy_id,
                'name' => $data['name'] ?? $election->title,
                'type' => 'student_council',
                'settings' => ['election_id' => $election->id, 'party_id' => $winner->party_id, 'published_at' => $election->published_at],
            ]);
            $this->permissions->seedDefaults($group);
            foreach ($winner->party->members as $member) {
                AcademyGroupMember::create(['academy_group_id' => $group->id, 'user_id' => $member->user_id, 'role' => $member->role, 'status' => 2, 'invited_by' => $actor->id]);
            }
            AcademyGroupAdmin::create(['academy_group_id' => $group->id, 'user_id' => $leader->user_id, 'role' => 'leader', 'appointed_by' => $actor->id]);
            MemberActivityLog::logActivity(['academy_id' => $election->academy_id, 'user_id' => $actor->id, 'action' => MemberActivityLog::ACTION_ELECTION_COUNCIL_CREATE, 'action_category' => MemberActivityLog::CATEGORY_SYSTEM, 'new_values' => ['election_id' => $election->id, 'party_id' => $winner->party_id, 'group_id' => $group->id]]);

            return $group;
        });
    }
}
