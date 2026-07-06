<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentCard;
use Illuminate\Console\Command;

class StudentsBackfillCardLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:backfill-card-link {--dry-run : Only show what would be updated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill student_id into student_cards table based on student_number or national_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill of student-card links...');

        $cards = StudentCard::whereNull('student_id')->get();
        $this->info("Found {$cards->count()} cards to process.");

        $matched = 0;
        $failed = 0;

        foreach ($cards as $card) {
            $student = Student::where('student_id', $card->student_number)
                ->orWhere('citizen_id', $card->national_id)
                ->first();

            if ($student) {
                if (! $this->option('dry-run')) {
                    $card->student_id = $student->id;
                    $card->save();
                }
                $matched++;
                $this->line("Matched: Card #{$card->id} ($card->student_number / $card->national_id) -> Student #{$student->id}");
            } else {
                $failed++;
                $this->warn("Failed: No student found for Card #{$card->id} ($card->student_number / $card->national_id)");
            }
        }

        $this->info('Backfill completed.');
        $this->info("Total matched: {$matched}");
        $this->info("Total failed: {$failed}");

        if ($this->option('dry-run')) {
            $this->warn('This was a DRY RUN. No changes were made.');
        }
    }
}
