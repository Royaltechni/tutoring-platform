<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * إحصائيات سريعة عن حجوزات المعلم الحالي.
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        // نفترض إن عندك علاقة teacherProfile على موديل User
        $teacherProfile = $user->teacherProfile;

        if (! $teacherProfile) {
            return response()->json([
                'message' => 'Teacher profile not found',
            ], 404);
        }

        $baseQuery = Booking::where('teacher_profile_id', $teacherProfile->id);

        $now = Carbon::now();

        // إجمالي الحجوزات
        $totalBookings = (clone $baseQuery)->count();

        // إجمالي المبالغ
        $totalAmount = (clone $baseQuery)->sum('total_amount');

        // حجوزات اليوم
        $todayBookings = (clone $baseQuery)
            ->whereDate('created_at', $now->toDateString())
            ->count();

        // عدد الحجوزات حسب الحالة
        $pendingCount = (clone $baseQuery)->where('status', 'pending')->count();
        $confirmedCount = (clone $baseQuery)->where('status', 'confirmed')->count();
        $cancelledCount = (clone $baseQuery)->where('status', 'cancelled')->count();

        return response()->json([
            'teacher_profile_id' => $teacherProfile->id,
            'stats' => [
                'total_bookings'   => $totalBookings,
                'total_amount'     => $totalAmount,
                'today_bookings'   => $todayBookings,
                'by_status' => [
                    'pending'   => $pendingCount,
                    'confirmed' => $confirmedCount,
                    'cancelled' => $cancelledCount,
                ],
            ],
        ]);
    }
}
