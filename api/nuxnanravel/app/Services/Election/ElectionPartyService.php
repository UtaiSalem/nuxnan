<?php

namespace App\Services\Election;

use App\Models\AcademyMember;
use App\Models\Election;
use App\Models\ElectionParty;
use App\Models\MemberActivityLog;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ElectionPartyService
{
    public function apply(Election $e, array $data, User $actor): ElectionParty
    {
        $this->nomination($e);
        $this->validateMembers($e, $data['members'], $actor, null);
        $p = DB::transaction(function () use ($e, $data, $actor) {
            $payload = collect($data)->except(['members', 'number', 'logo'])->all();
            $payload += ['status' => ElectionParty::STATUS_PENDING, 'number' => null, 'applied_by' => $actor->id];
            if (($data['logo'] ?? null) instanceof UploadedFile) {
                $payload['logo_path'] = Storage::disk('public')->putFile('images/elections', $data['logo']);
            }
            $p = $e->parties()->create($payload);
            $this->saveMembers($p, $data['members']);

            return $p;
        });
        $this->log($e, $actor, MemberActivityLog::ACTION_ELECTION_PARTY_APPLY, ['party_id' => $p->id]);

        return $p->load('members.user');
    }

    public function update(ElectionParty $p, array $data, User $actor): ElectionParty
    {
        if ($p->status !== ElectionParty::STATUS_PENDING) {
            throw new DomainException('แก้ไขได้เฉพาะใบสมัครที่รอตรวจสอบเท่านั้น');
        }
        if ($p->applied_by !== $actor->id && ! AcademyMember::where(['academy_id' => $p->election->academy_id, 'user_id' => $actor->id, 'status' => 2])->first()?->hasPermission('elections.manage')) {
            throw new DomainException('ไม่มีสิทธิ์แก้ไขใบสมัครนี้');
        }
        if (isset($data['members'])) {
            $this->validateMembers($p->election, $data['members'], $p->applicant, $p);
        }
        DB::transaction(function () use ($p, $data) {
            $p->update(collect($data)->except(['members', 'number', 'logo'])->all());
            if (($data['logo'] ?? null) instanceof UploadedFile) {
                $p->update(['logo_path' => Storage::disk('public')->putFile('images/elections', $data['logo'])]);
            } if (isset($data['members'])) {
                $p->members()->delete();
                $this->saveMembers($p, $data['members']);
            }
        });
        $this->log($p->election, $actor, MemberActivityLog::ACTION_ELECTION_PARTY_UPDATE, ['party_id' => $p->id]);

        return $p->fresh('members.user');
    }

    public function withdraw(ElectionParty $p, User $actor): ElectionParty
    {
        $election = $p->election()->firstOrFail();
        if ($p->applied_by !== $actor->id && ! AcademyMember::where(['academy_id' => $election->academy_id, 'user_id' => $actor->id, 'status' => 2])->first()?->hasPermission('elections.manage')) {
            throw new DomainException('ไม่มีสิทธิ์ถอนใบสมัครนี้');
        }
        if (in_array($election->status, [Election::STATUS_VOTING, Election::STATUS_CLOSED, Election::STATUS_PUBLISHED])) {
            throw new DomainException('ไม่สามารถถอนพรรคหลังเริ่มลงคะแนนได้');
        } if (! in_array($p->status, [ElectionParty::STATUS_PENDING, ElectionParty::STATUS_APPROVED])) {
            throw new DomainException('ไม่สามารถถอนพรรคในสถานะปัจจุบันได้');
        } $p->update(['status' => ElectionParty::STATUS_WITHDRAWN]);
        $this->log($election, $actor, MemberActivityLog::ACTION_ELECTION_PARTY_WITHDRAW, ['party_id' => $p->id]);

        return $p->fresh();
    }

    public function approve(ElectionParty $p, ?int $number, User $actor): ElectionParty
    {
        return DB::transaction(function () use ($p, $number, $actor) {
            $election = Election::whereKey($p->election_id)->lockForUpdate()->firstOrFail();
            if ($p->status !== ElectionParty::STATUS_PENDING) {
                throw new DomainException('อนุมัติได้เฉพาะใบสมัครที่รอตรวจสอบเท่านั้น');
            }
            if ($number === null) {
                $number = 1;
                while ($p->election->parties()->where('number', $number)->exists()) {
                    $number++;
                }
            }
            if ($p->election->parties()->where('number', $number)->exists()) {
                throw new DomainException('หมายเลข '.$number.' ถูกใช้แล้ว');
            } $p->update(['status' => ElectionParty::STATUS_APPROVED, 'number' => $number, 'reviewed_by' => $actor->id, 'reviewed_at' => now()]);
            $this->log($p->election, $actor, MemberActivityLog::ACTION_ELECTION_PARTY_APPROVE, ['party_id' => $p->id, 'number' => $number]);

            return $p->fresh('members.user');
        });
    }

    public function reject(ElectionParty $p, string $note, User $actor): ElectionParty
    {
        if ($p->status !== ElectionParty::STATUS_PENDING) {
            throw new DomainException('ปฏิเสธได้เฉพาะใบสมัครที่รอตรวจสอบเท่านั้น');
        } if (trim($note) === '') {
            throw new DomainException('กรุณาระบุเหตุผลการปฏิเสธ');
        } $p->update(['status' => ElectionParty::STATUS_REJECTED, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => $note]);
        $this->log($p->election, $actor, MemberActivityLog::ACTION_ELECTION_PARTY_REJECT, ['party_id' => $p->id, 'review_note' => $note]);

        return $p->fresh();
    }

    private function nomination(Election $e): void
    {
        if ($e->status !== Election::STATUS_NOMINATION) {
            throw new DomainException('ไม่สามารถสมัครพรรคได้ในสถานะ '.$e->status);
        } if ($e->nomination_closes_at && $e->nomination_closes_at->isPast()) {
            throw new DomainException('หมดเวลารับสมัครแล้ว');
        }
    }

    private function validateMembers(Election $e, array $members, User $actor, ?ElectionParty $current): void
    {
        if (collect($members)->pluck('user_id')->duplicates()->isNotEmpty()) {
            throw new DomainException('ไม่สามารถส่งสมาชิกคนเดิมซ้ำในใบสมัครเดียวกันได้');
        }
        if (collect($members)->where('role', 'leader')->count() !== 1) {
            throw new DomainException('ต้องมีผู้สมัครประธาน 1 คนเท่านั้น');
        } if (! collect($members)->pluck('user_id')->contains($actor->id)) {
            throw new DomainException('ผู้สมัครต้องเป็นสมาชิกในทีม');
        } foreach ($members as $m) {
            if (! AcademyMember::where(['academy_id' => $e->academy_id, 'user_id' => $m['user_id'], 'status' => 2])->exists()) {
                throw new DomainException('ผู้สมัครหมายเลข '.$m['user_id'].' ไม่ใช่สมาชิกที่ได้รับอนุมัติ');
            } $q = ElectionParty::where('election_id', $e->id)->whereNotIn('status', [ElectionParty::STATUS_WITHDRAWN, ElectionParty::STATUS_REJECTED]);
            if ($current) {
                $q->where('id', '!=', $current->id);
            } $old = $q->whereHas('members', fn ($x) => $x->where('user_id', $m['user_id']))->first();
            if ($old) {
                throw new DomainException('บุคคลนี้อยู่ในพรรค '.$old->name.' แล้ว');
            }
        }
    }

    private function saveMembers(ElectionParty $p, array $members): void
    {
        foreach ($members as $i => $m) {
            $p->members()->create(['user_id' => $m['user_id'], 'role' => $m['role'], 'position_label' => $m['position_label'] ?? null, 'sort_order' => $m['sort_order'] ?? $i]);
        }
    }

    private function log(Election $e, User $a, string $action, array $values): void
    {
        $descriptions = [
            MemberActivityLog::ACTION_ELECTION_PARTY_APPLY => 'สมัครพรรคเลือกตั้ง',
            MemberActivityLog::ACTION_ELECTION_PARTY_UPDATE => 'แก้ไขพรรคเลือกตั้ง',
            MemberActivityLog::ACTION_ELECTION_PARTY_WITHDRAW => 'ถอนพรรคเลือกตั้ง',
            MemberActivityLog::ACTION_ELECTION_PARTY_APPROVE => 'อนุมัติพรรคเลือกตั้ง',
            MemberActivityLog::ACTION_ELECTION_PARTY_REJECT => 'ปฏิเสธพรรคเลือกตั้ง',
        ];
        MemberActivityLog::logActivity(['academy_id' => $e->academy_id, 'user_id' => $a->id, 'action' => $action, 'description' => $descriptions[$action] ?? $action, 'new_values' => $values]);
    }
}
