<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;

class DashboardController extends Controller
{
    /**
     * عرض لوحة تحكّم الأدمن
     */
    public function index()
    {
        // إجمالي الحجوزات
        $totalBookings = Booking::count();

        // حسب الحالة
        $pendingBookings   = Booking::where('status', 'pending')->count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();
        $cancelledBookings = Booking::where('status', 'cancelled')->count();

        // حجوزات اليوم
        $todayBookings = Booking::whereDate('created_at', today())->count();

        // حجوزات هذا الشهر
        $thisMonthBookings = Booking::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        // إجمالي المبالغ (من الحجوزات المؤكَّدة فقط)
        $totalRevenue = Booking::where('status', 'confirmed')
            ->sum('total_amount');

        // مبالغ هذا الشهر (من المؤكَّدة فقط)
        $thisMonthRevenue = Booking::where('status', 'confirmed')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        // آخر 5 حجوزات
        $latestBookings = Booking::with(['student', 'teacher'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'totalBookings'     => $totalBookings,
            'pendingBookings'   => $pendingBookings,
            'confirmedBookings' => $confirmedBookings,
            'cancelledBookings' => $cancelledBookings,
            'todayBookings'     => $todayBookings,
            'thisMonthBookings' => $thisMonthBookings,
            'totalRevenue'      => $totalRevenue,
            'thisMonthRevenue'  => $thisMonthRevenue,
            'latestBookings'    => $latestBookings,
        ]);
    }
}
