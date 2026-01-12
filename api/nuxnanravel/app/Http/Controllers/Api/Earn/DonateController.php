<?php

namespace App\Http\Controllers\Api\Earn;

use App\Models\User;

use App\Models\Donate;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Enums\ActivityType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Earn\DonateResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\Play\ActivityResource;

class DonateController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $donates = Donate::latest()->paginate();
        $donatesResource = DonateResource::collection($donates);

        return response()->json([
            'donates' => $donatesResource,
        ]);
    }
// ... (skip unchanged methods)
    // get donor
    public function getDonor(User $user)
    {
        return response()->json([
            'success' => true,
            'donor' => new UserResource($user),
        ]);
    }

    //recieve
    public function recieve(Donate $donate)
    {
        $donate->update([
            'status'        => 1,
            'approved_by'   => auth()->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'donate' => $donate,
        ], 200);
    }

    //cancel
    public function reject(Donate $donate)
    {
        $donate->update([
            'status' => 2,
            'approved_by'   => auth()->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'donate' => $donate,
        ], 200);
    }

    //get donate
    public function getDonate(Donate $donate)
    {
        // ตรวจสอบสถานะการอนุมัติ (status: 0=รอ, 1=อนุมัติ, 2=ปฏิเสธ)
        if($donate->status !== 1){
            $statusMessage = match($donate->status) {
                0 => 'การสนับสนุนนี้ยังรอการตรวจสอบและอนุมัติจากแอดมิน',
                2 => 'การสนับสนุนนี้ถูกปฏิเสธ',
                default => 'การสนับสนุนนี้ยังไม่พร้อมใช้งาน',
            };
            return response()->json([
                'success' => false,
                'donate' => $donate,
                'message' => $statusMessage,
                'pending_approval' => $donate->status === 0,
            ]);
        }

        if($donate->remaining_points < 270){
            return response()->json([
                'success' => false,
                'donate' => $donate,
                'message' => 'การสนับสนุนนี้หมดแล้ว',
            ]);
        }
        try {
            if($donate->remaining_points > 269){
                $authUser = auth()->user();

                // ตรวจสอบจำนวนครั้งที่รับแต้มจาก donate นี้ในวันนี้ (จำกัด 10 ครั้ง/คน/วัน/การสนับสนุน)
                $todayReceiveCount = $donate->recipients()
                    ->where('user_id', $authUser->id)
                    ->whereDate('donate_recipients.created_at', Carbon::today())
                    ->count();

                if ($todayReceiveCount >= 10) {
                    return response()->json([
                        'success' => false,
                        'donate' => $donate,
                        'message' => 'คุณได้รับแต้มจากการสนับสนุนนี้ครบ 10 ครั้งแล้วในวันนี้ กรุณารอวันใหม่',
                        'daily_limit_reached' => true,
                        'today_count' => $todayReceiveCount,
                    ]);
                }

                $donate->recipients()->attach($authUser->id);

                
                $donate->decrement('remaining_points', 270);
                
                $authUser->increment('pp', 240);
                 
                $suggesterCode = $authUser->suggester_code ?? 99999999;
                
                $suggester = User::where('personal_code', $authUser->suggester_code)->first();

                if($suggester) {
                    $suggester->increment('pp', 30);
                }
                
                $activity = new Activity();
                $activity->user_id = $authUser->id;
                $activity->activity_type = ActivityType::RECEIVE_DONATION->value;
                $activity->activityable()->associate($donate->donateRecipients()->where('user_id', $authUser->id)->latest()->first());
                $activity->save();
       

                $donate->refresh();
        
                return response()->json([
                    'success' => true,
                    'donate' => $donate,
                    'activity' => new ActivityResource($activity),
                ]);
            }else {
                return response()->json([
                    'success' => false,
                    'donate' => $donate,
                    'message' => 'การสนับสนุนนี้หมดแล้ว',
                ]);
            }
        } catch (\Throwable $th) {
            // throw $th;
            return response()->json([
                'success'   => false,
                'message'   => 'ไม่สามารถสนับสนุนได้',
                'error'     => $th->getMessage(),
            ]);
        }
    }
    /**
     * Get all available donations for public view.
     */
    public function allAvailableDonates()
    {
        $donates = Donate::latest()
            ->with(['donor'])
            ->paginate(12);

        // Manually structure response to match frontend expectations
        return response()->json([
            'donates' => [
                'data' => DonateResource::collection($donates)->resolve(),
                'links' => [
                    'first' => $donates->url(1),
                    'last' => $donates->url($donates->lastPage()),
                    'prev' => $donates->previousPageUrl(),
                    'next' => $donates->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $donates->currentPage(),
                    'from' => $donates->firstItem(),
                    'last_page' => $donates->lastPage(),
                    'per_page' => $donates->perPage(),
                    'to' => $donates->lastItem(),
                    'total' => $donates->total(),
                ],
            ],
        ]);
    }

    /**
     * Get donations for dashboard widget (limited count).
     */
    public function widget()
    {
        $donates = Donate::whereIn('status', [0, 1])
            ->where('remaining_points', '>', 0)
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'donates' => DonateResource::collection($donates),
        ]);
    }

    /**
     * Get donation history for the authenticated user.
     */
    public function history()
    {
        $donates = Donate::where('donor_id', auth()->id())
            ->latest()
            ->paginate(12);

        return response()->json([
            'donates' => DonateResource::collection($donates),
        ]);
    }
}

