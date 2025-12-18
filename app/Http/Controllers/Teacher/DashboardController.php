<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $teacher = Auth::user();

        $bookingModel = new Booking();
        $table = $bookingModel->getTable();

        /**
         * ✅ تحديد عمود ربط الطالب بالحجز تلقائيًا
         * (عشان اسم الطالب يظهر بدون ما نعتمد على student_name)
         */
        $studentKey = null;
        $possibleStudentKeys = ['student_id', 'student_user_id', 'user_id'];
        foreach ($possibleStudentKeys as $key) {
            if (Schema::hasColumn($table, $key)) {
                $studentKey = $key;
                break;
            }
        }

        /**
         * ✅ تحديد عمود تاريخ الجلسة/الحجز (حجوزات اليوم)
         * نفضّل booking_date لو موجود
         */
        $dateColumn = null;
        $possibleDateCols = ['booking_date', 'session_date', 'date', 'scheduled_at', 'start_at', 'created_at'];
        foreach ($possibleDateCols as $col) {
            if (Schema::hasColumn($table, $col)) {
                $dateColumn = $col;
                break;
            }
        }
        $dateColumn = $dateColumn ?: 'created_at';

        // كل حجوزات هذا المعلم فقط
        $baseQuery = Booking::where('teacher_id', $teacher->id);

        $totalBookings = (clone $baseQuery)->count();

        // ✅ حجوزات اليوم: على تاريخ الحجز/الجلسة لو متاح
        $todayBookings = (clone $baseQuery)
            ->whereDate($dateColumn, now()->toDateString())
            ->count();

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $byStatus = [
            'pending'   => $statusCounts['pending']   ?? 0,
            'confirmed' => $statusCounts['confirmed'] ?? 0,
            'cancelled' => $statusCounts['cancelled'] ?? ($statusCounts['canceled'] ?? 0),
        ];

        // ✅ أحدث الحجوزات
        $latestBookings = (clone $baseQuery)
            ->latest()
            ->take(5)
            ->get();

        /**
         * ✅ جلب أسماء الطلاب بطريقة آمنة (بدون الاعتماد على student_name)
         * هنكوّن map: [student_id => student_name]
         */
        $studentsMap = collect();

        if (!empty($studentKey)) {
            $studentIds = $latestBookings
                ->pluck($studentKey)
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (!empty($studentIds)) {
                $studentsMap = User::whereIn('id', $studentIds)
                    ->pluck('name', 'id'); // [id => name]
            }
        }

        return view('teacher.dashboard', compact(
            'teacher',
            'totalBookings',
            'todayBookings',
            'byStatus',
            'latestBookings',
            'studentsMap',
            'studentKey',
            'dateColumn'
        ));
    }
}
