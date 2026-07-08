<?php

namespace App\Console\Commands;

use App\Enums\StudentCardRequestOrigin;
use App\Enums\StudentCardRequestStatus;
use App\Enums\StudentCardRequestType;
use App\Models\Academy;
use App\Models\ClassroomStudent;
use App\Models\StudentCard;
use App\Models\StudentCardRequest;
use Illuminate\Console\Command;

class SeedLegacyCardRequests extends Command
{
    protected $signature = 'students:seed-legacy-card-requests {academy : Academy ID} {--dry-run}';

    protected $description = 'Create synthetic completed requests for existing student cards';

    public function handle(): int
    {
        $academy = Academy::find($this->argument('academy'));
        if (! $academy) {
            $this->error('Academy not found.');

            return self::FAILURE;
        }

        $cards = StudentCard::query()->with('student')->where('academy_id', $academy->id)->get();
        $created = 0;
        foreach ($cards as $card) {
            if (StudentCardRequest::where('result_card_id', $card->id)->exists()) {
                continue;
            }
            if ($this->option('dry-run')) {
                $created++;

                continue;
            }
            $enrollment = ClassroomStudent::query()->with('classroom')->where('academy_id', $academy->id)
                ->where('student_id', $card->student_id)->latest('id')->first();
            StudentCardRequest::create([
                'academy_id' => $academy->id,
                'academic_year_id' => $card->academic_year_id,
                'classroom_id' => $enrollment?->classroom_id,
                'student_id' => $card->student_id,
                'result_card_id' => $card->id,
                'request_type' => StudentCardRequestType::FirstIssue,
                'status' => StudentCardRequestStatus::Completed,
                'priority' => 'normal',
                'origin' => StudentCardRequestOrigin::Legacy,
                'full_name_snapshot' => $card->full_name_thai ?: $card->student?->full_name_th ?: 'Unknown',
                'student_number_snapshot' => $card->student_number,
                'grade_level_snapshot' => $enrollment?->classroom?->grade_level ?? $card->class_level,
                'section_snapshot' => $enrollment?->classroom?->section ?? $card->class_section,
                'requested_at' => $card->created_at,
                'completed_at' => $card->created_at,
            ]);
            $created++;
        }

        $this->info(($this->option('dry-run') ? 'Would create ' : 'Created ').$created.' legacy request(s).');

        return self::SUCCESS;
    }
}
