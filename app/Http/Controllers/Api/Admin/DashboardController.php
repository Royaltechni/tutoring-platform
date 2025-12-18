<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * إحصائيات عامة للإدارة عن كل الحجوزات في النظام.
     */
    public function summary(Request $request)
    {
        $now = Carbon::now();

        $baseQuery = Booking::query();

        // إجمالي الحجوزات في النظام
        $totalBookings = (clone $baseQuery)->count();

        // إجمالي المبالغ في كل الحجوزات
        $totalAmount = (clone $baseQuery)->sum('total_amount');

        // حجوزات اليوم فقط
        $todayBookings = (clone $baseQuery)
            ->whereDate('created_at', $now->toDateString())
            ->count();

        // حجوزات آخر 7 أيام
        $last7DaysBookings = (clone $baseQuery)
            ->where('created_at', '>=', $now->copy()->subDays(7))
            ->count();

        // عدد الحجوزات حسب الحالة
        $pendingCount   = (clone $baseQuery)->where('status', 'pending')->count();
        $confirmedCount = (clone $baseQuery)->where('status', 'confirmed')->count();
        $cancelledCount = (clone $baseQuery)->where('status', 'cancelled')->count();

        return response()->json([
            'stats' => [
                'total_bookings'       => $totalBookings,
                'total_amount'         => $totalAmount,
                'today_bookings'       => $todayBookings,
                'last_7_days_bookings' => $last7DaysBookings,
                'by_status' => [
                    'pending'   => $pendingCount,
                    'confirmed' => $confirmedCount,
                    'cancelled' => $cancelledCount,
                ],
            ],
        ]);
    }
}
