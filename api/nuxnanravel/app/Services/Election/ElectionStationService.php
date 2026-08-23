<?php

namespace App\Services\Election;

use App\Models\AcademyMember;
use App\Models\Election;
use App\Models\ElectionParty;
use App\Models\ElectionStation;
use App\Models\ElectionVoter;
use App\Models\ElectionVoterReceipt;
use App\Models\MemberActivityLog;
use App\Models\StudentCard;
use App\Models\User;
use App\Services\StudentIdentifierResolver;
use DomainException;
use Illuminate\Support\Facades\DB;

class ElectionStationService
{
    public function open(ElectionStation $station, User $actor): ElectionStation
    {
        $e = $station->election()->firstOrFail();
        if ($e->status !== Election::STATUS_VOTING) {
            throw new DomainException('ยังไม่สามารถเปิดสถานีได้ เนื่องจากการเลือกตั้งยังไม่อยู่ในช่วงลงคะแนน');
        }
        $station->update(['is_open' => true, 'opened_by' => $actor->id, 'opened_at' => now(), 'closed_by' => null, 'closed_at' => null]);
        $this->log($e, $actor, MemberActivityLog::ACTION_ELECTION_STATION_OPEN, ['station_id' => $station->id]);

        return $station->fresh();
    }

    public function close(ElectionStation $station, User $actor): ElectionStation
    {
        $station->update(['is_open' => false, 'closed_by' => $actor->id, 'closed_at' => now()]);
        $this->log($station->election()->firstOrFail(), $actor, MemberActivityLog::ACTION_ELECTION_STATION_CLOSE, ['station_id' => $station->id]);

        return $station->fresh();
    }

    public function lookup(ElectionStation $station, string $identifier = '', ?int $userId = null, ?string $memberCode = null): array
    {
        $identifier = trim($identifier);
        $election = $station->election()->firstOrFail();
        if (preg_match('/^STUDENT:([^:]+):(.+)$/', $identifier, $m)) {
            if ((string) $election->academy_id !== $m[1]) {
                throw new DomainException('QR นี้ไม่ตรงกับสถาบันของการเลือกตั้ง กรุณาใช้ QR ของสถาบันนี้');
            }
            $identifier = $m[2];
        }
        $resolved = $userId !== null
            ? ['user_id' => $userId, 'student_name' => null]
            : app(StudentIdentifierResolver::class)->resolve($election->academy_id, $memberCode ?? $identifier);
        $voter = $resolved['user_id'] ? ElectionVoter::where('election_id', $election->id)->where('user_id', $resolved['user_id'])->first() : null;
        $user = $resolved['user_id'] ? User::find($resolved['user_id']) : null;
        $member = $resolved['user_id']
            ? AcademyMember::where('user_id', $resolved['user_id'])
                ->where('academy_id', $election->academy_id)
                ->first()
            : null;
        $card = $member?->student_id
            ? StudentCard::where('student_id', $member->student_id)
                ->where('academy_id', $election->academy_id)
                ->where('is_active_flag', 1)
                ->latest('id')
                ->first()
            : null;

        $status = ! $voter ? 'not_on_roll' : ($voter->receipt?->status === 'cast' ? 'already_voted' : 'eligible');
        $statusLabels = ['not_on_roll' => 'ไม่มีรายชื่อผู้มีสิทธิ์เลือกตั้ง', 'already_voted' => 'ลงคะแนนแล้ว', 'eligible' => 'มีสิทธิ์ลงคะแนน'];

        return [
            'user_id' => $resolved['user_id'],
            'name' => $voter?->display_name ?? $resolved['student_name'] ?? $user?->name,
            'photo' => $card?->profile_image_url ?? $user?->profile_photo_path,
            'classroom' => $voter?->classroom_name,
            'grade_level' => $voter?->grade_level,
            'status' => $status,
            'status_label' => $statusLabels[$status],
        ];
    }

    public function searchByName(ElectionStation $station, string $term)
    {
        return ElectionVoter::where('election_id', $station->election_id)->where('display_name', 'like', '%'.trim($term).'%')->orderBy('display_name')->paginate();
    }

    public function issue(ElectionStation $station, int $userId, User $actor): array
    {
        return DB::transaction(function () use ($station, $userId, $actor) {
            $e = Election::whereKey($station->election_id)->lockForUpdate()->firstOrFail();
            $station = ElectionStation::whereKey($station->id)->lockForUpdate()->firstOrFail();
            if ($e->status !== Election::STATUS_VOTING || ! $station->is_open) {
                throw new DomainException('สถานียังไม่ได้เปิดลงคะแนน กรุณาเปิดสถานีก่อนเริ่มให้ผู้มีสิทธิ์ลงคะแนน');
            }
            $voter = ElectionVoter::where(['election_id' => $e->id, 'user_id' => $userId])->lockForUpdate()->first();
            if (! $voter) {
                throw new DomainException('บุคคลนี้ไม่มีรายชื่อในบัญชีผู้มีสิทธิ์เลือกตั้ง กรุณาตรวจสอบรายชื่ออีกครั้ง');
            }
            $receipt = ElectionVoterReceipt::where(['election_id' => $e->id, 'user_id' => $userId])->lockForUpdate()->first();
            if ($receipt?->status === ElectionVoterReceipt::STATUS_CAST) {
                throw new DomainException('บัตรลงคะแนนของบุคคลนี้ถูกลงคะแนนแล้ว ไม่สามารถออกบัตรใหม่ได้');
            }
            $token = bin2hex(random_bytes(32));
            $payload = ['election_voter_id' => $voter->id, 'station_id' => $station->id, 'issued_by' => $actor->id, 'status' => 'issued', 'token_hash' => hash('sha256', $token), 'token_expires_at' => now()->addSeconds($e->ballot_ttl_seconds ?? 180), 'issued_at' => now(), 'void_reason' => null];
            if ($receipt) {
                $receipt->update($payload);
            } else {
                $receipt = ElectionVoterReceipt::create(array_merge(['election_id' => $e->id, 'user_id' => $userId], $payload));
            }
            $this->log($e, $actor, MemberActivityLog::ACTION_ELECTION_BALLOT_ISSUE, ['election_id' => $e->id, 'station_id' => $station->id, 'user_id' => $userId]);

            return [
                'ballot_token' => $token,
                'parties' => $e->parties()->where('status', ElectionParty::STATUS_APPROVED)->orderBy('number')->get(),
                'allow_abstain' => (bool) $e->allow_abstain,
                'ballot_ttl_seconds' => $e->ballot_ttl_seconds ?? 180,
            ];
        });
    }

    public function void(ElectionVoterReceipt $receipt, string $reason, User $actor): ElectionVoterReceipt
    {
        return DB::transaction(function () use ($receipt, $reason, $actor) {
            $receipt = ElectionVoterReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            if ($receipt->status === ElectionVoterReceipt::STATUS_CAST) {
                throw new DomainException('บัตรลงคะแนนที่ลงคะแนนแล้วไม่สามารถเรียกคืนได้');
            }
            $receipt->update(['status' => 'void', 'token_hash' => null, 'token_expires_at' => null, 'void_reason' => $reason]);
            $this->log($receipt->election, $actor, MemberActivityLog::ACTION_ELECTION_BALLOT_VOID, ['receipt_id' => $receipt->id, 'user_id' => $receipt->user_id]);

            return $receipt->fresh();
        });
    }

    public function expireStale(Election $e): int
    {
        return $e->receipts()->where('status', 'issued')->where('token_expires_at', '<', now())->update(['status' => 'expired', 'token_hash' => null]);
    }

    private function log(Election $e, User $actor, string $action, array $values): void
    {
        $descriptions = [
            MemberActivityLog::ACTION_ELECTION_STATION_OPEN => 'เปิดสถานีลงคะแนน',
            MemberActivityLog::ACTION_ELECTION_STATION_CLOSE => 'ปิดสถานีลงคะแนน',
            MemberActivityLog::ACTION_ELECTION_BALLOT_ISSUE => 'ออกบัตรลงคะแนน',
            MemberActivityLog::ACTION_ELECTION_BALLOT_VOID => 'ยกเลิกบัตรลงคะแนน',
        ];
        MemberActivityLog::logActivity(['academy_id' => $e->academy_id, 'user_id' => $actor->id, 'action' => $action, 'description' => $descriptions[$action] ?? $action, 'new_values' => $values]);
    }
}
