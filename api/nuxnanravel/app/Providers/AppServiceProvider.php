<?php

namespace App\Providers;

use App\Models\Academy;
use App\Models\AcademyDonate;
use App\Models\AcademyPointWithdrawalRequest;
use App\Models\AcademyPost;
use App\Models\AcademyPostComment;
use App\Models\AcademyPostLike;
use App\Models\AssignmentAnswer;
use App\Models\ClassroomStudent;
use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\CourseMember;
use App\Models\CoursePointWithdrawalRequest;
use App\Models\EventRegistration;
use App\Models\Student;
use App\Models\WalletTransaction;
use App\Observers\AcademyPostCommentObserver;
use App\Observers\AcademyPostLikeObserver;
use App\Observers\AcademyPostObserver;
use App\Observers\AssignmentAnswerObserver;
use App\Observers\ClassroomStudentObserver;
use App\Observers\CourseMemberObserver;
use App\Observers\EventRegistrationObserver;
use App\Observers\StudentObserver;
use App\Policies\AcademyDonatePolicy;
use App\Policies\AcademyPointWithdrawalPolicy;
use App\Policies\CourseDonatePolicy;
use App\Policies\CoursePointWithdrawalPolicy;
use App\Policies\CoursePolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\StudentMasterProfilePolicy;
use App\Policies\WithdrawalPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        $routeKey = static function (Request $request, string $parameter): string {
            $value = $request->route($parameter);

            return (string) (is_object($value) && method_exists($value, 'getKey')
                ? $value->getKey()
                : $value);
        };

        // Ceilings cover normal bursts while leaving room above the expected ~3 requests/minute/station average.
        // Do not key these limits by IP: all students in a school may share one NAT address.
        RateLimiter::for('election-issue', static fn (Request $request) => Limit::perMinute(60)->by($routeKey($request, 'station')));
        RateLimiter::for('election-lookup', static fn (Request $request) => Limit::perMinute(120)->by($routeKey($request, 'station')));
        RateLimiter::for('election-cast', static fn (Request $request) => Limit::perMinute(60)->by($routeKey($request, 'election').'|'.(string) $request->user('api')?->getAuthIdentifier()));
        RateLimiter::for('election-candidates', static fn (Request $request) => Limit::perMinute(60)->by($routeKey($request, 'election').'|'.(string) $request->user('api')?->getAuthIdentifier()));

        Schema::defaultStringLength(191);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(CourseDonate::class, CourseDonatePolicy::class);
        Gate::policy(Academy::class, AcademyDonatePolicy::class);
        Gate::policy(AcademyDonate::class, AcademyDonatePolicy::class);
        Gate::policy(CoursePointWithdrawalRequest::class, CoursePointWithdrawalPolicy::class);
        Gate::policy(AcademyPointWithdrawalRequest::class, AcademyPointWithdrawalPolicy::class);
        Gate::policy(WalletTransaction::class, WithdrawalPolicy::class);
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
