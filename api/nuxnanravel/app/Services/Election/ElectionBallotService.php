<?php

namespace App\Services\Election;

use App\Models\Election;
use App\Models\ElectionBallot;
use App\Models\ElectionParty;
use App\Models\ElectionStation;
use App\Models\ElectionVoterReceipt;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ElectionBallotService
{
    public function cast(Election $e, string $ballotToken, ?int $partyId, User $actor): array
    {
        // Deliberately unused: logging actor with partyId would compromise ballot secrecy.
        return DB::transaction(function () use ($e, $ballotToken, $partyId) {
            $receipt = ElectionVoterReceipt::where('election_id', $e->id)
                ->where('token_hash', hash('sha256', $ballotToken))
                ->lockForUpdate()->first();
            if (! $receipt || ! hash_equals((string) $receipt->token_hash, hash('sha256', $ballotToken))) {
                throw new DomainException('ไม่พบบัตรลงคะแนนที่ใช้งานได้');
            }
            $election = Election::whereKey($e->id)->lockForUpdate()->firstOrFail();
            $station = ElectionStation::whereKey($receipt->station_id)->lockForUpdate()->firstOrFail();
            if ($receipt->status !== ElectionVoterReceipt::STATUS_ISSUED || ! $receipt->token_expires_at || ! $receipt->token_expires_at->isFuture() || $election->status !== Election::STATUS_VOTING || ! $station->is_open) {
                throw new DomainException('บัตรลงคะแนนนี้ไม่สามารถใช้งานได้');
            }
            if ($partyId === null) {
                if (! $election->allow_abstain) {
                    throw new DomainException('การไม่ประสงค์ลงคะแนนไม่เปิดให้ใช้งาน');
                }
            } elseif (! ElectionParty::where('id', $partyId)->where('election_id', $election->id)->where('status', ElectionParty::STATUS_APPROVED)->exists()) {
                throw new DomainException('พรรคที่เลือกไม่ถูกต้อง');
            }
            ElectionBallot::create(['uuid' => (string) Str::uuid(), 'election_id' => $election->id, 'party_id' => $partyId]);
            $receipt->update(['status' => ElectionVoterReceipt::STATUS_CAST, 'cast_at' => now()->second(0), 'token_hash' => null]);

            return ['success' => true];
        });
    }

    public function verifyIntegrity(Election $e): array
    {
        $ballots = ElectionBallot::where('election_id', $e->id)->count();
        $castReceipts = ElectionVoterReceipt::where('election_id', $e->id)->where('status', ElectionVoterReceipt::STATUS_CAST)->count();

        return ['ballots' => $ballots, 'cast_receipts' => $castReceipts, 'matches' => $ballots === $castReceipts];
    }
}
