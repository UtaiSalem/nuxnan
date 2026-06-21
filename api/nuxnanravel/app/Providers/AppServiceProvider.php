<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Student;
use App\Policies\CoursePolicy;
use App\Policies\StudentMasterProfilePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
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
        Gate::define('enrollment.lifecycle', [\App\Policies\EnrollmentPolicy::class, 'lifecycle']);
        Gate::define('enrollment.preview', [\App\Policies\EnrollmentPolicy::class, 'previewRollover']);
        Gate::define('enrollment.plan', [\App\Policies\EnrollmentPolicy::class, 'planRollover']);
        Gate::define('enrollment.commit', [\App\Policies\EnrollmentPolicy::class, 'commitRollover']);
        Gate::define('enrollment.undo', [\App\Policies\EnrollmentPolicy::class, 'undoRollover']);
        Gate::define('enrollment.viewBatches', [\App\Policies\EnrollmentPolicy::class, 'viewBatches']);
    }
}
