<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TeacherProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(
            $request->user()->teacherProfile->load(['subjects', 'cities', 'deliveryModes'])
        );
    }

    public function update(Request $request)
    {
        $request->validate([
            'bio' => 'nullable|string|max:2000',
            'years_of_experience' => 'nullable|integer|min:0',
            'country' => 'nullable|string',
            'time_zone' => 'nullable|string',
            'photo_url' => 'nullable|url',
            'meta' => 'nullable|array',
        ]);

        $profile = $request->user()->teacherProfile;

        $profile->update($request->only([
            'bio',
            'years_of_experience',
            'country',
            'time_zone',
            'photo_url',
            'meta',
        ]));

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile,
        ]);
    }
}
