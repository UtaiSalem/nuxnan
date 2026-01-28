<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Advert; // We will still use the Advert model, as that is the table name
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Enums\ActivityType;
use Illuminate\Support\Carbon;
use App\Http\Resources\UserResource;
use App\Http\Resources\Shared\AdvertResource; // New Resource
use Illuminate\Support\Facades\Storage;

class AdvertController extends Controller
{
    public function index()
    {
        return response()->json([
            'adverts'    => AdvertResource::collection(Advert::with('advertiser')->where('status',1)->orderBy('remaining_views', 'DESC')->latest()->paginate()),
        ]);
    }

    public function getMoreAdvertisings()
    {
        $adverts = Advert::with('advertiser')->where('status',1)->orderBy('remaining_views', 'DESC')->latest()->paginate();

        return response()->json([
            'success' => true,
            'adverts' => AdvertResource::collection($adverts)
        ]);
    }   

    public function create()
    {
        $authUser = auth()->user();
        return response()->json([
            'Advertiser' => new UserResource($authUser),
        ]);
    }

    public function advertisesIndex()
    {
        return response()->json([
            'advertises' => AdvertResource::collection(Advert::with('advertiser')->latest()->paginate()),
        ]);
    }

    /**
     * Get advertises for sidebar widget (limited to 5 items).
     */
    public function widget()
    {
        $advertises = Advert::with('advertiser')->where('status', 1)
            ->where('remaining_views', '>', 0)
            ->orderBy('remaining_views', 'DESC')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'advertises' => AdvertResource::collection($advertises),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'advertiser_id' => '',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'media_link' => 'nullable|string|url',
            'amounts' => 'required',
            'duration' => 'required',
            'total_views' => 'required',
            'transfer_date' => 'required',
            'transfer_time' => 'required',
            'slip' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'media_image' => 'required|mimes:jpg,jpeg,png,gif,svg,mp4,webm,ogg|max:20480',
        ]);

        try {
            $slip_filename = null;
            $media_filename = null;

            if($request->file('slip')) {
                $slip_file = $request->file('slip');
                $slip_filename =  uniqid().'.'.$slip_file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/adverts/slips', $slip_file, $slip_filename);
            }
            if($request->file('media_image')) {
                $media_file = $request->file('media_image');
                $media_filename =  uniqid().'.'.$media_file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/adverts/medias', $media_file, $media_filename);
            }

            $newAdvert                     = new Advert();
            $newAdvert->user_id            = auth()->id();
            $newAdvert->advertiser_id      = $validated['advertiser_id'];
            $newAdvert->title              = $validated['title'];
            $newAdvert->description        = $validated['description'] ?? null;
            $newAdvert->media_link         = $validated['media_link'] ?? null;
            $newAdvert->amounts            = $validated['amounts'];
            $newAdvert->duration           = $validated['duration'];
            $newAdvert->total_views        = $validated['total_views'];
            $newAdvert->remaining_views    = $validated['total_views'];
            $newAdvert->transfer_date      = Carbon::parse($request->transfer_date);
            $newAdvert->transfer_time      = $validated['transfer_time'];
            $newAdvert->slip               = $slip_filename ?? null;
            $newAdvert->media_image        = $media_filename;

            if ($request->hasFile('slip')) {
                $newAdvert->status = 0; // Pending Admin Review
            } else {
                // Pay with Wallet
                $user = auth()->user();
                $expectedAmount = $validated['total_views'] * $validated['duration'] * 0.10;
                
                // Allow small floating point difference (e.g. 0.01)
                if (abs($validated['amounts'] - $expectedAmount) > 0.1) {
                     return response()->json([
                        'success' => false,
                        'message' => 'ยอดเงินไม่ถูกต้อง (ระบบคำนวณใหม่ได้ ' . number_format($expectedAmount, 2) . ' บาท)',
                    ], 400);
                }

                if ($user->wallet < $validated['amounts']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ยอดเงินในกระเป๋าไม่เพียงพอ',
                    ], 402);
                }
                
                $user->decrement('wallet', $validated['amounts']);
                $newAdvert->status = 1; // Auto Approve
            }

            $newAdvert->save();

            $activity = new Activity();
            $activity->user_id = $newAdvert->user_id;
            $activity->activity_type = ActivityType::CREATE_ADVERTISE->value;
            $activity->activityable()->associate($newAdvert);
            $activity->save();

            return response()->json([
                'success' => true,
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }

    }


    public function view(Advert $advert)
    {
        try {
            $authUser = auth()->user();

            // 1. Check Points (PP) required
            $pointsRequired = $advert->duration * 20;
            if ($authUser->pp < $pointsRequired) {
                return response()->json([
                    'success' => false,
                    'message' => 'แต้มสะสม (PP) ไม่เพียงพอสำหรับการรับชมโฆษณานี้ ต้องการ ' . $pointsRequired . ' PP',
                ], 402);
            }

            // 2. Check Daily View Limit (Max 5 times per day per advert)
            $todayViews = $advert->advertViewers()
                ->where('user_id', $authUser->id)
                ->whereDate('created_at', Carbon::today())
                ->count();

            if ($todayViews >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณดูโฆษณานี้ครบโควต้า 5 ครั้งต่อวันแล้ว',
                ], 429);
            }

            // 2. Atomic Check & Decrement Remaining Views
            // Returns 1 if successful, 0 if condition failed (e.g., remaining_views was 0)
            $affected = Advert::where('id', $advert->id)
                ->where('remaining_views', '>', 0)
                ->decrement('remaining_views');

            if ($affected === 0) {
                 return response()->json([
                    'success' => false,
                    'advert'    => new AdvertResource($advert),
                    'message' => 'จำนวนการแสดงโฆษณาครบแล้ว',
                ], 404);
            }

            // 3. Process Rewards (In Transaction to ensure consistency)
            \DB::transaction(function() use ($authUser, $advert, $pointsRequired) {
                 // Attach Viewer record
                $advert->viewers()->attach($authUser->id);

                // Deduct Points
                $authUser->decrement('pp', $pointsRequired);

                // Reward Viewer
                // Policy: Base 0.06 THB/sec + (Points / 1200) THB
                $baseReward = $advert->duration * 0.06;
                $pointsReward = $pointsRequired / 1200;
                $totalReward = $baseReward + $pointsReward;

                $authUser->increment('wallet', $totalReward);

                // Reward Referrer (0.02 THB/sec)
                $suggesterCode = $authUser->suggester_code ?? 99999999;
                $suggester = User::where('personal_code', $suggesterCode)->first() ?? User::where('personal_code', 99999999)->first();
                if($suggester) {
                    $suggester->increment('wallet', $advert->duration * 0.02);
                }

                // Log Activity
                $activity = new Activity();
                $activity->user_id = $authUser->id;
                $activity->activity_type = ActivityType::VIEW_ADVERTISE->value;
                $activity->activityable()->associate($advert->advertViewers()->where('user_id', $authUser->id)->latest()->first());
                $activity->save();
            });

            return response()->json([
                'success'   => true,
                'advert'    => new AdvertResource($advert->refresh()),
                'message'   => 'success'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'advert'    => new AdvertResource($advert),
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function approve(Advert $advert)
    {
        try {
            $advert->status = 1;
            $advert->save();

            return response()->json([
                'success' => true,
                'message' => 'success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function reject(Advert $advert)
    {
        try {
            $advert->status = 2;
            $advert->save();

            return response()->json([
                'success' => true,
                'message' => 'success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

}
