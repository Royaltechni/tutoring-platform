<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /**
     * قائمة حجوزات الطالب الحالي
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::with(['teacherProfile', 'city'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($bookings);
    }

    /**
     * إنشاء حجز جديد للطالب الحالي
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'teacher_profile_id'      => ['required', 'integer', 'exists:teacher_profiles,id'],
            'lesson_delivery_mode_id' => ['nullable', 'integer'],
            'city_id'                 => ['required', 'integer', 'exists:cities,id'],
            'total_amount'            => ['required', 'numeric', 'min:0'],
            'currency'                => ['required', 'string', 'max:10'],
            'booking_date'            => ['required', 'date'],
            'address'                 => ['nullable', 'string', 'max:255'],
            'notes'                   => ['nullable', 'string'],
        ]);

        $booking = Booking::create([
            'uuid'                     => (string) Str::uuid(),
            'user_id'                  => $user->id,
            'teacher_profile_id'       => $data['teacher_profile_id'],
            'lesson_delivery_mode_id'  => $data['lesson_delivery_mode_id'] ?? null,
            'city_id'                  => $data['city_id'],
            'total_amount'             => $data['total_amount'],
            'currency'                 => $data['currency'],
            'booking_date'             => $data['booking_date'],
            'address'                  => $data['address'] ?? null,
            'notes'                    => $data['notes'] ?? null,
            'status'                   => 'pending',
        ]);

        $booking->load(['teacherProfile', 'city']);

        return response()->json([
            'message' => 'Booking created successfully',
            'data'    => $booking,
        ], 201);
    }
}
