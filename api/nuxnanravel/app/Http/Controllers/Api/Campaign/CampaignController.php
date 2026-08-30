<?php

namespace App\Http\Controllers\Api\Campaign;

use App\Enums\ActivityType;
use App\Enums\CampaignPaymentStatus;
use App\Enums\CampaignReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\RecordImpressionRequest;
use App\Http\Requests\Campaign\RecordViewRequest;
use App\Http\Requests\Campaign\ReviewCampaignRequest;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Models\Academy;
use App\Models\Activity;
use App\Models\Advert;
use App\Models\Course;
use App\Services\Campaign\CampaignDeliveryService;
use App\Services\Campaign\CampaignRefundService;
use App\Services\Campaign\CampaignViewService;
use App\Services\Campaign\SupportPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function __construct(private CampaignDeliveryService $delivery) {}

    public function widget(Request $request): JsonResponse
    {
        $campaigns = $this->delivery->query(
            $request->string('scope', 'public')->toString(),
            $request->integer('academy_id') ?: null,
            $request->integer('course_id') ?: null,
        )->latest()->limit(5)->get();

        return response()->json(['success' => true, 'campaigns' => CampaignResource::collection($campaigns)]);
    }

    public function index(Request $request): JsonResponse
    {
        $scope = $request->string('scope', 'public')->toString();
        $academyId = $request->integer('academy_id') ?: null;
        $courseId = $request->integer('course_id') ?: null;

        $campaigns = $this->delivery->query($scope, $academyId, $courseId)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'campaigns' => CampaignResource::collection($campaigns)]);
    }

    public function targetAcademies(Request $request): JsonResponse
    {
        // SET-S2 — โรงเรียนที่ถูกเก็บถาวรเลือกเป็นเป้าหมายแคมเปญไม่ได้
        $query = Academy::query()
            ->notArchived()
            ->select(['id', 'name', 'logo'])
            ->when($request->filled('id'), fn ($q) => $q->whereKey($request->integer('id')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->toString().'%'))
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json(['success' => true, 'academies' => $query]);
    }

    public function targetCourses(Request $request): JsonResponse
    {
        $query = Course::query()
            ->select(['id', 'name', 'title', 'code', 'academy_id', 'cover as cover_image'])
            ->when($request->filled('id'), fn ($q) => $q->whereKey($request->integer('id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $q->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('title', 'like', $term)->orWhere('code', 'like', $term));
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json(['success' => true, 'courses' => $query]);
    }

    public function store(StoreCampaignRequest $request, SupportPaymentService $payments): JsonResponse
    {
        $data = $request->validated();
        $mediaFilename = $request->hasFile('media_image') ? $this->storeFile($request, 'media_image', 'images/adverts/medias') : null;
        $slipFilename = $request->hasFile('slip') ? $this->storeFile($request, 'slip', 'images/adverts/slips', 'local') : null;

        // Course-scoped campaigns must carry their owning academy so academy widgets can inherit them.
        $academyId = $data['academy_id'] ?? null;
        if (($data['scope_type'] ?? null) === 'course' && ! empty($data['course_id'])) {
            $academyId = $academyId ?: Course::whereKey($data['course_id'])->value('academy_id');
        }

        $campaign = DB::transaction(function () use ($request, $data, $mediaFilename, $slipFilename, $payments, $academyId) {
            $campaign = new Advert;
            $campaign->forceFill([
                'user_id' => $request->user()->id,
                'advertiser_id' => $request->user()->id,
                'campaign_type' => $data['campaign_type'],
                'scope_type' => $data['scope_type'],
                'academy_id' => $academyId,
                'course_id' => $data['course_id'] ?? null,
                'beneficiary_id' => $data['beneficiary_id'] ?? null,
                'inherit_to_academy' => (bool) ($data['inherit_to_academy'] ?? false),
                'title' => $data['title'] ?? 'สนับสนุนการเรียนรู้',
                'description' => $data['description'] ?? null,
                'media_link' => $data['media_link'] ?? null,
                'media_image' => $mediaFilename ?? '',
                'amounts' => $data['budget_amount'],
                'budget_amount' => $data['budget_amount'],
                'exchange_points' => 0,
                'duration' => $data['duration'] ?? 0,
                'total_views' => $data['total_views'] ?? 0,
                'remaining_views' => $data['total_views'] ?? 0,
                'slip' => $slipFilename ?? '',
                'transfer_date' => $data['transfer_date'] ?? today()->toDateString(),
                'transfer_time' => $data['transfer_time'] ?? now()->format('H:i'),
                'active_from' => $data['active_from'] ?? null,
                'active_until' => $data['active_until'] ?? null,
                'payment_status' => $data['payment_method'] === 'wallet' ? CampaignPaymentStatus::UNPAID : CampaignPaymentStatus::PENDING_SLIP,
                'review_status' => CampaignReviewStatus::PENDING,
                'status' => 0,
                'idempotency_key' => $data['idempotency_key'] ?? null,
            ]);
            $campaign->save();

            if ($data['payment_method'] === 'wallet') {
                $payments->payWithWallet($campaign, $request->user());
            } else {
                $payments->markSlipPending($campaign);
            }

            return $campaign->refresh();
        });

        $this->logActivity($campaign, ActivityType::CREATE_ADVERTISE, $request->user()->id);

        return response()->json(['success' => true, 'campaign' => new CampaignResource($campaign->load(['advertiser', 'academy', 'course', 'beneficiary']))], 201);
    }

    public function impression(RecordImpressionRequest $request, Advert $campaign, CampaignViewService $views): JsonResponse
    {
        $views->impression($campaign, $request->user(), $request->input('placement'), $this->ipHash($request), $request->userAgent(), $request->input('idempotency_key'));

        return response()->json(['success' => true]);
    }

    public function view(RecordViewRequest $request, Advert $campaign, CampaignViewService $views): JsonResponse
    {
        $campaign = $views->rewardedView($campaign, $request->user(), $request->string('idempotency_key')->toString());
        $this->logActivity($campaign, ActivityType::VIEW_ADVERTISE, $request->user()->id);

        return response()->json(['success' => true, 'campaign' => new CampaignResource($campaign->load(['advertiser', 'academy', 'course']))]);
    }

    public function manage(Request $request): JsonResponse
    {
        $campaigns = Advert::with(['advertiser', 'academy', 'course', 'reviewer'])->where('user_id', $request->user()->id)->latest()->paginate(20);

        return response()->json(['success' => true, 'campaigns' => CampaignResource::collection($campaigns)]);
    }

    public function academyManage(Academy $academy, Request $request): JsonResponse
    {
        abort_unless($academy->isAdmin($request->user()), 403);

        return response()->json(['success' => true, 'campaigns' => CampaignResource::collection(Advert::with(['advertiser', 'academy', 'course', 'reviewer'])->where('academy_id', $academy->id)->latest()->paginate(20))]);
    }

    public function courseManage(Course $course, Request $request): JsonResponse
    {
        abort_unless($course->isAdmin($request->user()), 403);

        return response()->json(['success' => true, 'campaigns' => CampaignResource::collection(Advert::with(['advertiser', 'academy', 'course', 'reviewer'])->where('course_id', $course->id)->latest()->paginate(20))]);
    }

    public function review(ReviewCampaignRequest $request, Advert $campaign, CampaignRefundService $refunds): JsonResponse
    {
        $action = $request->string('action')->toString();
        $current = $campaign->review_status?->value;

        // Enforce a strict state machine so support funds can never be double-paid or
        // refunded after they were already distributed to beneficiaries.
        $allowed = match ($action) {
            'approve' => $current === CampaignReviewStatus::PENDING->value,
            'reject' => $current === CampaignReviewStatus::PENDING->value && $campaign->distributed_at === null,
            'pause' => $current === CampaignReviewStatus::APPROVED->value,
            default => false,
        };

        if (! $allowed) {
            return response()->json([
                'success' => false,
                'message' => "ไม่สามารถดำเนินการ '{$action}' กับแคมเปญสถานะ '{$current}' ได้",
            ], 422);
        }

        $status = $action === 'approve' ? CampaignReviewStatus::APPROVED : ($action === 'reject' ? CampaignReviewStatus::REJECTED : CampaignReviewStatus::PENDING);

        $campaign->forceFill([
            'review_status' => $status,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'status' => $action === 'approve' ? 1 : ($action === 'reject' ? 2 : 0),
        ]);

        if ($action === 'approve') {
            if ($campaign->payment_status === CampaignPaymentStatus::PENDING_SLIP) {
                $campaign->payment_status = CampaignPaymentStatus::PAID;
            }
            $campaign->save();
        } else {
            $campaign->save();
        }

        if ($action === 'reject') {
            $refunds->refundWallet($campaign);
        }

        $this->logActivity($campaign, $action === 'reject' ? ActivityType::REJECT_ADVERTISE : ActivityType::APPROVE_ADVERTISE, $request->user()->id);

        return response()->json(['success' => true, 'campaign' => new CampaignResource($campaign->load(['advertiser', 'academy', 'course', 'reviewer']))]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->hasAnyRole(['ADMIN', 'PLEARND_ADMIN']), 403);

        $query = Advert::with(['advertiser', 'academy', 'course', 'reviewer']);

        if ($request->filled('review_status')) {
            $query->where('review_status', $request->input('review_status'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }
        if ($request->filled('campaign_type')) {
            $query->where('campaign_type', $request->input('campaign_type'));
        }
        if ($request->filled('scope_type')) {
            $query->where('scope_type', $request->input('scope_type'));
        }

        $campaigns = $query->latest()->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'campaigns' => CampaignResource::collection($campaigns),
        ]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->hasAnyRole(['ADMIN', 'PLEARND_ADMIN']), 403);

        $query = Activity::with(['user', 'activityable'])
            ->whereIn('activity_type', [
                'create_advertise',
                'view_advertise',
                'approve_advertise',
                'reject_advertise',
            ]);

        $logs = $query->latest()->paginate($request->integer('per_page', 30));

        $formattedLogs = $logs->getCollection()->map(function ($activity) {
            return [
                'id' => $activity->id,
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                ] : null,
                'activity_type' => $activity->activity_type,
                'created_at' => $activity->created_at,
                'details' => $activity->activity_details,
                'campaign' => $activity->activityable ? [
                    'id' => $activity->activityable->id,
                    'title' => $activity->activityable->title,
                    'campaign_type' => $activity->activityable->campaign_type,
                    'budget_amount' => $activity->activityable->budget_amount,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'logs' => [
                'data' => $formattedLogs,
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'total' => $logs->total(),
                    'per_page' => $logs->perPage(),
                ],
            ],
        ]);
    }

    public function updatePaymentStatus(Request $request, Advert $campaign): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->hasAnyRole(['ADMIN', 'PLEARND_ADMIN']), 403);

        $request->validate([
            'payment_status' => ['required', Rule::in(array_column(CampaignPaymentStatus::cases(), 'value'))],
        ]);

        $campaign->forceFill([
            'payment_status' => $request->input('payment_status'),
            'refunded_at' => $request->input('payment_status') === 'refunded' ? now() : $campaign->refunded_at,
        ])->save();

        return response()->json(['success' => true, 'campaign' => new CampaignResource($campaign->load(['advertiser', 'academy', 'course', 'reviewer']))]);
    }

    private function storeFile(StoreCampaignRequest $request, string $key, string $directory, string $disk = 'public'): string
    {
        return basename($request->file($key)->store($directory, $disk));
    }

    private function ipHash(Request $request): ?string
    {
        return $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null;
    }

    private function logActivity(Advert $campaign, ActivityType $type, int $userId): void
    {
        $activity = new Activity;
        $activity->user_id = $userId;
        $activity->activity_type = $type->value;
        $activity->activityable()->associate($campaign);
        $activity->save();
    }
}
