<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Models\Advert;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Enums\ActivityType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SupportController extends Controller
{
    /**
     * Handle "Support Plearnd" (Money -> Points) transaction.
     */
    public function storePlearndSupport(Request $request)
    {
        $validated = $request->validate([
            'amounts' => 'required|numeric|min:1',
            'transfer_date' => 'required',
            'transfer_time' => 'required',
            'slip' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
        ]);

        try {
            $slip_filename = null;
            if($request->file('slip')) {
                $slip_file = $request->file('slip');
                $slip_filename =  uniqid().'.'.$slip_file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/adverts/slips', $slip_file, $slip_filename);
            }

            $newAdvert = new Advert();
            $newAdvert->user_id = auth()->id();
            $newAdvert->amounts = $validated['amounts'];
            $newAdvert->duration = 0; // Not an advertisement
            $newAdvert->total_views = 0;
            $newAdvert->remaining_views = 0;
            $newAdvert->transfer_date = Carbon::parse($request->transfer_date);
            $newAdvert->transfer_time = $validated['transfer_time'];
            $newAdvert->slip = $slip_filename;
            $newAdvert->media_image = ''; // No media for direct support
            $newAdvert->exchange_points = $validated['amounts'] * 684; // Calculate Points

            if ($slip_filename) {
                $newAdvert->status = 0; // Pending
            } else {
                // Wallet Payment
                $user = auth()->user();
                if ($user->wallet < $validated['amounts']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ยอดเงินในกระเป๋าไม่เพียงพอ',
                    ], 402);
                }
                
                $user->decrement('wallet', $validated['amounts']);
                $user->increment('pp', $newAdvert->exchange_points); // Give points immediately
                $newAdvert->status = 1; // Approved
            }

            $newAdvert->save();

            $activity = new Activity();
            $activity->user_id = $newAdvert->user_id;
            $activity->activity_type = ActivityType::CREATE_ADVERTISE->value; // Consider adding a new ActivityType for SUPPORT if possible
            $activity->activityable()->associate($newAdvert);
            $activity->save();

            return response()->json([
                'success' => true,
                'message' => 'ขอบคุณสำหรับการสนับสนุน',
                'points' => $newAdvert->exchange_points
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
