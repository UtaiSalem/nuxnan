<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReconcileStudentCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:reconcile-cards {academy} {--fix} {--dry-run} {--report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile and repair data drift between student cards and master records';

    private function numericGradeLevel(?string $grade): int
    {
        if (! $grade) {
            return 0;
        }
        if (! preg_match('/(\d+)\s*$/u', trim($grade), $matches)) {
            return 0;
        }

        return (int) $matches[1];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $academyId = $this->argument('academy');
        $isFix = $this->option('fix');
        $isDryRun = $this->option('dry-run');
        $isReport = $this->option('report') || ! $isFix;

        $academy = Academy::find($academyId);
        if (! $academy) {
            $this->error("Academy not found: {$academyId}");

            return 1;
        }

        $year = AcademicYear::where('academy_id', $academy->id)->where('is_current', 1)->first();
        if (! $year) {
            $this->error('Current academic year not found');

            return 1;
        }

        $this->info("Reconciling cards for Academy: {$academy->name} | Year: {$year->name}");

        // 1. Backfill student_id for orphan cards if possible
        $orphans = StudentCard::where('academy_id', $academy->id)
            ->whereNull('student_id')
            ->get();

        $resolvedOrphans = 0;
        $unresolvedOrphans = 0;

        foreach ($orphans as $orphan) {
            $student = null;
            if ($orphan->student_number) {
                $student = Student::where('academy_id', $academy->id)
                    ->where('student_id', $orphan->student_number)
                    ->first();
            }
            if (! $student && $orphan->national_id) {
                $student = Student::where('academy_id', $academy->id)
                    ->where('citizen_id', $orphan->national_id)
                    ->first();
            }

            if ($student) {
                if ($isFix) {
                    if (! $isDryRun) {
                        $orphan->update(['student_id' => $student->id]);
                    }
                    $resolvedOrphans++;
                } else {
                    $resolvedOrphans++;
                }
            } else {
                $unresolvedOrphans++;
            }
        }

        $this->line("Orphan cards: Resolved: {$resolvedOrphans}, Unresolved: {$unresolvedOrphans}");

        // 2. Query active student cards
        $cards = StudentCard::where('academy_id', $academy->id)
            ->where('student_status', 'active')
            ->whereNotNull('student_id')
            ->get();

        $drifts = [];
        $fixedCount = 0;

        foreach ($cards as $card) {
            $student = Student::find($card->student_id);
            if (! $student) {
                continue;
            }

            $enrollment = ClassroomStudent::select('classroom_students.*', 'classrooms.grade_level', 'classrooms.section')
                ->join('classrooms', 'classroom_students.classroom_id', '=', 'classrooms.id')
                ->where('classroom_students.student_id', $student->id)
                ->where('classroom_students.academic_year_id', $year->id)
                ->where('classroom_students.status', 'active')
                ->first();

            $drift = [];

            // Compare: student_number
            if ($card->student_number !== $student->student_id) {
                $drift['student_number'] = ['card' => $card->student_number, 'master' => $student->student_id];
            }
            // Compare: national_id
            if ($card->national_id !== $student->citizen_id) {
                $drift['national_id'] = ['card' => $card->national_id, 'master' => $student->citizen_id];
            }
            // Compare: first_name_en
            if ($card->first_name_english !== $student->first_name_en) {
                $drift['first_name_english'] = ['card' => $card->first_name_english, 'master' => $student->first_name_en];
            }
            // Compare: birth_date
            $cardDob = $card->birth_date ? Carbon::parse($card->birth_date)->format('Y-m-d') : null;
            $studentDob = $student->date_of_birth ? Carbon::parse($student->date_of_birth)->format('Y-m-d') : null;
            if ($cardDob !== $studentDob) {
                $drift['birth_date'] = ['card' => $cardDob, 'master' => $studentDob];
            }
            // Compare: first_name_thai
            if ($card->first_name_thai !== $student->first_name_th) {
                $drift['first_name_thai'] = ['card' => $card->first_name_thai, 'master' => $student->first_name_th];
            }
            // Compare: last_name_thai
            if ($card->last_name_thai !== $student->last_name_th) {
                $drift['last_name_thai'] = ['card' => $card->last_name_thai, 'master' => $student->last_name_th];
            }
            // Compare: profile_image
            if ($card->profile_image !== $student->profile_image) {
                $drift['profile_image'] = ['card' => $card->profile_image, 'master' => $student->profile_image];
            }

            // Compare enrollment grade / section
            if ($enrollment) {
                $expectedLevel = $this->numericGradeLevel($enrollment->grade_level);
                $expectedSection = (int) $enrollment->section;
                if ((int) $card->class_level !== $expectedLevel) {
                    $drift['class_level'] = ['card' => $card->class_level, 'master' => $expectedLevel];
                }
                if ((int) $card->class_section !== $expectedSection) {
                    $drift['class_section'] = ['card' => $card->class_section, 'master' => $expectedSection];
                }
            }

            if (! empty($drift)) {
                $drifts[$card->id] = [
                    'card_id' => $card->id,
                    'name' => $card->full_name_thai ?? "{$student->first_name_th} {$student->last_name_th}",
                    'drift' => $drift,
                ];

                if ($isFix) {
                    $cardUpdates = [];
                    $studentUpdates = [];

                    foreach ($drift as $field => $values) {
                        // If master field is null/empty but card has it, backfill master
                        if (empty($values['master']) && ! empty($values['card'])) {
                            if ($field === 'first_name_english') {
                                $studentUpdates['first_name_en'] = $values['card'];
                            }
                            if ($field === 'birth_date') {
                                $studentUpdates['date_of_birth'] = $values['card'];
                            }
                            if ($field === 'national_id') {
                                $studentUpdates['citizen_id'] = $values['card'];
                            }
                            if ($field === 'student_number') {
                                $studentUpdates['student_id'] = $values['card'];
                            }
                        } else {
                            // Overwrite card with master
                            if ($field === 'first_name_english') {
                                $cardUpdates['first_name_english'] = $values['master'];
                            }
                            if ($field === 'birth_date') {
                                $cardUpdates['birth_date'] = $values['master'];
                                $cardUpdates['birth_date_string'] = $values['master'] ? Carbon::parse($values['master'])->format('d/m/Y') : null;
                            }
                            if ($field === 'national_id') {
                                $cardUpdates['national_id'] = $values['master'];
                            }
                            if ($field === 'student_number') {
                                $cardUpdates['student_number'] = $values['master'];
                            }
                            if ($field === 'first_name_thai') {
                                $cardUpdates['first_name_thai'] = $values['master'];
                            }
                            if ($field === 'last_name_thai') {
                                $cardUpdates['last_name_thai'] = $values['master'];
                            }
                            if ($field === 'profile_image') {
                                $cardUpdates['profile_image'] = $values['master'];
                            }
                            if ($field === 'class_level') {
                                $cardUpdates['class_level'] = $values['master'];
                            }
                            if ($field === 'class_section') {
                                $cardUpdates['class_section'] = $values['master'];
                            }
                        }
                    }

                    if (! empty($cardUpdates) && ! $isDryRun) {
                        if (isset($cardUpdates['class_level']) || isset($cardUpdates['class_section'])) {
                            $level = $cardUpdates['class_level'] ?? $card->class_level;
                            $section = $cardUpdates['class_section'] ?? $card->class_section;
                            $cardUpdates['level_and_room'] = "{$level}/{$section}";
                        }
                        if (isset($cardUpdates['first_name_thai']) || isset($cardUpdates['last_name_thai'])) {
                            $first = $cardUpdates['first_name_thai'] ?? $card->first_name_thai;
                            $last = $cardUpdates['last_name_thai'] ?? $card->last_name_thai;
                            $title = $card->title_name ?? '';
                            $cardUpdates['full_name_thai'] = trim("{$title} {$first} {$last}");
                        }
                        $card->update($cardUpdates);
                    }

                    if (! empty($studentUpdates) && ! $isDryRun) {
                        $student->update($studentUpdates);
                    }

                    $fixedCount++;
                }
            }
        }

        if ($isReport) {
            $this->info("\n--- Data Drift Report ---");
            $this->line('Total Cards Checked: '.count($cards));
            $this->line('Cards with Drift: '.count($drifts));

            foreach ($drifts as $item) {
                $this->warn("\nCard ID: {$item['card_id']} | Name: {$item['name']}");
                foreach ($item['drift'] as $field => $vals) {
                    $this->line("  * {$field}: Card = '{$vals['card']}', Master = '{$vals['master']}'");
                }
            }
        }

        if ($isFix) {
            $action = $isDryRun ? 'Would have fixed' : 'Fixed';
            $this->info("\n{$action} {$fixedCount} cards with data drift.");
        }

        return 0;
    }
}
