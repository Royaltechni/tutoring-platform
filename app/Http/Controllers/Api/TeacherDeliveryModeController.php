<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TeacherDeliveryModeController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->teacherProfile->deliveryModes
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'lesson_delivery_mode_id' => 'required|exists:lesson_delivery_modes,id',
            'price_per_hour' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'is_active' => 'boolean',
        ]);

        $profile = $request->user()->teacherProfile;

        $profile->deliveryModes()->syncWithoutDetaching([
            $request->lesson_delivery_mode_id => [
                'price_per_hour' => $request->price_per_hour,
                'currency' => $request->currency,
                'is_active' => $request->boolean('is_active', true),
            ],
        ]);

        return response()->json([
            'message' => 'Delivery mode and pricing saved',
            'modes' => $profile->load('deliveryModes')->deliveryModes,
        ]);
    }

    public function destroy(Request $request, string $modeId)
    {
        $request->user()->teacherProfile->deliveryModes()->detach($modeId);

        return response()->json(['message' => 'Delivery mode removed']);
    }
}
