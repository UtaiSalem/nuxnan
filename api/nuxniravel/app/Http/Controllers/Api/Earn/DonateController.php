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

    public function fetch_more_donates()
    {
        $donates = Donate::latest()->paginate();
        $donatesResource = DonateResource::collection($donates);

        return response()->json([
            'success'           => true,
            'donates'           => $donates,
        ]);
    }

    public function allAvailableDonates()
    {
        return response()->json([
            'donates' => DonateResource::collection(Donate::whereNotIn('status',[2])->orderBy('remaining_points', 'desc')->latest()->paginate()),
        ]);
    }

    /**
     * Get donates for sidebar widget (limited to 5 items).
     */
    public function widget()
    {
        $donates = Donate::whereNotIn('status', [2])
            ->orderBy('remaining_points', 'DESC')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'donates' => DonateResource::collection($donates),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $donor = Auth::user();

        return response()->json([
            'donor' => $donor,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       
        $validated = $request->validate([
            'user_id'      => ['nullable'],
            'donor_id'      => ['nullable'],
            'donor_name'    => ['nullable'],
            'amounts'       => ['required', 'numeric'],
            'transfer_date' => ['required'],
            'transfer_time' => ['required'],
        ]);
       
        if($request->file('slip')) {
            $slip_file = $request->file('slip');
            $slip_filename =  uniqid().'.'.$slip_file->getClientOriginalExtension();
            // $slip_file->storeAs('public/images/supports/donates/slips', $slip_filename);
            $slip_file->storeAs('public/images/donates', $slip_filename);

        }

        $donate = new Donate();

        $donate->user_id            = $validated['user_id'] ?? 7;
        $donate->donor_id           = !$validated['donor_id'] || $validated['donor_id'] === 'null' ? null : $validated['donor_id'];
        $donate->donor_name         = $validated['donor_name'] ?? null;
        
        $donate->amounts            = $validated['amounts'];
        $donate->slip               = $slip_filename;
        $donate->transfer_date      = Carbon::parse($request->transfer_date);
        $donate->transfer_time      = $validated['transfer_time'];
        $donate->remaining_points   = $validated['amounts']*1080;
        $donate->save();

        $activity = new Activity();
        $activity->user_id = $donate->donor_id ?? 7;
        $activity->activity_type = ActivityType::GIVE_DONATION->value;
        $activity->activityable()->associate($donate);
        $activity->save();

        return response()->json([
            'success' => true,
            'donate' => $donate,
        ]);
    }


    // get donor
    public function getDonor(User $user)
    {
        return response()->json([
            'success' => true,
            'donor' => $user,
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
}
