<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseCertificate;
use App\Models\CourseMember;
use App\Models\PointsTransaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    /**
     * Default certificate download cost in THB
     */
    public const DEFAULT_DOWNLOAD_COST_THB = 10;

    /**
     * Points per THB (1 THB = 1080 points)
     */
    public const POINTS_PER_THB = 1080;

    /**
     * Instructor share percentage
     */
    public const INSTRUCTOR_SHARE_PERCENT = 90;

    /**
     * System share percentage
     */
    public const SYSTEM_SHARE_PERCENT = 10;

    /**
     * Get download cost in points (default)
     */
    public static function getDefaultDownloadCostPoints(): int
    {
        return self::DEFAULT_DOWNLOAD_COST_THB * self::POINTS_PER_THB;
    }

    /**
     * Get download cost for a specific course
     */
    public function getCourseDownloadCost(Course $course): array
    {
        // Check if free download
        if ($course->certificate_free_download) {
            return [
                'cost_thb' => 0,
                'cost_points' => 0,
                'is_free' => true,
                'is_custom' => false,
            ];
        }

        // Get custom cost or use default
        $costThb = $course->certificate_download_cost ?? self::DEFAULT_DOWNLOAD_COST_THB;
        $costPoints = (int) ($costThb * self::POINTS_PER_THB);

        return [
            'cost_thb' => (float) $costThb,
            'cost_points' => $costPoints,
            'is_free' => false,
            'is_custom' => $course->certificate_download_cost !== null,
        ];
    }

    /**
     * Generate certificate for a course member
     */
    public function generateCertificate(
        CourseMember $member,
        string $type = CourseCertificate::TYPE_COMPLETION
    ): CourseCertificate {
        $course = $member->course;
        $student = $member->user;

        // Determine certificate type based on grade
        if ($type === CourseCertificate::TYPE_COMPLETION) {
            $grade = $member->final_grade ?? $member->draft_grade;
            if (in_array($grade, ['A'])) {
                $type = CourseCertificate::TYPE_EXCELLENCE;
            } elseif (in_array($grade, ['A', 'B+', 'B'])) {
                $type = CourseCertificate::TYPE_ACHIEVEMENT;
            }
        }

        // Create certificate record
        $certificate = CourseCertificate::create([
            'course_member_id' => $member->id,
            'course_id' => $course->id,
            'student_id' => $student->id,
            'type' => $type,
            'grade' => $member->final_grade ?? $member->draft_grade,
            'score' => $member->final_percentage ?? $member->draft_percentage,
            'grade_point' => $member->final_grade_point ?? $member->draft_grade_point,
            'student_name' => $student->name,
            'course_title' => $course->title,
            'instructor_name' => $course->instructor?->name,
            'completion_date' => $member->completed_at ?? now(),
            'issue_date' => now(),
            'status' => CourseCertificate::STATUS_DRAFT,
        ]);

        return $certificate;
    }

    /**
     * Generate PDF for certificate
     */
    public function generatePdf(CourseCertificate $certificate): string
    {
        $data = [
            'certificate' => $certificate,
            'student' => $certificate->student,
            'course' => $certificate->course,
            'qr_code' => $this->generateQrCode($certificate),
        ];

        $pdf = Pdf::loadView('pdf.certificate', $data);
        $pdf->setPaper('a4', 'landscape');

        // Generate filename
        $filename = sprintf(
            'certificates/%s/%s.pdf',
            $certificate->course_id,
            $certificate->certificate_number
        );

        // Save to storage
        Storage::disk('public')->put($filename, $pdf->output());

        // Update certificate record
        $certificate->update([
            'pdf_path' => $filename,
        ]);

        return $filename;
    }

    /**
     * Generate QR code for verification
     */
    protected function generateQrCode(CourseCertificate $certificate): string
    {
        // Generate QR code URL or base64
        $verificationUrl = $certificate->getVerificationUrl();

        // You can use a QR code library here
        // For now, return the URL
        return $verificationUrl;
    }

    /**
     * Issue certificate (finalize and make available)
     */
    public function issueCertificate(CourseCertificate $certificate, User $issuer): CourseCertificate
    {
        // Generate PDF if not exists
        if (! $certificate->pdf_path) {
            $this->generatePdf($certificate);
        }

        $certificate->issue($issuer);

        return $certificate->fresh();
    }

    /**
     * Bulk generate certificates for a course
     */
    public function bulkGenerateCertificates(Course $course, string $type = CourseCertificate::TYPE_COMPLETION): array
    {
        $members = $course->members()
            ->whereIn('completion_status', ['completed'])
            ->whereNotNull('final_grade')
            ->where('final_grade', '!=', 'F')
            ->get();

        $certificates = [];

        foreach ($members as $member) {
            // Check if certificate already exists
            $existing = CourseCertificate::where('course_member_id', $member->id)
                ->where('type', $type)
                ->first();

            if (! $existing) {
                $certificates[] = $this->generateCertificate($member, $type);
            } else {
                $certificates[] = $existing;
            }
        }

        return $certificates;
    }

    /**
     * Verify a certificate by its verification code
     */
    public function verifyCertificate(string $verificationCode): ?array
    {
        $certificate = CourseCertificate::where('verification_code', $verificationCode)->first();

        if (! $certificate) {
            return null;
        }

        return [
            'valid' => $certificate->isValid(),
            'certificate' => [
                'number' => $certificate->certificate_number,
                'type' => $certificate->type,
                'type_label' => $certificate->type_label,
                'status' => $certificate->status,
                'status_label' => $certificate->status_label,
                'student_name' => $certificate->student_name,
                'course_title' => $certificate->course_title,
                'grade' => $certificate->grade,
                'completion_date' => $certificate->completion_date->format('d/m/Y'),
                'issue_date' => $certificate->issue_date->format('d/m/Y'),
                'issued_at' => $certificate->issued_at?->format('d/m/Y H:i'),
            ],
            'revoked' => $certificate->status === CourseCertificate::STATUS_REVOKED,
            'revocation_reason' => $certificate->revocation_reason,
            'expired' => $certificate->expires_at && $certificate->expires_at->isPast(),
        ];
    }

    /**
     * Get student's certificates
     */
    public function getStudentCertificates(User $student): array
    {
        return CourseCertificate::where('student_id', $student->id)
            ->with(['course:id,title,cover_image'])
            ->orderBy('issued_at', 'desc')
            ->get()
            ->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'certificate_number' => $cert->certificate_number,
                    'type' => $cert->type,
                    'type_label' => $cert->type_label,
                    'status' => $cert->status,
                    'status_label' => $cert->status_label,
                    'course' => $cert->course,
                    'grade' => $cert->grade,
                    'completion_date' => $cert->completion_date->format('d/m/Y'),
                    'issue_date' => $cert->issue_date?->format('d/m/Y'),
                    'pdf_url' => $cert->pdf_path ? Storage::url($cert->pdf_path) : null,
                    'is_valid' => $cert->isValid(),
                    'verification_url' => $cert->getVerificationUrl(),
                ];
            })
            ->toArray();
    }

    /**
     * Download certificate (track download count)
     * Note: This is called after payment verification
     */
    public function downloadCertificate(CourseCertificate $certificate): ?string
    {
        if (! $certificate->pdf_path || ! Storage::disk('public')->exists($certificate->pdf_path)) {
            // Regenerate if missing
            $this->generatePdf($certificate);
        }

        $certificate->recordDownload();

        return Storage::disk('public')->path($certificate->pdf_path);
    }

    /**
     * Check if user has enough points to download
     */
    public function canDownload(User $user, Course $course): array
    {
        $costInfo = $this->getCourseDownloadCost($course);
        $requiredPoints = $costInfo['cost_points'];
        $userPoints = $user->pp ?? 0;

        return [
            'can_download' => $costInfo['is_free'] || $userPoints >= $requiredPoints,
            'required_points' => $requiredPoints,
            'user_points' => $userPoints,
            'cost_thb' => $costInfo['cost_thb'],
            'is_free' => $costInfo['is_free'],
            'is_custom' => $costInfo['is_custom'],
            'missing_points' => max(0, $requiredPoints - $userPoints),
        ];
    }

    /**
     * Process download payment - deduct points from user and distribute
     */
    public function processDownloadPayment(User $user, CourseCertificate $certificate): array
    {
        $course = $certificate->course;
        $costInfo = $this->getCourseDownloadCost($course);

        // If free, no payment needed
        if ($costInfo['is_free']) {
            return [
                'success' => true,
                'message' => 'ดาวน์โหลดฟรี',
                'points_deducted' => 0,
                'new_balance' => $user->pp ?? 0,
                'instructor_earned' => 0,
                'system_fee' => 0,
            ];
        }

        $requiredPoints = $costInfo['cost_points'];
        $userPoints = $user->pp ?? 0;

        if ($userPoints < $requiredPoints) {
            return [
                'success' => false,
                'message' => 'แต้มของคุณไม่เพียงพอ',
                'required_points' => $requiredPoints,
                'user_points' => $userPoints,
            ];
        }

        return DB::transaction(function () use ($user, $certificate, $course, $costInfo, $requiredPoints) {
            $instructor = $course->instructor;

            // Calculate distribution
            $instructorPoints = (int) floor($requiredPoints * self::INSTRUCTOR_SHARE_PERCENT / 100);
            $systemPoints = $requiredPoints - $instructorPoints;

            $userBalanceBefore = $user->pp;
            $userBalanceAfter = $userBalanceBefore - $requiredPoints;

            // 1. Deduct from user
            $user->update([
                'pp' => $userBalanceAfter,
                'total_points_spent' => $user->total_points_spent + $requiredPoints,
            ]);

            // Record user spend transaction
            PointsTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'spend',
                'amount' => $requiredPoints,
                'balance_before' => $userBalanceBefore,
                'balance_after' => $userBalanceAfter,
                'source_type' => 'certificate_download',
                'source_id' => $certificate->id,
                'description' => "ค่าดาวน์โหลดใบประกาศ: {$course->title}",
                'metadata' => [
                    'certificate_id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'course_id' => $course->id,
                    'cost_thb' => $costInfo['cost_thb'],
                ],
                'status' => 'completed',
            ]);

            // 2. Add to instructor (90%)
            if ($instructor && $instructorPoints > 0) {
                $instructorBalanceBefore = $instructor->pp;
                $instructorBalanceAfter = $instructorBalanceBefore + $instructorPoints;

                $instructor->update([
                    'pp' => $instructorBalanceAfter,
                    'total_points_earned' => $instructor->total_points_earned + $instructorPoints,
                ]);

                PointsTransaction::create([
                    'user_id' => $instructor->id,
                    'transaction_type' => 'earn',
                    'amount' => $instructorPoints,
                    'balance_before' => $instructorBalanceBefore,
                    'balance_after' => $instructorBalanceAfter,
                    'source_type' => 'certificate_revenue',
                    'source_id' => $certificate->id,
                    'description' => "รายได้จากใบประกาศ: {$course->title}",
                    'metadata' => [
                        'certificate_id' => $certificate->id,
                        'student_id' => $user->id,
                        'student_name' => $user->name,
                        'share_percent' => self::INSTRUCTOR_SHARE_PERCENT,
                    ],
                    'status' => 'completed',
                ]);
            }

            // 3. System share (10%) - record but don't add to anyone
            // System points are tracked in metadata for reporting

            Log::info('Certificate download payment processed', [
                'user_id' => $user->id,
                'certificate_id' => $certificate->id,
                'total_points' => $requiredPoints,
                'instructor_points' => $instructorPoints,
                'system_points' => $systemPoints,
            ]);

            return [
                'success' => true,
                'message' => 'ชำระเงินสำเร็จ',
                'points_deducted' => $requiredPoints,
                'new_balance' => $userBalanceAfter,
                'instructor_earned' => $instructorPoints,
                'system_fee' => $systemPoints,
            ];
        });
    }

    /**
     * Get download pricing info for a course
     */
    public function getDownloadPricing(?Course $course = null): array
    {
        if ($course) {
            $costInfo = $this->getCourseDownloadCost($course);

            return [
                'cost_thb' => $costInfo['cost_thb'],
                'cost_points' => $costInfo['cost_points'],
                'is_free' => $costInfo['is_free'],
                'is_custom' => $costInfo['is_custom'],
                'instructor_share_percent' => self::INSTRUCTOR_SHARE_PERCENT,
                'system_share_percent' => self::SYSTEM_SHARE_PERCENT,
                'exchange_rate' => self::POINTS_PER_THB,
                'default_cost_thb' => self::DEFAULT_DOWNLOAD_COST_THB,
            ];
        }

        return [
            'cost_thb' => self::DEFAULT_DOWNLOAD_COST_THB,
            'cost_points' => self::getDefaultDownloadCostPoints(),
            'is_free' => false,
            'is_custom' => false,
            'instructor_share_percent' => self::INSTRUCTOR_SHARE_PERCENT,
            'system_share_percent' => self::SYSTEM_SHARE_PERCENT,
            'exchange_rate' => self::POINTS_PER_THB,
            'default_cost_thb' => self::DEFAULT_DOWNLOAD_COST_THB,
        ];
    }

    /**
     * Update certificate download settings for a course
     */
    public function updateCourseSettings(Course $course, array $settings): Course
    {
        $data = [];

        if (array_key_exists('certificate_download_cost', $settings)) {
            $cost = $settings['certificate_download_cost'];
            // null = use default, 0 or greater = custom cost
            $data['certificate_download_cost'] = $cost === null ? null : max(0, (float) $cost);
        }

        if (array_key_exists('certificate_free_download', $settings)) {
            $data['certificate_free_download'] = (bool) $settings['certificate_free_download'];
        }

        if (! empty($data)) {
            $course->update($data);
        }

        return $course->fresh();
    }
}
