<?php

use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CourseCompletionController;
use App\Http\Controllers\Api\CourseReportController;
use App\Http\Controllers\Api\ExamEligibilityController;
use App\Http\Controllers\Api\GradeAppealController;
use App\Http\Controllers\Api\InstructorDashboardController;
use App\Http\Controllers\Api\RemediationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Course Completion API Routes
|--------------------------------------------------------------------------
|
| ระบบจบวิชา สิทธิ์สอบ อุทธรณ์ และใบประกาศ
|
*/

Route::middleware(['auth:api'])->group(function () {

    // ===========================================
    // Exam Eligibility (สิทธิ์สอบ)
    // ===========================================
    Route::prefix('courses/{course}')->group(function () {

        // Student: Check my eligibility
        Route::get('eligibility', [ExamEligibilityController::class, 'getStatus']);
        Route::get('eligibility/my-status', [ExamEligibilityController::class, 'getMyStatus']);
        Route::get('eligibility/reading-progress', [ExamEligibilityController::class, 'readingProgress']);

        // Student: Request unlock
        Route::post('eligibility/unlock/self', [ExamEligibilityController::class, 'requestSelfUnlock']);
        Route::post('eligibility/unlock/points', [ExamEligibilityController::class, 'requestPointsUnlock']);
        Route::post('eligibility/unlock/reading', [ExamEligibilityController::class, 'requestReadingUnlock']);
        Route::post('eligibility/unlock/appeal', [ExamEligibilityController::class, 'requestAppealUnlock']);

        // Admin: Eligibility management
        Route::get('eligibility/summary', [ExamEligibilityController::class, 'getCourseSummary']);
        Route::post('eligibility/refresh', [ExamEligibilityController::class, 'refreshCourseEligibility']);
        Route::get('eligibility/pending-requests', [ExamEligibilityController::class, 'getPendingRequests']);
        Route::get('eligibility/audit-log', [ExamEligibilityController::class, 'getAuditLog']);
        Route::post('eligibility/members/{member}/unlock', [ExamEligibilityController::class, 'adminUnlock']);
        Route::post('eligibility/bulk-unlock', [ExamEligibilityController::class, 'bulkUnlock']);
        Route::post('eligibility/bulk-revoke', [ExamEligibilityController::class, 'bulkRevoke']);
    });

    // Update reading progress
    Route::patch('eligibility-overrides/{override}/reading-progress', [ExamEligibilityController::class, 'updateReadingProgress']);

    // Appeal: Add evidence
    Route::patch('eligibility-overrides/{override}/appeal-evidence', [ExamEligibilityController::class, 'addAppealEvidence']);

    // Admin: Approve/Reject requests
    Route::post('eligibility-overrides/{override}/approve', [ExamEligibilityController::class, 'approveRequest']);
    Route::post('eligibility-overrides/{override}/reject', [ExamEligibilityController::class, 'rejectRequest']);

    // ===========================================
    // Course Completion & Grading (จบวิชา/เกรด)
    // ===========================================
    Route::prefix('courses/{course}/completion')->group(function () {

        // Summary
        Route::get('summary', [CourseCompletionController::class, 'getSummary']);

        // Grading workflow
        Route::post('start-grading', [CourseCompletionController::class, 'startGrading']);
        Route::get('preview-grades', [CourseCompletionController::class, 'previewGrades']);
        Route::post('publish-grades', [CourseCompletionController::class, 'publishGrades']);
        Route::post('finalize', [CourseCompletionController::class, 'finalizeGrades']);
        Route::post('reopen', [CourseCompletionController::class, 'reopenGrading']);
        Route::post('archive', [CourseCompletionController::class, 'archiveCourse']);

        // Student: Get/Accept grade
        Route::get('my-grade', [CourseCompletionController::class, 'getMyGrade']);
        Route::post('accept-grade', [CourseCompletionController::class, 'acceptGrade']);

        // Admin: Override grade
        Route::patch('members/{member}/grade', [CourseCompletionController::class, 'overrideGrade']);
        Route::get('members/{member}/grade-history', [CourseCompletionController::class, 'getGradeHistory']);

        // Export
        Route::get('export', [CourseCompletionController::class, 'exportGrades']);
    });

    // ===========================================
    // Grade Appeals (อุทธรณ์เกรด)
    // ===========================================

    // Student: My appeals
    Route::get('grade-appeals/my', [GradeAppealController::class, 'myAppeals']);

    Route::prefix('courses/{course}/appeals')->group(function () {
        Route::get('/', [GradeAppealController::class, 'index']);
        Route::post('/', [GradeAppealController::class, 'store']);
        Route::get('statistics', [GradeAppealController::class, 'getStatistics']);
    });

    Route::prefix('grade-appeals/{appeal}')->group(function () {
        Route::get('/', [GradeAppealController::class, 'show']);
        Route::post('start-review', [GradeAppealController::class, 'startReview']);
        Route::post('approve', [GradeAppealController::class, 'approve']);
        Route::post('reject', [GradeAppealController::class, 'reject']);
        Route::post('withdraw', [GradeAppealController::class, 'withdraw']);
    });

    // ===========================================
    // Remediation (แก้ตัว)
    // ===========================================

    // Student: My remediation enrollments
    Route::get('remediation/my-enrollments', [RemediationController::class, 'myEnrollments']);

    Route::prefix('courses/{course}/remediation')->group(function () {
        Route::get('/', [RemediationController::class, 'index']);
        Route::post('/', [RemediationController::class, 'store']);

        Route::prefix('{session}')->group(function () {
            Route::get('/', [RemediationController::class, 'show']);
            Route::patch('/', [RemediationController::class, 'update']);
            Route::post('open', [RemediationController::class, 'open']);
            Route::post('start', [RemediationController::class, 'start']);
            Route::post('complete', [RemediationController::class, 'complete']);
            Route::post('enroll', [RemediationController::class, 'enroll']);
            Route::post('bulk-enroll', [RemediationController::class, 'bulkEnroll']);
            Route::post('bulk-grade', [RemediationController::class, 'bulkGrade']);
        });
    });

    Route::prefix('remediation-enrollments/{enrollment}')->group(function () {
        Route::post('submit', [RemediationController::class, 'submitWork']);
        Route::post('mark-attendance', [RemediationController::class, 'markAttendance']);
        Route::post('grade', [RemediationController::class, 'grade']);
        Route::post('cancel', [RemediationController::class, 'cancelEnrollment']);
    });

    // ===========================================
    // Certificates (ใบประกาศ)
    // ===========================================

    // Student: My certificates
    Route::get('certificates/my', [CertificateController::class, 'myCertificates']);

    Route::prefix('courses/{course}/certificates')->group(function () {
        Route::get('/', [CertificateController::class, 'index']);
        Route::get('eligible', [CertificateController::class, 'getEligibleStudents']);
        Route::post('generate', [CertificateController::class, 'generate']);
        Route::post('issue', [CertificateController::class, 'issueForMembers']);
        Route::post('issue-all', [CertificateController::class, 'issueAllEligible']);
        Route::post('bulk-issue', [CertificateController::class, 'bulkIssue']);
        Route::get('statistics', [CertificateController::class, 'getStatistics']);
        Route::get('settings', [CertificateController::class, 'getSettings']);
        Route::patch('settings', [CertificateController::class, 'updateSettings']);
        Route::get('pricing', [CertificateController::class, 'getDownloadPricing']);
    });

    Route::prefix('certificates/{certificate}')->group(function () {
        Route::post('issue', [CertificateController::class, 'issue']);
        Route::get('download', [CertificateController::class, 'download']);
        Route::get('check-download', [CertificateController::class, 'checkDownload']);
        Route::post('revoke', [CertificateController::class, 'revoke']);
        Route::post('regenerate-pdf', [CertificateController::class, 'regeneratePdf']);
    });

    // Certificate pricing info (default)
    Route::get('certificates/pricing', [CertificateController::class, 'getDownloadPricing']);

    // ===========================================
    // Instructor Dashboard (แดชบอร์ดผู้สอน)
    // ===========================================
    Route::prefix('courses/{course}/instructor-dashboard')->group(function () {
        Route::get('/', [InstructorDashboardController::class, 'courseDashboard']);
        Route::get('trends', [InstructorDashboardController::class, 'getPerformanceTrends']);
        Route::get('at-risk', [InstructorDashboardController::class, 'getAtRiskStudents']);
        Route::get('top-performers', [InstructorDashboardController::class, 'getTopPerformers']);
    });

    // ===========================================
    // Course Reports (รายงานสรุปการเรียน)
    // ===========================================
    Route::prefix('courses/{course}/reports')->group(function () {
        Route::get('types', [CourseReportController::class, 'getReportTypes']);
        Route::get('grades', [CourseReportController::class, 'exportGrades']);
        Route::get('attendance', [CourseReportController::class, 'exportAttendance']);
        Route::get('progress', [CourseReportController::class, 'exportProgress']);
        Route::get('full', [CourseReportController::class, 'exportFull']);
        Route::get('certificates', [CourseReportController::class, 'exportCertificates']);
    });
});

// Public: Verify certificate (no auth required)
Route::get('certificates/verify/{verificationCode}', [CertificateController::class, 'verify']);
