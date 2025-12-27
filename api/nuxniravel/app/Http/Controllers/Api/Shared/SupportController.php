<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;

use App\Models\User;

use App\Models\Donate;
use App\Models\Support;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Enums\ActivityType;
use Illuminate\Support\Carbon;
use App\Http\Resources\UserResource;
use App\Http\Resources\DonateResource;
use App\Http\Resources\Shared\SupportResource;
use Illuminate\Support\Facades\Storage;

class SupportController extends Controller
{
    public function index()
    {
        return response()->json([
            'adverts'    => SupportResource::collection(Support::where('status',1)->orderBy('remaining_views', 'DESC')->latest()->paginate()),
            // 'donates' => DonateResource::collection(Donate::whereNotIn('status',[2])->orderBy('remaining_points', 'desc')->latest()->paginate()),
        ]);
    }

    public function getMoreAdvertisings()
    {
        $adverts = Support::where('status',1)->orderBy('remaining_views', 'DESC')->latest()->paginate();
        $advertsResource = SupportResource::collection($adverts);

        return response()->json([
            'success' => true,
            'adverts'    => $adverts
        ]);
    }   

    public function create()
    {
        $authUser = auth()->user();
        return response()->json([
            'Supporter' => new UserResource($authUser),
        ]);
    }

    public function advertisesIndex()
    {
        return response()->json([
            'advertises' => SupportResource::collection(Support::latest()->paginate()),
        ]);
    }

    /**
     * Get advertises for sidebar widget (limited to 5 items).
     */
    public function widget()
    {
        $advertises = Support::where('status', 1)
            ->where('remaining_views', '>', 0)
            ->orderBy('remaining_views', 'DESC')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'advertises' => SupportResource::collection($advertises),
        ]);
    }

    public function storeAdvertise(Request $request)
    {
        $validated = $request->validate([
            'supporter' => '',
            'amounts' => 'required',
            'duration' => 'required',
            'total_views' => 'required',
            'transfer_date' => 'required',
            'transfer_time' => 'required',
            'slip' => 'required|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'media_image' => 'required|image|mimes:jpg,jpeg,png,gif,svg|max:4096',
        ]);

        try {
            if($request->file('slip')) {
                $slip_file = $request->file('slip');
                $slip_filename =  uniqid().'.'.$slip_file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/supports/slips', $slip_file, $slip_filename);
            }
            if($request->file('media_image')) {
                $media_file = $request->file('media_image');
                $media_filename =  uniqid().'.'.$media_file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/supports/medias', $media_file, $media_filename);
            }

            $newSupport                     = new Support();
            $newSupport->user_id            = auth()->id();
            $newSupport->supporter          = $validated['supporter'];
            $newSupport->amounts            = $validated['amounts'];
            $newSupport->duration           = $validated['duration'];
            $newSupport->total_views        = $validated['total_views'];
            $newSupport->remaining_views    = $validated['total_views'];
            $newSupport->transfer_date      = Carbon::parse($request->transfer_date);
            $newSupport->transfer_time      = $validated['transfer_time'];
            $newSupport->slip               = $slip_filename;
            $newSupport->media_image        = $media_filename;
            $newSupport->status             = 0;
            $newSupport->save();

            $activity = new Activity();
            $activity->user_id = $newSupport->user_id;
            $activity->activity_type = ActivityType::CREATE_ADVERTISE->value;
            $activity->activityable()->associate($newSupport);
            $activity->save();

            // return to_route('/');
            return response()->json([
                'success' => true,
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }

    }


    public function viewAdvertise(Support $advert)
    {
        if($advert->remaining_views < 1){
            return response()->json([
                'success' => false,
                'advert'    => new SupportResource($advert),
                'message' => 'จำนวนการแสดงโฆษณาครบแล้ว',
            ], 404);
        }

        try {

            $authUser = auth()->user();

            $advert->viewers()->attach($authUser->id);
            $advert->decrement('remaining_views', 1);
            $authUser->increment('wallet', $advert->duration*0.04);
            $authUser->decrement('pp', $advert->duration*20);

            $suggesterCode = $authUser->suggester_code ?? 99999999;
            $suggester = User::where('personal_code', $suggesterCode)->first() ?? User::where('personal_code', 99999999)->first();
            if($suggester) {
                $suggester->increment('wallet', $advert->duration*0.05);
            }

            $activity = new Activity();
            $activity->user_id = $authUser->id;
            $activity->activity_type = ActivityType::VIEW_ADVERTISE->value;
            $activity->activityable()->associate($advert->supportViewers()->where('user_id', $authUser->id)->latest()->first());
            $activity->save();

            return response()->json([
                'success'   => true,
                'advert'    => new SupportResource($advert),
                'message'   => 'success'
            ], 200);
        } catch (\Throwable $th) {
            // throw $th;
            return response()->json([
                'success' => false,
                'advert'    => new SupportResource($advert),
                'message' => $th->getMessage(),
            ], 500);
        }

    }

    public function approveAdvertise(Support $advert)
    {
        try {
            $advert->status = 1;
            $advert->save();

            // $activity = new Activity();
            // $activity->user_id = $advert->supporter;
            // $activity->activity_type = 'approveadvertise';
            // $activity->activityable()->associate($advert);
            // $activity->save();

            return response()->json([
                'success' => true,
                'message' => 'success'
            ], 200);
        } catch (\Throwable $th) {
            // throw $th;
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function rejectAdvertise(Support $advert)
    {
        try {
            $advert->status = 2;
            $advert->save();

            // $activity = new Activity();
            // $activity->user_id = $advert->supporter;
            // $activity->activity_type = 'rejectadvertise';
            // $activity->activityable()->associate($advert);
            // $activity->save();

            return response()->json([
                'success' => true,
                'message' => 'success'
            ], 200);
        } catch (\Throwable $th) {
            // throw $th;
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

}
