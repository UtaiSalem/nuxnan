<?php

namespace App\Http\Controllers\Api\Academies;

use App\Enums\CampaignPaymentStatus;
use App\Enums\CampaignReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AcademyDonate\AcademyDonateResource;
use App\Http\Resources\AcademyRevenue\AcademyAdminDonationResource;
use App\Http\Resources\AcademyRevenue\AcademyPublicDonationResource;
use App\Http\Resources\Campaign\CampaignResource;
use App\Models\Academy;
use App\Models\AcademyDonate;
use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
use App\Models\Activity;
use App\Models\Advert;
use App\Services\AcademyDonateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademyRevenueController extends Controller
{
    public function __construct(protected AcademyDonateService $donationService) {}

    public function supportSummary(Academy $academy): JsonResponse
    {
        $approvedDonations = $academy->donations()
            ->whereIn('status', ['approved', 'completed'])
            ->whereNull('deleted_at')
            ->get();

        $pointsTotal = (int) $approvedDonations->where('donation_type', 'point')->sum('points_amount');
        $cashTotal = (int) $approvedDonations->where('donation_type', 'cash')->sum('cash_amount');
        $supporterCount = $approvedDonations->where('donor_id', '!=', null)->unique('donor_id')->count();

        $adRevenuePoints = (int) AcademyPointTransaction::where('academy_id', $academy->id)
            ->where('type', AcademyPointTransaction::TYPE_AD_REVENUE)
            ->sum('amount');

        $campaignCount = Advert::where('academy_id', $academy->id)
            ->where('review_status', 'approved')
            ->count();

        $recentDonations = AcademyDonateResource::collection(
            $academy->donations()
                ->whereIn('status', ['approved', 'completed'])
                ->whereNull('deleted_at')
                ->latest()
                ->limit(5)
                ->get()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'approved_points_total' => $pointsTotal,
                'approved_cash_total' => $cashTotal,
                'supporter_count' => $supporterCount,
                'ad_revenue_points' => $adRevenuePoints,
                'campaign_count' => $campaignCount,
                'recent_donations' => $recentDonations,
            ],
        ]);
    }

    public function donations(Academy $academy, Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $academy->isAdmin($user);

        $query = AcademyDonate::with(['academy', 'donor', 'transaction', 'reviewer'])
            ->where('academy_id', $academy->id)
            ->whereNull('deleted_at')
            ->latest();

        if (! $isAdmin) {
            $query->whereIn('status', ['approved', 'completed']);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('donation_type')) {
            $query->where('donation_type', $request->input('donation_type'));
        }

        $donations = $query->paginate($request->integer('per_page', 15));
        $resource = $isAdmin
            ? AcademyAdminDonationResource::class
            : AcademyPublicDonationResource::class;

        return response()->json([
            'success' => true,
            'donations' => $resource::collection($donations->items()),
            'meta' => [
                'current_page' => $donations->currentPage(),
                'last_page' => $donations->lastPage(),
                'total' => $donations->total(),
                'per_page' => $donations->perPage(),
            ],
        ]);
    }

    public function revenue(Academy $academy): JsonResponse
    {
        $account = AcademyPointAccount::firstOrCreate(['academy_id' => $academy->id]);

        $donationsQuery = $academy->donations()->whereNull('deleted_at');

        $pendingDonations = (clone $donationsQuery)->where('status', 'pending')->get();
        $approvedDonations = (clone $donationsQuery)->where('status', 'approved')->get();
        $rejectedDonations = (clone $donationsQuery)->where('status', 'rejected')->get();

        $pendingPoints = (int) $pendingDonations->where('donation_type', 'point')->sum('points_amount');
        $pendingCash = (int) $pendingDonations->where('donation_type', 'cash')->sum('cash_amount');
        $approvedPoints = (int) $approvedDonations->where('donation_type', 'point')->sum('points_amount');
        $approvedCash = (int) $approvedDonations->where('donation_type', 'cash')->sum('cash_amount');

        $adRevenuePoints = (int) AcademyPointTransaction::where('academy_id', $academy->id)
            ->where('type', AcademyPointTransaction::TYPE_AD_REVENUE)
            ->sum('amount');

        $activeCampaigns = Advert::where('academy_id', $academy->id)
            ->where('review_status', 'approved')
            ->where('remaining_views', '>', 0)
            ->count();

        $recentDonations = AcademyAdminDonationResource::collection(
            $academy->donations()
                ->whereNull('deleted_at')
                ->latest()
                ->limit(10)
                ->get()
        );

        $recentTransactions = AcademyPointTransaction::where('academy_id', $academy->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($txn) {
                return [
                    'id' => $txn->id,
                    'type' => $txn->type,
                    'amount' => $txn->amount,
                    'balance_after' => $txn->balance_after,
                    'created_at' => $txn->created_at,
                    'metadata' => $txn->metadata,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'points' => [
                    'balance' => $account->balance,
                    'available_balance' => $account->available_balance,
                    'reserved_balance' => $account->reserved_balance,
                    'total_earned' => $account->total_earned,
                    'total_withdrawn' => $account->total_withdrawn,
                    'total_distributed' => $account->total_distributed,
                    'platform_earned' => $account->platform_earned,
                ],
                'donations' => [
                    'pending_count' => $pendingDonations->count(),
                    'pending_points' => $pendingPoints,
                    'pending_cash' => $pendingCash,
                    'approved_count' => $approvedDonations->count(),
                    'approved_points' => $approvedPoints,
                    'approved_cash' => $approvedCash,
                    'rejected_count' => $rejectedDonations->count(),
                ],
                'ad_revenue' => [
                    'total_points' => $adRevenuePoints,
                    'campaign_count' => Advert::where('academy_id', $academy->id)->count(),
                    'active_campaigns' => $activeCampaigns,
                ],
                'recent_donations' => $recentDonations,
                'recent_transactions' => $recentTransactions,
            ],
        ]);
    }

    public function revenueActivity(Academy $academy, Request $request): JsonResponse
    {
        $activities = Activity::with(['user', 'activityable'])
            ->where(function ($q) use ($academy) {
                $q->where('activityable_type', AcademyDonate::class)
                    ->whereIn('activityable_id', $academy->donations()->pluck('id'));
            })
            ->orWhere(function ($q) use ($academy) {
                $q->where('activityable_type', Advert::class)
                    ->whereIn('activityable_id', Advert::where('academy_id', $academy->id)->pluck('id'));
            })
            ->latest()
            ->paginate($request->integer('per_page', 20));

        $formatted = $activities->getCollection()->map(function ($activity) {
            $data = [
                'id' => $activity->id,
                'type' => $activity->activity_type,
                'created_at' => $activity->created_at,
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                ] : null,
            ];

            if ($activity->activityable_type === AcademyDonate::class) {
                $donation = $activity->activityable;
                $amountDisplay = $donation->donation_type === 'point'
                    ? $donation->points_amount.' แต้ม'
                    : number_format($donation->cash_amount).' บาท';
                $data['description'] = match ($activity->activity_type) {
                    'approve_academy_donation' => "อนุมัติการบริจาค {$amountDisplay}",
                    'reject_academy_donation' => "ปฏิเสธการบริจาค {$amountDisplay}",
                    default => "การบริจาค #{$donation->id}",
                };
                $data['amount'] = $donation->donation_type === 'point' ? $donation->points_amount : $donation->cash_amount;
                $data['amount_type'] = $donation->donation_type;
            } elseif ($activity->activityable_type === Advert::class) {
                $campaign = $activity->activityable;
                $data['description'] = match ($activity->activity_type) {
                    'approve_advertise' => "อนุมัติแคมเปญ '{$campaign->title}'",
                    'reject_advertise' => "ปฏิเสธแคมเปญ '{$campaign->title}'",
                    'create_advertise' => "สร้างแคมเปญ '{$campaign->title}'",
                    default => "แคมเปญ #{$campaign->id}",
                };
                $data['amount'] = $campaign->budget_amount;
                $data['amount_type'] = 'budget';
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'activities' => $formatted,
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
            ],
        ]);
    }

    public function approveDonation(Academy $academy, AcademyDonate $donation, Request $request): JsonResponse
    {
        abort_if($donation->academy_id !== $academy->id, 403);
        abort_if($academy->isAdmin($request->user()) === false, 403);

        $request->validate(['note' => 'nullable|string|max:1000']);

        $donation = $this->donationService->approve($donation, $request->user(), $request->input('note'));

        return response()->json([
            'success' => true,
            'donation' => new AcademyAdminDonationResource($donation->load(['academy', 'donor', 'transaction'])),
        ]);
    }

    public function rejectDonation(Academy $academy, AcademyDonate $donation, Request $request): JsonResponse
    {
        abort_if($donation->academy_id !== $academy->id, 403);
        abort_if($academy->isAdmin($request->user()) === false, 403);

        $request->validate(['reason' => 'required|string|max:1000']);

        $donation = $this->donationService->reject($donation, $request->user(), $request->input('reason'));

        return response()->json([
            'success' => true,
            'donation' => new AcademyAdminDonationResource($donation->load(['academy', 'donor', 'transaction'])),
        ]);
    }

    public function campaignsIndex(Academy $academy, Request $request): JsonResponse
    {
        abort_if($academy->isAdmin($request->user()) === false, 403);

        $campaigns = Advert::with(['advertiser', 'academy', 'course', 'beneficiary', 'reviewer'])
            ->where('academy_id', $academy->id)
            ->when($request->filled('review_status'), fn ($q, $v) => $q->where('review_status', $v))
            ->when($request->filled('campaign_type'), fn ($q, $v) => $q->where('campaign_type', $v))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'campaigns' => CampaignResource::collection($campaigns),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'total' => $campaigns->total(),
                'per_page' => $campaigns->perPage(),
            ],
        ]);
    }

    public function campaignsStore(Academy $academy, Request $request): JsonResponse
    {
        abort_if($academy->isAdmin($request->user()) === false, 403);

        $request->validate([
            'campaign_type' => ['required', Rule::in(['advertisement', 'support'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'budget_amount' => ['required', 'numeric', 'min:1'],
            'total_views' => ['required', 'integer', 'min:1', 'max:100000'],
            'duration' => ['required', 'integer', Rule::in([5, 10, 15, 30, 60])],
            'payment_method' => ['required', Rule::in(['wallet', 'slip'])],
            'transfer_date' => ['nullable', 'date'],
            'transfer_time' => ['nullable', 'date_format:H:i'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after:active_from'],
            'media_link' => ['nullable', 'url', 'max:2048'],
        ]);

        $data = $request->only([
            'campaign_type', 'title', 'description', 'budget_amount', 'total_views',
            'duration', 'payment_method', 'transfer_date', 'transfer_time',
            'active_from', 'active_until', 'media_link',
        ]);

        $campaign = DB::transaction(function () use ($request, $data, $academy) {
            $campaign = new Advert;
            $campaign->forceFill([
                'user_id' => $request->user()->id,
                'advertiser_id' => $request->user()->id,
                'campaign_type' => $data['campaign_type'],
                'scope_type' => 'academy',
                'academy_id' => $academy->id,
                'course_id' => null,
                'inherit_to_academy' => false,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'amounts' => $data['budget_amount'],
                'budget_amount' => $data['budget_amount'],
                'duration' => $data['duration'],
                'total_views' => $data['total_views'],
                'remaining_views' => $data['total_views'],
                'slip' => $data['payment_method'] === 'slip' ? ($data['slip'] ?? '') : '',
                'media_image' => $data['media_image'] ?? '',
                'transfer_date' => $data['transfer_date'] ?? today()->toDateString(),
                'transfer_time' => $data['transfer_time'] ?? now()->format('H:i'),
                'active_from' => $data['active_from'] ?? null,
                'active_until' => $data['active_until'] ?? null,
                'payment_status' => $data['payment_method'] === 'wallet' ? CampaignPaymentStatus::UNPAID : CampaignPaymentStatus::PENDING_SLIP,
                'review_status' => CampaignReviewStatus::PENDING,
                'status' => 0,
            ]);
            $campaign->save();

            return $campaign->refresh();
        });

        return response()->json([
            'success' => true,
            'campaign' => new CampaignResource($campaign->load(['advertiser', 'academy', 'course', 'beneficiary', 'reviewer'])),
        ], 201);
    }

    public function campaignsUpdate(Academy $academy, Advert $campaign, Request $request): JsonResponse
    {
        abort_if($campaign->academy_id !== $academy->id, 403);
        abort_if($academy->isAdmin($request->user()) === false, 403);

        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'active_until' => ['nullable', 'date', 'after:today'],
            'status' => ['nullable', 'integer', Rule::in([0, 1, 2])],
        ]);

        $campaign->forceFill($request->only(['title', 'description', 'active_until', 'status']))->save();

        return response()->json([
            'success' => true,
            'campaign' => new CampaignResource($campaign->load(['advertiser', 'academy', 'course', 'beneficiary', 'reviewer'])),
        ]);
    }
}
