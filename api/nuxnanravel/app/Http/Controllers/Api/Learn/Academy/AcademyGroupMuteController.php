<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\AcademyGroup;
use App\Models\UserMutedGroup;
use Illuminate\Http\Request;

class AcademyGroupMuteController extends Controller
{
    public function mute(Request $request, AcademyGroup $academyGroup)
    {
        UserMutedGroup::firstOrCreate([
            'user_id'          => $request->user()->id,
            'academy_group_id' => $academyGroup->id,
        ]);

        return response()->json(['success' => true, 'muted' => true]);
    }

    public function unmute(Request $request, AcademyGroup $academyGroup)
    {
        UserMutedGroup::where('user_id', $request->user()->id)
            ->where('academy_group_id', $academyGroup->id)
            ->delete();

        return response()->json(['success' => true, 'muted' => false]);
    }
}
