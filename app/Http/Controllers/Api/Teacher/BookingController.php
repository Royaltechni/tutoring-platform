<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * عرض كل الحجوزات الخاصة بالمعلم الحالي
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // نجيب الـ teacher profile المرتبط بالمعلّم
        $teacherProfile = $user->teacherProfile;

        if (! $teacherProfile) {
            return response()->json([
                'message' => 'Teacher profile not found',
            ], 404);
        }

        $query = Booking::where('teacher_profile_id', $teacherProfile->id);

        // ✅ فلترة بالحالة (اختياري)
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // ✅ Pagination
        $perPage = $request->get('per_page', 10);

        $bookings = $query
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($bookings);
    }

    /**
     * ✅ عرض حجز واحد بالتفصيل للمعلم الحالي عن طريق UUID.
     */
    public function show(Request $request, string $uuid)
    {
        $user = $request->user();

        $teacherProfile = $user->teacherProfile;

        if (! $teacherProfile) {
            return response()->json([
                'message' => 'Teacher profile not found',
            ], 404);
        }

        $booking = Booking::where('uuid', $uuid)
            ->where('teacher_profile_id', $teacherProfile->id)
            ->first();

        if (! $booking) {
            return response()->json([
                'message' => 'Booking not found',
            ], 404);
        }

        return response()->json([
            'data' => $booking,
        ]);
    }

    /**
     * ✅ تحديث حالة الحجز من جهة المعلم
     */
    public function updateStatus(Request $request, string $uuid)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        $user = $request->user();

        $teacherProfile = $user->teacherProfile;

        if (! $teacherProfile) {
            return response()->json([
                'message' => 'Teacher profile not found',
            ], 404);
        }

        $booking = Booking::where('uuid', $uuid)
            ->where('teacher_profile_id', $teacherProfile->id)
            ->first();

        if (! $booking) {
            return response()->json([
                'message' => 'Booking not found',
            ], 404);
        }

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'message' => 'Booking status updated successfully by teacher',
            'data'    => $booking,
        ]);
    }
}
