<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TeacherCityController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->teacherProfile->cities
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'city_ids' => 'required|array',
            'city_ids.*' => 'exists:cities,id',
        ]);

        $profile = $request->user()->teacherProfile;

        $profile->cities()->syncWithoutDetaching($request->city_ids);

        return response()->json([
            'message' => 'Coverage cities updated',
            'cities' => $profile->cities,
        ]);
    }

    public function destroy(Request $request, string $cityId)
    {
        $request->user()->teacherProfile->cities()->detach($cityId);

        return response()->json(['message' => 'City removed']);
    }
}
