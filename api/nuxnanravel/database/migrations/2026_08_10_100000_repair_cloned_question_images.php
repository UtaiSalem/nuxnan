<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Restores question/option images lost by courses that were copied or purchased
 * while CourseMediaService searched the wrong directory for option images.
 *
 * The code fix only protects future copies; existing copies are missing the
 * `question_images` rows entirely, so they are rebuilt here from the source
 * course. See RepairClonedQuestionImages for the pairing rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses') || ! Schema::hasColumn('courses', 'source_course_id')) {
            return;
        }

        if (! Schema::hasTable('question_images')) {
            return;
        }

        Artisan::call('courses:repair-cloned-question-images');
    }

    public function down(): void
    {
        // Intentionally empty. This migration only adds back image rows whose
        // files it also copies; nothing is modified or removed, and deleting the
        // restored images on rollback would re-break the affected courses.
    }
};
