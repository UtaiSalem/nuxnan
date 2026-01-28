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
use Illuminate\Support\Facades\Storage;

class DonateController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Donate::latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $donates = $query->paginate($request->get('per_page', 15));
        $donatesResource = DonateResource::collection($donates);

        $stats = [
            'total' => Donate::count(),
            'pending' => Donate::where('status', 0)->count(),
            'approved' => Donate::where('status', 1)->count(),
            'rejected' => Donate::where('status', 2)->count(),
        ];

        return response()->json([
            'donates' => $donatesResource,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * รองรับทั้งผู้ใช้ที่ login และไม่ได้ login (anonymous)
     * payment_method: slip, wallet, points
     */
    public function store(Request $request)
    {
        $user = auth()->user() ?? auth('api')->user();
        
        // If user is not authenticated but user_id is sent (for Slip payment via Web)
        if (!$user && $request->has('user_id') && $request->input('payment_method') === 'slip') {
           $user = User::find($request->input('user_id'));
        }

        $isAnonymousRequested = $request->boolean('is_anonymous', false);
        $isAnonymous = $isAnonymousRequested || !$user;
        $paymentMethod = $request->input('payment_method', 'slip');

        // กำหนด validation rules - anonymous ต้องมี slip เสมอ
        $rules = [
            'amounts' => 'required|numeric|min:1',
            'transfer_date' => 'required',
            'transfer_time' => 'required',
            'payment_method' => 'nullable|in:slip,wallet,points',
            'donor_name' => 'nullable|string|max:255',
            'slip' => ($isAnonymous || $paymentMethod === 'slip') ? 'required|image|mimes:jpg,jpeg,png,gif,svg|max:2048' : 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
        ];

        $validated = $request->validate($rules);

        // Anonymous ต้องใช้ slip เท่านั้น
        if ($isAnonymous && $paymentMethod !== 'slip') {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบเพื่อใช้วิธีการชำระนี้',
            ], 401);
        }

        try {
            $slip_filename = null;
            if ($request->hasFile('slip')) {
                $slip_file = $request->file('slip');
                $slip_filename = uniqid() . '.' . $slip_file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/donates', $slip_file, $slip_filename);
            }

            $donate = new Donate();
            
            // กำหนดข้อมูลผู้บริจาค
            if ($isAnonymous) {
                $donate->user_id = null;
                $donate->donor_id = null;
                $donate->donor_name = 'ไม่ประสงค์ออกนาม';
            } else {
                $donate->user_id = $user->id;
                $donate->donor_id = $user->id;
                $donate->donor_name = $request->input('donor_name', $user->name);
            }
            
            $donate->amounts = $validated['amounts'];
            $donate->transfer_date = Carbon::parse($request->transfer_date);
            $donate->transfer_time = $validated['transfer_time'];
            $donate->slip = $slip_filename ?? '';
            $donate->remaining_points = $validated['amounts'] * 1080;
            $donate->payment_method = $paymentMethod;
            
            // จัดการตาม payment_method
            switch ($paymentMethod) {
                case 'slip':
                    // มี slip = รอตรวจสอบจาก admin
                    $donate->status = 0; // Pending
                    break;
                    
                case 'wallet':
                    // หักจาก wallet
                    if ($user->wallet < $validated['amounts']) {
                        return response()->json([
                            'success' => false,
                            'message' => 'ยอดเงินในกระเป๋าไม่เพียงพอ',
                            'wallet_balance' => $user->wallet,
                        ], 402);
                    }
                    $user->decrement('wallet', $validated['amounts']);
                    $donate->status = 1; // Approved
                    break;
                    
                case 'points':
                    // หักจากแต้มสะสม (pp) - อัตราแลกเปลี่ยน: 1 บาท = 100 แต้ม
                    $pointsRequired = $validated['amounts'] * 100;
                    if ($user->pp < $pointsRequired) {
                        return response()->json([
                            'success' => false,
                            'message' => 'แต้มสะสมไม่เพียงพอ',
                            'points_balance' => $user->pp,
                            'points_required' => $pointsRequired,
                        ], 402);
                    }
                    $user->decrement('pp', $pointsRequired);
                    $donate->status = 1; // Approved
                    break;
            }

            $donate->save();
            
            return response()->json([
                'success' => true,
                'message' => 'สร้างการสนับสนุนสำเร็จ',
                'donate' => new DonateResource($donate),
                'payment_method' => $paymentMethod,
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $th->getMessage(),
            ], 500);
        }
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
            ->whereIn('status', [0, 1])
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

