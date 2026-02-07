<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCertificate;
use App\Services\CertificateService;
use App\Services\GradingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    protected CertificateService $certificateService;
    protected GradingNotificationService $notificationService;

    public function __construct(
        CertificateService $certificateService,
        GradingNotificationService $notificationService
    ) {
        $this->certificateService = $certificateService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get certificates for a course (Admin)
     */
    public function index(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $query = CourseCertificate::where('course_id', $course->id)
            ->with('student:id,name,avatar,email');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $certificates = $query->orderBy('issued_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $certificates,
        ]);
    }

    /**
     * Get my certificates
     */
    public function myCertificates(Request $request): JsonResponse
    {
        $certificates = $this->certificateService->getStudentCertificates($request->user());

        return response()->json([
            'success' => true,
            'data' => $certificates,
        ]);
    }

    /**
     * Generate certificates for a course
     */
    public function generate(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        if ($course->finalization_status !== 'finalized') {
            return response()->json([
                'success' => false,
                'message' => 'ต้องยืนยันเกรดก่อนออกใบประกาศ',
            ], 400);
        }

        $request->validate([
            'type' => 'nullable|in:completion,achievement,participation,excellence',
        ]);

        $type = $request->type ?? CourseCertificate::TYPE_COMPLETION;
        $certificates = $this->certificateService->bulkGenerateCertificates($course, $type);

        return response()->json([
            'success' => true,
            'message' => sprintf('สร้างใบประกาศ %d ใบ', count($certificates)),
            'data' => [
                'count' => count($certificates),
                'certificates' => $certificates,
            ],
        ]);
    }

    /**
     * Issue a single certificate
     */
    public function issue(Request $request, CourseCertificate $certificate): JsonResponse
    {
        $this->authorize('manage', $certificate->course);

        if ($certificate->status !== CourseCertificate::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'ใบประกาศนี้ออกแล้ว',
            ], 400);
        }

        $certificate = $this->certificateService->issueCertificate($certificate, $request->user());

        // Notify student
        $this->notificationService->notifyCertificateIssued($certificate);

        return response()->json([
            'success' => true,
            'message' => 'ออกใบประกาศเรียบร้อย',
            'data' => $certificate,
        ]);
    }

    /**
     * Bulk issue certificates
     */
    public function bulkIssue(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $request->validate([
            'certificate_ids' => 'required|array',
            'certificate_ids.*' => 'exists:course_certificates,id',
        ]);

        $issued = 0;
        $issuedCertificates = collect();

        foreach ($request->certificate_ids as $id) {
            $certificate = CourseCertificate::find($id);
            if ($certificate && $certificate->course_id === $course->id && $certificate->status === CourseCertificate::STATUS_DRAFT) {
                $certificate = $this->certificateService->issueCertificate($certificate, $request->user());
                $issuedCertificates->push($certificate);
                $issued++;
            }
        }

        // Bulk notify students
        if ($issuedCertificates->isNotEmpty()) {
            $this->notificationService->notifyBulkCertificatesIssued($issuedCertificates, $request->user());
        }

        return response()->json([
            'success' => true,
            'message' => sprintf('ออกใบประกาศ %d ใบ', $issued),
            'data' => ['issued_count' => $issued],
        ]);
    }

    /**
     * View/Download certificate (with points deduction)
     */
    public function download(Request $request, CourseCertificate $certificate)
    {
        // Check access
        $user = $request->user();
        if ($certificate->student_id !== $user->id) {
            $this->authorize('manage', $certificate->course);
            // Admin/Instructor can download without payment
            $isAdmin = true;
        } else {
            $isAdmin = false;
        }

        if ($certificate->status !== CourseCertificate::STATUS_ISSUED) {
            return response()->json([
                'success' => false,
                'message' => 'ใบประกาศยังไม่ได้ออก',
            ], 400);
        }

        // For students: process payment
        if (!$isAdmin) {
            $paymentResult = $this->certificateService->processDownloadPayment($user, $certificate);
            
            if (!$paymentResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $paymentResult['message'],
                    'data' => [
                        'required_points' => $paymentResult['required_points'] ?? null,
                        'user_points' => $paymentResult['user_points'] ?? null,
                    ],
                ], 402); // Payment Required
            }
        }

        $path = $this->certificateService->downloadCertificate($certificate);

        if (!$path || !file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบไฟล์ใบประกาศ',
            ], 404);
        }

        return response()->download($path, $certificate->certificate_number . '.pdf');
    }
    
    /**
     * Check download eligibility and pricing
     */
    public function checkDownload(Request $request, CourseCertificate $certificate): JsonResponse
    {
        $user = $request->user();
        
        // Check ownership
        if ($certificate->student_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์ดาวน์โหลด',
            ], 403);
        }
        
        if ($certificate->status !== CourseCertificate::STATUS_ISSUED) {
            return response()->json([
                'success' => false,
                'message' => 'ใบประกาศยังไม่ได้ออก',
            ], 400);
        }
        
        $course = $certificate->course;
        $canDownload = $this->certificateService->canDownload($user, $course);
        $pricing = $this->certificateService->getDownloadPricing($course);
        
        return response()->json([
            'success' => true,
            'data' => array_merge($canDownload, [
                'pricing' => $pricing,
                'certificate' => [
                    'id' => $certificate->id,
                    'number' => $certificate->certificate_number,
                    'course_title' => $certificate->course_title,
                ],
            ]),
        ]);
    }
    
    /**
     * Get download pricing info for a course
     */
    public function getDownloadPricing(Request $request, ?Course $course = null): JsonResponse
    {
        $pricing = $this->certificateService->getDownloadPricing($course);
        
        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }
    
    /**
     * Get certificate settings for a course
     */
    public function getSettings(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);
        
        $pricing = $this->certificateService->getDownloadPricing($course);
        
        return response()->json([
            'success' => true,
            'data' => [
                'certificate_download_cost' => $course->certificate_download_cost,
                'certificate_free_download' => (bool) $course->certificate_free_download,
                'pricing' => $pricing,
            ],
        ]);
    }
    
    /**
     * Update certificate settings for a course
     */
    public function updateSettings(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);
        
        $validated = $request->validate([
            'certificate_download_cost' => 'nullable|numeric|min:0|max:10000',
            'certificate_free_download' => 'nullable|boolean',
        ]);
        
        $course = $this->certificateService->updateCourseSettings($course, $validated);
        $pricing = $this->certificateService->getDownloadPricing($course);
        
        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่าสำเร็จ',
            'data' => [
                'certificate_download_cost' => $course->certificate_download_cost,
                'certificate_free_download' => (bool) $course->certificate_free_download,
                'pricing' => $pricing,
            ],
        ]);
    }

    /**
     * Verify a certificate (public)
     */
    public function verify(Request $request, string $verificationCode): JsonResponse
    {
        $result = $this->certificateService->verifyCertificate($verificationCode);

        if (!$result) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'ไม่พบใบประกาศ',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Revoke a certificate
     */
    public function revoke(Request $request, CourseCertificate $certificate): JsonResponse
    {
        $this->authorize('manage', $certificate->course);

        if ($certificate->status !== CourseCertificate::STATUS_ISSUED) {
            return response()->json([
                'success' => false,
                'message' => 'เฉพาะใบประกาศที่ออกแล้ว',
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $certificate->revoke($request->user(), $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'เพิกถอนใบประกาศเรียบร้อย',
            'data' => $certificate->fresh(),
        ]);
    }

    /**
     * Get certificate statistics for a course
     */
    public function getStatistics(Request $request, Course $course): JsonResponse
    {
        $this->authorize('manage', $course);

        $certificates = CourseCertificate::where('course_id', $course->id)->get();

        $stats = [
            'total' => $certificates->count(),
            'draft' => $certificates->where('status', 'draft')->count(),
            'issued' => $certificates->where('status', 'issued')->count(),
            'revoked' => $certificates->where('status', 'revoked')->count(),
            'by_type' => $certificates->groupBy('type')->map->count(),
            'total_downloads' => $certificates->sum('download_count'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Regenerate PDF for a certificate
     */
    public function regeneratePdf(Request $request, CourseCertificate $certificate): JsonResponse
    {
        $this->authorize('manage', $certificate->course);

        $this->certificateService->generatePdf($certificate);

        return response()->json([
            'success' => true,
            'message' => 'สร้าง PDF ใหม่เรียบร้อย',
            'data' => $certificate->fresh(),
        ]);
    }
}
