<?php

namespace App\Services\Election;

use App\Models\Academy;
use App\Models\Election;
use App\Models\ElectionParty;
use App\Models\MemberActivityLog;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ElectionService
{
    public function create(array $data, User $actor, Academy $academy): Election
    {
        unset($data['status']);
        $election = $academy->elections()->create(array_merge($data, ['status' => Election::STATUS_DRAFT, 'created_by' => $actor->id]));
        $this->log($election, $actor, MemberActivityLog::ACTION_ELECTION_CREATE, 'สร้างการเลือกตั้ง', ['election_id' => $election->id]);

        return $election;
    }

    public function update(Election $e, array $data, User $actor): Election
    {
        if ($e->status === Election::STATUS_PUBLISHED) {
            throw new DomainException('ไม่สามารถแก้ไขการเลือกตั้งที่ประกาศผลแล้วได้');
        }
        unset($data['status']);
        $e->update($data);
        $this->log($e, $actor, MemberActivityLog::ACTION_ELECTION_UPDATE, 'แก้ไขการเลือกตั้ง', ['election_id' => $e->id]);

        return $e->fresh();
    }

    public function transitionTo(Election $e, string $to, User $actor): Election
    {
        return DB::transaction(function () use ($e, $to, $actor) {
            $e = Election::whereKey($e->id)->lockForUpdate()->firstOrFail();
            $from = $e->status;
            $legal = [Election::STATUS_DRAFT => Election::STATUS_NOMINATION, Election::STATUS_NOMINATION => Election::STATUS_CAMPAIGN, Election::STATUS_CAMPAIGN => Election::STATUS_VOTING, Election::STATUS_VOTING => Election::STATUS_CLOSED, Election::STATUS_CLOSED => Election::STATUS_PUBLISHED];
            if ($from === Election::STATUS_PUBLISHED || ($to !== Election::STATUS_CANCELLED && (($legal[$from] ?? null) !== $to))) {
                throw new DomainException('ไม่อนุญาตให้เปลี่ยนสถานะการเลือกตั้งจาก '.$from.' เป็น '.$to);
            }
            if ($to === Election::STATUS_VOTING) {
                if (! $e->voter_roll_locked_at) {
                    throw new DomainException('ไม่สามารถเปิดลงคะแนนได้ เนื่องจากยังไม่ได้ล็อกบัญชีผู้มีสิทธิ์');
                }
                if (! $e->parties()->where('status', ElectionParty::STATUS_APPROVED)->exists()) {
                    throw new DomainException('ไม่สามารถเปิดลงคะแนนได้ เนื่องจากยังไม่มีพรรคที่ได้รับอนุมัติ');
                }
            }
            $e->update(['status' => $to]);
            $this->log($e, $actor, MemberActivityLog::ACTION_ELECTION_STATUS_CHANGE, 'เปลี่ยนสถานะการเลือกตั้ง', ['election_id' => $e->id, 'from' => $from, 'to' => $to]);

            return $e->fresh();
        });
    }

    public function delete(Election $e, User $actor): void
    {
        if ($e->status === Election::STATUS_PUBLISHED) {
            throw new DomainException('ไม่สามารถลบการเลือกตั้งที่ประกาศผลแล้วได้');
        }
        if ($e->ballots()->exists()) {
            throw new DomainException('ไม่สามารถลบการเลือกตั้งที่มีบัตรลงคะแนนแล้วได้');
        }
        $e->delete();
        $this->log($e, $actor, MemberActivityLog::ACTION_ELECTION_DELETE, 'ลบการเลือกตั้ง', ['election_id' => $e->id]);
    }

    private function log(Election $e, User $actor, string $action, string $description, ?array $values = null): void
    {
        MemberActivityLog::logActivity(['academy_id' => $e->academy_id, 'user_id' => $actor->id, 'action' => $action, 'description' => $description, 'new_values' => $values]);
    }
}
