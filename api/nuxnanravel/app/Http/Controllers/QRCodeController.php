<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Universal QR Code Controller
 * 
 * Handles QR code parsing and routing to appropriate actions
 * 
 * QR Format: PREFIX:DATA[:DATA2:...]
 * 
 * Supported Types:
 * - COUPON:12345678         → Redeem coupon
 * - CHECKIN:class_id:session_id → Class check-in
 * - EVENT:event_id          → Event check-in
 * - POLL:poll_id            → Open poll
 * - SHARE:user_ref_code     → View profile
 * - COURSE:course_id        → View course
 * - ACADEMY:academy_id      → View academy
 * - REWARD:reward_id        → Claim reward
 */
class QRCodeController extends Controller
{
    /**
     * QR Type Registry
     */
    protected const QR_TYPES = [
        'COUPON' => [
            'type' => 'coupon',
            'label' => 'รับคูปอง',
            'action' => 'redirect',
            'route' => '/earn/coupons/redeem',
        ],
        'CHECKIN' => [
            'type' => 'checkin',
            'label' => 'เช็คชื่อเข้าเรียน',
            'action' => 'api',
            'endpoint' => '/api/classes/checkin',
        ],
        'EVENT' => [
            'type' => 'event',
            'label' => 'เข้าร่วมกิจกรรม',
            'action' => 'api',
            'endpoint' => '/api/events/checkin',
        ],
        'POLL' => [
            'type' => 'poll',
            'label' => 'ตอบแบบสำรวจ',
            'action' => 'redirect',
            'route' => '/polls/{id}',
        ],
        'SHARE' => [
            'type' => 'share',
            'label' => 'โปรไฟล์ผู้ใช้',
            'action' => 'redirect',
            'route' => '/profile/{id}',
        ],
        'COURSE' => [
            'type' => 'course',
            'label' => 'ดูรายวิชา',
            'action' => 'redirect',
            'route' => '/learn/courses/{id}',
        ],
        'ACADEMY' => [
            'type' => 'academy',
            'label' => 'ดูโรงเรียน',
            'action' => 'redirect',
            'route' => '/academies/{id}',
        ],
        'REWARD' => [
            'type' => 'reward',
            'label' => 'รับรางวัล',
            'action' => 'api',
            'endpoint' => '/api/rewards/claim',
        ],
    ];

    /**
     * Parse QR code and return type information
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function parse(Request $request)
    {
        $request->validate([
            'qr_data' => 'required|string|max:500',
        ]);

        $qrData = trim($request->qr_data);
        $parsed = $this->parseQRCode($qrData);

        return response()->json([
            'success' => $parsed['is_valid'],
            'data' => $parsed,
        ]);
    }

    /**
     * Execute QR code action
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(Request $request)
    {
        $request->validate([
            'qr_data' => 'required|string|max:500',
        ]);

        $qrData = trim($request->qr_data);
        $parsed = $this->parseQRCode($qrData);

        if (!$parsed['is_valid']) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code ไม่ถูกต้องหรือไม่รู้จัก',
                'data' => $parsed,
            ], 400);
        }

        // Route to appropriate handler based on type
        try {
            $result = $this->executeAction($parsed, $request);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('QR Execute Error: ' . $e->getMessage(), [
                'qr_data' => $qrData,
                'parsed' => $parsed,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse QR code string
     * 
     * @param string $qrString
     * @return array
     */
    protected function parseQRCode(string $qrString): array
    {
        $upperString = strtoupper(trim($qrString));
        
        // Try to match known prefixes
        foreach (self::QR_TYPES as $prefix => $config) {
            if (str_starts_with($upperString, $prefix . ':')) {
                $dataString = substr($qrString, strlen($prefix) + 1);
                $dataParts = explode(':', $dataString);
                
                return [
                    'type' => $config['type'],
                    'prefix' => $prefix,
                    'label' => $config['label'],
                    'action' => $config['action'],
                    'route' => $config['route'] ?? null,
                    'endpoint' => $config['endpoint'] ?? null,
                    'raw_data' => $qrString,
                    'data' => $dataParts,
                    'is_valid' => !empty($dataParts[0]),
                ];
            }
        }
        
        // Check if it's a pure numeric code (legacy coupon format)
        if (preg_match('/^\d{8}$/', $upperString)) {
            $config = self::QR_TYPES['COUPON'];
            return [
                'type' => $config['type'],
                'prefix' => 'COUPON',
                'label' => $config['label'],
                'action' => $config['action'],
                'route' => $config['route'] ?? null,
                'endpoint' => $config['endpoint'] ?? null,
                'raw_data' => $qrString,
                'data' => [$upperString],
                'is_valid' => true,
            ];
        }
        
        // Unknown QR code
        return [
            'type' => 'unknown',
            'prefix' => null,
            'label' => 'ไม่รู้จัก',
            'action' => null,
            'route' => null,
            'endpoint' => null,
            'raw_data' => $qrString,
            'data' => [$qrString],
            'is_valid' => false,
        ];
    }

    /**
     * Execute action based on parsed QR
     * 
     * @param array $parsed
     * @param Request $request
     * @return array
     */
    protected function executeAction(array $parsed, Request $request): array
    {
        switch ($parsed['type']) {
            case 'coupon':
                return $this->handleCoupon($parsed, $request);
            
            case 'checkin':
                return $this->handleCheckin($parsed, $request);
            
            case 'event':
                return $this->handleEvent($parsed, $request);
            
            case 'poll':
            case 'share':
            case 'course':
            case 'academy':
                // These types just need redirect, return the route
                return [
                    'success' => true,
                    'type' => $parsed['type'],
                    'action' => 'redirect',
                    'redirect_url' => str_replace('{id}', $parsed['data'][0], $parsed['route']),
                    'message' => 'กำลังนำทาง...',
                ];
            
            case 'reward':
                return $this->handleReward($parsed, $request);
            
            default:
                return [
                    'success' => false,
                    'message' => 'ไม่รองรับประเภท QR Code นี้',
                ];
        }
    }

    /**
     * Handle coupon redemption
     */
    protected function handleCoupon(array $parsed, Request $request): array
    {
        $couponController = app(\App\Http\Controllers\CouponController::class);
        
        // Create a new request with coupon code
        $redeemRequest = new Request([
            'coupon_code' => $parsed['data'][0],
        ]);
        $redeemRequest->setUserResolver($request->getUserResolver());
        
        $response = $couponController->redeem($redeemRequest);
        $responseData = json_decode($response->getContent(), true);
        
        return $responseData;
    }

    /**
     * Handle class check-in
     */
    protected function handleCheckin(array $parsed, Request $request): array
    {
        // TODO: Implement when class check-in feature is ready
        $classId = $parsed['data'][0] ?? null;
        $sessionId = $parsed['data'][1] ?? null;
        
        if (!$classId) {
            return [
                'success' => false,
                'message' => 'รหัสชั้นเรียนไม่ถูกต้อง',
            ];
        }
        
        // Placeholder - implement actual check-in logic
        return [
            'success' => true,
            'type' => 'checkin',
            'message' => 'เช็คชื่อสำเร็จ! (อยู่ระหว่างพัฒนา)',
            'data' => [
                'class_id' => $classId,
                'session_id' => $sessionId,
            ],
        ];
    }

    /**
     * Handle event check-in
     */
    protected function handleEvent(array $parsed, Request $request): array
    {
        // TODO: Implement when event check-in feature is ready
        $eventId = $parsed['data'][0] ?? null;
        
        if (!$eventId) {
            return [
                'success' => false,
                'message' => 'รหัสกิจกรรมไม่ถูกต้อง',
            ];
        }
        
        // Placeholder - implement actual check-in logic
        return [
            'success' => true,
            'type' => 'event',
            'message' => 'เข้าร่วมกิจกรรมสำเร็จ! (อยู่ระหว่างพัฒนา)',
            'data' => [
                'event_id' => $eventId,
            ],
        ];
    }

    /**
     * Handle reward claim
     */
    protected function handleReward(array $parsed, Request $request): array
    {
        // TODO: Implement when reward feature is ready
        $rewardId = $parsed['data'][0] ?? null;
        
        if (!$rewardId) {
            return [
                'success' => false,
                'message' => 'รหัสรางวัลไม่ถูกต้อง',
            ];
        }
        
        // Placeholder - implement actual reward claim logic
        return [
            'success' => true,
            'type' => 'reward',
            'message' => 'รับรางวัลสำเร็จ! (อยู่ระหว่างพัฒนา)',
            'data' => [
                'reward_id' => $rewardId,
            ],
        ];
    }

    /**
     * Get all supported QR types
     */
    public function types()
    {
        $types = [];
        foreach (self::QR_TYPES as $prefix => $config) {
            $types[] = [
                'prefix' => $prefix,
                'type' => $config['type'],
                'label' => $config['label'],
                'format' => $prefix . ':' . $this->getFormatExample($config['type']),
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Get format example for type
     */
    protected function getFormatExample(string $type): string
    {
        return match ($type) {
            'coupon' => '12345678',
            'checkin' => 'class_id:session_id',
            'event' => 'event_id',
            'poll' => 'poll_id',
            'share' => 'user_ref_code',
            'course' => 'course_id',
            'academy' => 'academy_id',
            'reward' => 'reward_id',
            default => 'data',
        };
    }
}
