<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAdminBookingStatusRequest;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BookingController extends Controller
{
    /**
     * عرض كل حجوزات المنصة مع فلاتر + Pagination للإدارة.
     */
    public function index(Request $request)
    {
        // ✅ Validation بسيط على بارامترات الفلترة
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,cancelled',
            'teacher_profile_id' => 'sometimes|integer|exists:teacher_profiles,id',
            'user_id' => 'sometimes|integer|exists:users,id',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Booking::query()
            ->orderByDesc('created_at');

        // ✅ فلتر بالحالة
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // ✅ فلتر بالمعلم
        if (isset($validated['teacher_profile_id'])) {
            $query->where('teacher_profile_id', $validated['teacher_profile_id']);
        }

        // ✅ فلتر بالطالب
        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        // ✅ فلتر بالتاريخ (من / إلى)
        if (isset($validated['date_from'])) {
            $query->whereDate('created_at', '>=', Carbon::parse($validated['date_from'])->toDateString());
        }

        if (isset($validated['date_to'])) {
            $query->whereDate('created_at', '<=', Carbon::parse($validated['date_to'])->toDateString());
        }

        // عدد العناصر في الصفحة (افتراضي 10)
        $perPage = $validated['per_page'] ?? 10;

        // ✅ Laravel بيرجع Pagination JSON جاهز
        $bookings = $query->paginate($perPage);

        return response()->json($bookings);
    }

    /**
     * عرض تفاصيل حجز واحد للإدارة عن طريق الـ UUID.
     */
    public function show(string $uuid)
    {
        $booking = Booking::where('uuid', $uuid)->first();

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
     * تحديث حالة الحجز من لوحة الإدارة.
     */
    public function updateStatus(UpdateAdminBookingStatusRequest $request, string $uuid)
    {
        $booking = Booking::where('uuid', $uuid)->first();

        if (! $booking) {
            return response()->json([
                'message' => 'Booking not found',
            ], 404);
        }

        $data = $request->validated();

        $booking->status = $data['status'];
        $booking->save();

        return response()->json([
            'message' => 'Booking status updated successfully by admin',
            'data'    => $booking,
        ]);
    }
}
