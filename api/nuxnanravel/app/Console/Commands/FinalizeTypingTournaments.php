<?php

namespace App\Console\Commands;

use App\Models\TypingTournament;
use App\Models\TypingTournamentEntry;
use Illuminate\Console\Command;

class FinalizeTypingTournaments extends Command
{
    protected $signature = 'typing:finalize-tournaments';

    protected $description = 'Update tournament statuses and finalize rankings';

    public function handle(): void
    {
        // upcoming -> active
        $activated = TypingTournament::upcoming()
            ->where('starts_at', '<=', now())
            ->update(['status' => 'active']);

        if ($activated > 0) {
            $this->info("Activated {$activated} tournaments.");
        }

        // active -> finished
        $toFinish = TypingTournament::active()
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($toFinish as $tournament) {
            $tournament->update(['status' => 'finished']);

            // Assign ranks by best_score; tie-break by whoever reached it first
            // (lower best_session_id = earlier attempt) for deterministic, fair order.
            $entries = TypingTournamentEntry::where('tournament_id', $tournament->id)
                ->orderByDesc('best_score')
                ->orderBy('best_session_id')
                ->get();

            foreach ($entries as $i => $entry) {
                $entry->update(['rank' => $i + 1]);
            }

            $this->info("Finalized: {$tournament->name} ({$entries->count()} entries)");
        }
    }
}
