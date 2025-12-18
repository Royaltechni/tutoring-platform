<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * إحصائيات سريعة عن حجوزات الطالب الحالي.
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        // نشتغل على حجوزات هذا الطالب فقط
        $baseQuery = Booking::where('user_id', $user->id);

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
        $pendingCount   = (clone $baseQuery)->where('status', 'pending')->count();
        $confirmedCount = (clone $baseQuery)->where('status', 'confirmed')->count();
        $cancelledCount = (clone $baseQuery)->where('status', 'cancelled')->count();

        return response()->json([
            'student_id' => $user->id,
            'stats' => [
                'total_bookings' => $totalBookings,
                'total_amount'   => $totalAmount,
                'today_bookings' => $todayBookings,
                'by_status' => [
                    'pending'   => $pendingCount,
                    'confirmed' => $confirmedCount,
                    'cancelled' => $cancelledCount,
                ],
            ],
        ]);
    }
}
