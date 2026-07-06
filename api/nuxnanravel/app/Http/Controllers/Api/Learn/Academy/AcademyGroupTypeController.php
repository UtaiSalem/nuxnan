<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Constants\AcademyGroupPermissions;
use App\Constants\AcademyGroupTypes;
use App\Http\Controllers\Controller;

class AcademyGroupTypeController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => AcademyGroupTypes::all(),
        ]);
    }

    public function permissions()
    {
        return response()->json([
            'success' => true,
            'data' => AcademyGroupPermissions::all(),
        ]);
    }
}
