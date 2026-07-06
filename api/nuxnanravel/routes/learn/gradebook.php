<?php

use App\Http\Controllers\Api\Learn\Academy\AcademicYearController;
use App\Http\Controllers\Api\Learn\Academy\ClassroomController;
use App\Http\Controllers\Api\Learn\Academy\GradebookController;
use App\Http\Controllers\Api\Learn\Academy\GradeScaleController;
use App\Http\Controllers\Api\Learn\Academy\SubjectController;
use App\Http\Controllers\Api\Learn\Academy\TranscriptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {

    // ============================================
    // Course Gradebook Routes
    // ============================================
    Route::prefix('/courses/{course}')->group(function () {
        // Gradebook main
        Route::get('/gradebook', [GradebookController::class, 'index'])->name('api.course.gradebook.index');

        // Assessments
        Route::post('/gradebook/assessments', [GradebookController::class, 'storeAssessment'])->name('api.course.gradebook.assessments.store');
        Route::put('/gradebook/assessments/{assessment}', [GradebookController::class, 'updateAssessment'])->name('api.course.gradebook.assessments.update');
        Route::delete('/gradebook/assessments/{assessment}', [GradebookController::class, 'destroyAssessment'])->name('api.course.gradebook.assessments.destroy');

        // Scores
        Route::get('/gradebook/assessments/{assessment}/scores', [GradebookController::class, 'getScores'])->name('api.course.gradebook.scores.index');
        Route::put('/gradebook/assessments/{assessment}/scores', [GradebookController::class, 'updateScores'])->name('api.course.gradebook.scores.update');
        Route::put('/gradebook/assessments/{assessment}/scores/{student}', [GradebookController::class, 'updateScore'])->name('api.course.gradebook.scores.updateSingle');

        // Course grades
        Route::get('/gradebook/grades', [GradebookController::class, 'getCourseGrades'])->name('api.course.gradebook.grades.index');
        Route::post('/gradebook/grades/publish', [GradebookController::class, 'publishGrades'])->name('api.course.gradebook.grades.publish');

        // Student view
        Route::get('/gradebook/my-grades', [GradebookController::class, 'getStudentGrades'])->name('api.course.gradebook.my-grades');
    });

    // ============================================
    // Academy Gradebook & Academic Management Routes
    // ============================================
    Route::prefix('/academies/{academy}')->group(function () {

        // Academic Years
        Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('api.academy.academic-years.index');
        Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('api.academy.academic-years.store');
        Route::get('/academic-years/current', [AcademicYearController::class, 'getCurrent'])->name('api.academy.academic-years.current');
        Route::put('/academic-years/{academicYear}', [AcademicYearController::class, 'update'])->name('api.academy.academic-years.update');
        Route::delete('/academic-years/{academicYear}', [AcademicYearController::class, 'destroy'])->name('api.academy.academic-years.destroy');

        // Semesters
        Route::post('/academic-years/{academicYear}/semesters', [AcademicYearController::class, 'storeSemester'])->name('api.academy.semesters.store');
        Route::put('/academic-years/{academicYear}/semesters/{semester}', [AcademicYearController::class, 'updateSemester'])->name('api.academy.semesters.update');

        // Classrooms
        Route::get('/classrooms', [ClassroomController::class, 'index'])->name('api.academy.classrooms.index');
        Route::post('/classrooms', [ClassroomController::class, 'store'])->name('api.academy.classrooms.store');
        Route::get('/classrooms/grade-levels', [ClassroomController::class, 'getGradeLevels'])->name('api.academy.classrooms.grade-levels');
        Route::get('/classrooms/{classroom}', [ClassroomController::class, 'show'])->name('api.academy.classrooms.show');
        Route::put('/classrooms/{classroom}', [ClassroomController::class, 'update'])->name('api.academy.classrooms.update');
        Route::delete('/classrooms/{classroom}', [ClassroomController::class, 'destroy'])->name('api.academy.classrooms.destroy');
        Route::post('/classrooms/{classroom}/students', [ClassroomController::class, 'addStudents'])->name('api.academy.classrooms.students.add');
        Route::delete('/classrooms/{classroom}/students/{student}', [ClassroomController::class, 'removeStudent'])->name('api.academy.classrooms.students.remove');
        Route::patch('/classrooms/{classroom}/students/{student}/number', [ClassroomController::class, 'updateStudentNumber'])->name('api.academy.classrooms.students.updateNumber');

        // Subjects
        Route::get('/subjects', [SubjectController::class, 'index'])->name('api.academy.subjects.index');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('api.academy.subjects.store');
        Route::get('/subjects/groups', [SubjectController::class, 'getGroups'])->name('api.academy.subjects.groups');
        Route::get('/subjects/{subject}', [SubjectController::class, 'show'])->name('api.academy.subjects.show');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('api.academy.subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('api.academy.subjects.destroy');

        // Grade Scales
        Route::get('/grade-scales', [GradeScaleController::class, 'index'])->name('api.academy.grade-scales.index');
        Route::post('/grade-scales', [GradeScaleController::class, 'store'])->name('api.academy.grade-scales.store');
        Route::get('/grade-scales/{gradeScale}', [GradeScaleController::class, 'show'])->name('api.academy.grade-scales.show');
        Route::put('/grade-scales/{gradeScale}', [GradeScaleController::class, 'update'])->name('api.academy.grade-scales.update');
        Route::delete('/grade-scales/{gradeScale}', [GradeScaleController::class, 'destroy'])->name('api.academy.grade-scales.destroy');

        // Assessment Categories
        Route::get('/assessment-categories', [GradeScaleController::class, 'getCategories'])->name('api.academy.assessment-categories.index');
        Route::post('/assessment-categories', [GradeScaleController::class, 'storeCategory'])->name('api.academy.assessment-categories.store');

        // Transcripts
        Route::post('/transcripts/semester/generate', [TranscriptController::class, 'generateSemesterTranscript'])->name('api.academy.transcripts.semester.generate');
        Route::post('/transcripts/semester/publish', [TranscriptController::class, 'publishSemesterTranscripts'])->name('api.academy.transcripts.semester.publish');
        Route::post('/transcripts/annual/generate', [TranscriptController::class, 'generateAnnualTranscript'])->name('api.academy.transcripts.annual.generate');
        Route::get('/transcripts/overview', [TranscriptController::class, 'getAcademyTranscriptOverview'])->name('api.academy.transcripts.overview');
    });

    // ============================================
    // Student Transcript Routes
    // ============================================
    Route::prefix('/students/{student}')->group(function () {
        Route::get('/transcripts/semester', [TranscriptController::class, 'getSemesterTranscript'])->name('api.student.transcripts.semester');
        Route::get('/transcripts/annual', [TranscriptController::class, 'getAnnualTranscript'])->name('api.student.transcripts.annual');
        Route::get('/transcripts/{transcript}/pdf', [TranscriptController::class, 'downloadTranscriptPdf'])->name('api.student.transcripts.pdf');
    });

    // ============================================
    // Current User Student Routes (me)
    // ============================================
    Route::prefix('/students/me')->group(function () {
        Route::get('/transcripts', [TranscriptController::class, 'getMyTranscripts'])->name('api.student.me.transcripts');
        Route::get('/transcripts/{transcript}/pdf', [TranscriptController::class, 'downloadMyTranscriptPdf'])->name('api.student.me.transcripts.pdf');
        Route::get('/card', [ClassroomController::class, 'getMyStudentCard'])->name('api.student.me.card');
    });

    // ============================================
    // Academy Students Management Routes
    // ============================================
    Route::prefix('/academies/{academy}/students')->group(function () {
        Route::get('/', [ClassroomController::class, 'getAllStudents'])->name('api.academy.students.index');
        Route::get('/{student}', [ClassroomController::class, 'getStudent'])->name('api.academy.students.show');
    });
});
