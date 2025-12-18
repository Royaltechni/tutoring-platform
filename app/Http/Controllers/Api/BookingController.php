<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\TeacherProfile;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = $request->user()->bookings()
            ->with(['teacherProfile.user', 'lessonSessions', 'deliveryMode'])
            ->latest()
            ->paginate(10);
            
        return response()->json($bookings);
    }

    public function show(Request $request, string $uuid)
    {
        $booking = $request->user()->bookings()
            ->where('uuid', $uuid)
            ->with(['teacherProfile.user', 'lessonSessions', 'payments'])
            ->firstOrFail();

        return response()->json($booking);
    }

    public function store(StoreBookingRequest $request)
    {
        $teacher = TeacherProfile::findOrFail($request->teacher_profile_id);
        
        $mode = $teacher->deliveryModes()
            ->where('lesson_delivery_modes.id', $request->lesson_delivery_mode_id)
            ->first();

        $price = $mode->pivot->price_per_hour;
        $currency = $mode->pivot->currency;
        
        DB::beginTransaction();

        try {
            $booking = Booking::create([
                'uuid' => Str::uuid(),
                'teacher_profile_id' => $teacher->id,
                'user_id' => $request->user()->id,
                'lesson_delivery_mode_id' => $mode->id,
                'city_id' => $request->city_id,
                'total_amount' => $price,
                'currency' => $currency,
                'status' => 'pending',
                'notes' => $request->notes
            ]);

            $startTime = \Carbon\Carbon::parse($request->scheduled_start_at);
            $endTime = $startTime->copy()->addHour();

            $booking->lessonSessions()->create([
                'teacher_profile_id' => $teacher->id,
                'user_id' => $request->user()->id,
                'scheduled_start_at' => $startTime,
                'scheduled_end_at' => $endTime,
                'status' => 'scheduled'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Booking request sent successfully.',
                'booking' => $booking->load('lessonSessions')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Booking creation failed.'], 500);
        }
    }

    public function cancel(Request $request, string $uuid)
    {
        $booking = $request->user()->bookings()
            ->where('uuid', $uuid)
            ->firstOrFail();

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json(['error' => 'Cannot cancel this booking in its current state.'], 400);
        }

        $booking->update(['status' => 'cancelled']);
        $booking->lessonSessions()->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Booking cancelled.']);
    }
}
