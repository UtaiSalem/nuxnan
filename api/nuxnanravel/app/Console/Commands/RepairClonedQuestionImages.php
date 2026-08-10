<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionImage;
use App\Services\CourseMediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Restores question/option images that were dropped while a course was copied.
 *
 * CourseMediaService used to look for option images under
 * `images/courses/quizzes/questions/options`, but quiz options are actually
 * uploaded next to their question in `images/courses/quizzes/questions`. The
 * copy therefore found nothing and CourseCloneService skipped the image row
 * entirely, so copied/purchased courses lost every image-based option (and any
 * question image whose file sat in an unexpected directory).
 *
 * The clone keeps question and option text verbatim, so the missing rows can be
 * rebuilt by pairing the copy against its source course.
 */
class RepairClonedQuestionImages extends Command
{
    protected $signature = 'courses:repair-cloned-question-images
        {--course=* : Only repair these (copied) course ids — defaults to every course with a source}
        {--from= : Source course id to pair against, overriding source_course_id (requires exactly one --course)}
        {--dry-run : Report what would be restored without writing anything}';

    protected $description = 'Restore question and option images lost when a course was copied or purchased';

    protected CourseMediaService $media;

    protected bool $dryRun = false;

    protected int $restored = 0;

    protected int $unresolved = 0;

    public function handle(CourseMediaService $media): int
    {
        $this->media = $media;
        $this->dryRun = (bool) $this->option('dry-run');

        $courseIds = array_filter((array) $this->option('course'));
        $from = $this->option('from');

        if ($from && count($courseIds) !== 1) {
            $this->error('--from requires exactly one --course.');

            return self::FAILURE;
        }

        $courses = Course::query()
            ->when($courseIds, fn ($q) => $q->whereIn('id', $courseIds))
            ->when(! $from, fn ($q) => $q->whereNotNull('source_course_id'))
            ->orderBy('id')
            ->get();

        if ($courses->isEmpty()) {
            $this->info('No copied courses to repair.');

            return self::SUCCESS;
        }

        foreach ($courses as $course) {
            $source = Course::find($from ?: $course->source_course_id);

            if (! $source) {
                $this->warn("Course {$course->id}: source course not found, skipped.");

                continue;
            }

            $this->info("Course {$course->id} <- {$source->id} ({$course->name})");
            $this->repairCourse($source, $course);
        }

        $this->newLine();
        $this->info(($this->dryRun ? '[dry-run] would restore ' : 'Restored ').$this->restored.' image(s).');

        if ($this->unresolved > 0) {
            $this->warn($this->unresolved.' image(s) could not be restored (source file missing on disk).');
        }

        return self::SUCCESS;
    }

    protected function repairCourse(Course $source, Course $target): void
    {
        foreach ($this->pairBy($source->courseQuizzes, $target->courseQuizzes, 'title') as [$sourceQuiz, $targetQuiz]) {
            $this->repairQuestions('App\Models\CourseQuiz', $sourceQuiz->id, $targetQuiz->id);
        }

        foreach ($this->pairBy($source->courseLessons, $target->courseLessons, 'title') as [$sourceLesson, $targetLesson]) {
            $this->repairQuestions('App\Models\Lesson', $sourceLesson->id, $targetLesson->id);

            foreach ($this->pairBy($sourceLesson->topics, $targetLesson->topics, 'title') as [$sourceTopic, $targetTopic]) {
                $this->repairQuestions('App\Models\Topic', $sourceTopic->id, $targetTopic->id);
            }
        }
    }

    protected function repairQuestions(string $type, int $sourceId, int $targetId): void
    {
        $sourceQuestions = $this->questionsOf($type, $sourceId);
        $targetQuestions = $this->questionsOf($type, $targetId);

        foreach ($this->pairQuestions($sourceQuestions, $targetQuestions) as [$sourceQuestion, $targetQuestion]) {
            $this->copyMissingImages($sourceQuestion, $targetQuestion, "question {$targetQuestion->id}");

            $sourceOptions = $sourceQuestion->options()->orderBy('id')->get();
            $targetOptions = $targetQuestion->options()->orderBy('id')->get();

            // Image-only options have no text to match on, so position is the only
            // safe key — and only when both sides line up exactly.
            if ($sourceOptions->count() !== $targetOptions->count()) {
                continue;
            }

            foreach ($sourceOptions as $index => $sourceOption) {
                $targetOption = $targetOptions[$index];

                // Any text present must agree, or these are not the same option.
                if (trim((string) $sourceOption->text) !== trim((string) $targetOption->text)) {
                    continue;
                }

                $this->copyMissingImages($sourceOption, $targetOption, "option {$targetOption->id}");
            }
        }
    }

    /**
     * @return Collection<int, Question>
     */
    protected function questionsOf(string $type, int $id): Collection
    {
        return Question::where('questionable_type', $type)
            ->where('questionable_id', $id)
            ->orderBy('id')
            ->get();
    }

    /**
     * Pair questions by position when both sides line up, falling back to text.
     *
     * @param  Collection<int, Question>  $sources
     * @param  Collection<int, Question>  $targets
     * @return array<int, array{0: Question, 1: Question}>
     */
    protected function pairQuestions(Collection $sources, Collection $targets): array
    {
        if ($sources->count() === $targets->count()) {
            $pairs = [];

            foreach ($sources as $index => $source) {
                $target = $targets[$index];

                if (trim((string) $source->text) === trim((string) $target->text)) {
                    $pairs[] = [$source, $target];
                }
            }

            return $pairs;
        }

        return $this->pairBy($sources, $targets, 'text');
    }

    /**
     * Pair two sets of records by an identical field value, consuming each
     * source at most once so repeated titles still line up in order.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Collection<int, TModel>  $sources
     * @param  Collection<int, TModel>  $targets
     * @return array<int, array{0: TModel, 1: TModel}>
     */
    protected function pairBy(Collection $sources, Collection $targets, string $field): array
    {
        $available = $sources->sortBy('id')->groupBy(fn ($item) => trim((string) $item->{$field}))
            ->map(fn ($group) => $group->values()->all())
            ->all();

        $pairs = [];

        foreach ($targets->sortBy('id') as $target) {
            $key = trim((string) $target->{$field});

            if (empty($available[$key])) {
                continue;
            }

            $pairs[] = [array_shift($available[$key]), $target];
        }

        return $pairs;
    }

    /**
     * Copy the source record's images onto a target that has none.
     *
     * Targets that already carry images are left untouched — re-running must not
     * duplicate them, and a partially repaired record is not worth the risk of
     * guessing which image is missing.
     */
    protected function copyMissingImages($source, $target, string $label): void
    {
        if ($source->images->isEmpty() || $target->images->isNotEmpty()) {
            return;
        }

        foreach ($source->images as $image) {
            $filename = $image->filename ?: $image->image_url;
            $path = $this->media->locate($filename, CourseMediaService::QUESTION_IMAGE_SEARCH_PATHS);

            if (! $path) {
                $this->unresolved++;
                $this->warn("  {$label}: source file missing on disk ({$filename})");

                continue;
            }

            $this->restored++;
            $this->line("  {$label}: restoring {$filename}");

            if ($this->dryRun) {
                continue;
            }

            $newFilename = $this->media->copyFile($filename, $path);

            if (! $newFilename) {
                $this->restored--;
                $this->unresolved++;

                continue;
            }

            QuestionImage::create([
                'imageable_type' => get_class($target),
                'imageable_id' => $target->id,
                'filename' => $newFilename,
            ]);
        }
    }
}
