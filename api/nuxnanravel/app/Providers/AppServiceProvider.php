<?php

namespace App\Providers;

use App\Models\AcademyPost;
use App\Models\AcademyPostComment;
use App\Models\AcademyPostLike;
use App\Models\AssignmentAnswer;
use App\Models\ClassroomStudent;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\EventRegistration;
use App\Models\Student;
use App\Observers\AcademyPostCommentObserver;
use App\Observers\AcademyPostLikeObserver;
use App\Observers\AcademyPostObserver;
use App\Observers\AssignmentAnswerObserver;
use App\Observers\ClassroomStudentObserver;
use App\Observers\CourseMemberObserver;
use App\Observers\EventRegistrationObserver;
use App\Observers\StudentObserver;
use App\Policies\CoursePolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\StudentMasterProfilePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(TelescopeApplicationServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Student::class, StudentMasterProfilePolicy::class);

        // Enrollment & Rollover Policy Gates
        Gate::define('student.intake', [EnrollmentPolicy::class, 'intake']);
        Gate::define('student.import', [EnrollmentPolicy::class, 'import']);
        Gate::define('enrollment.lifecycle', [EnrollmentPolicy::class, 'lifecycle']);
        Gate::define('enrollment.preview', [EnrollmentPolicy::class, 'previewRollover']);
        Gate::define('enrollment.plan', [EnrollmentPolicy::class, 'planRollover']);
        Gate::define('enrollment.commit', [EnrollmentPolicy::class, 'commitRollover']);
        Gate::define('enrollment.undo', [EnrollmentPolicy::class, 'undoRollover']);
        Gate::define('enrollment.viewBatches', [EnrollmentPolicy::class, 'viewBatches']);

        // Register Gamification Observers
        AcademyPost::observe(AcademyPostObserver::class);
        AcademyPostLike::observe(AcademyPostLikeObserver::class);
        AcademyPostComment::observe(AcademyPostCommentObserver::class);
        CourseMember::observe(CourseMemberObserver::class);
        EventRegistration::observe(EventRegistrationObserver::class);
        AssignmentAnswer::observe(AssignmentAnswerObserver::class);
        Student::observe(StudentObserver::class);
        ClassroomStudent::observe(ClassroomStudentObserver::class);
    }
}
