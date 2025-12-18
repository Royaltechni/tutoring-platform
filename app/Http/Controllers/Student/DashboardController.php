<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\Booking;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * لوحة تحكم الطالب
     */
    public function index()
    {
        // ✅ تأمين بسيط: نتأكد إنه طالب
        if (Auth::check() && Auth::user()->role !== 'student') {
            abort(403);
        }

        $student = Auth::user();

        /**
         * ✅ تحديد عمود ربط الطالب بالحجز تلقائيًا (عشان ما نكسرش أي بيئة)
         */
        $table = (new Booking())->getTable();
        $studentKey = null;

        $possibleKeys = ['student_id', 'student_user_id', 'user_id', 'studentId', 'studentID'];
        foreach ($possibleKeys as $key) {
            if (Schema::hasColumn($table, $key)) {
                $studentKey = $key;
                break;
            }
        }
        $studentKey = $studentKey ?: 'student_id';

        /**
         * ✅ تحديد عمود تاريخ الحجز تلقائيًا (لإحصائيات الشهر)
         * هنفضّل booking_date لو موجود (لأنه واضح عندك في جدول الحجوزات)
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

        // ✅ Query أساسي لحجوزات الطالب
        $baseQuery = Booking::where($studentKey, $student->id);

        // ✅ كروت الملخص
        $totalBookings = (clone $baseQuery)->count();

        $confirmedBookings = (clone $baseQuery)->where('status', 'confirmed')->count();
        $pendingBookings   = (clone $baseQuery)->where('status', 'pending')->count();
        $cancelledBookings = (clone $baseQuery)->whereIn('status', ['cancelled', 'canceled'])->count();

        // ✅ أقرب/آخر حجز (أحدث حجز غير ملغي)
        $latestBooking = (clone $baseQuery)
            ->whereNotIn('status', ['cancelled', 'canceled'])
            ->orderByDesc('created_at')
            ->first();

        // ✅ المرحلة 2: آخر الحجوزات
        $recentBookings = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->limit(7)
            ->get();

        // ✅ تنبيه
        $hasPending = $pendingBookings > 0;

        /**
         * =========================
         * ✅ المرحلة 3: إحصائيات الشهر + Progress + رسالة تحفيزية
         * =========================
         */
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd   = $now->copy()->endOfMonth();

        $monthQuery = (clone $baseQuery)
            ->whereBetween($dateColumn, [$monthStart, $monthEnd]);

        $bookingsThisMonth = (clone $monthQuery)->count();
        $confirmedThisMonth = (clone $monthQuery)->where('status', 'confirmed')->count();
        $pendingThisMonth   = (clone $monthQuery)->where('status', 'pending')->count();

        // ✅ Progress (نسبة تأكيد الحجوزات هذا الشهر)
        $progressPercent = $bookingsThisMonth > 0
            ? (int) round(($confirmedThisMonth / $bookingsThisMonth) * 100)
            : 0;

        // ✅ رسالة تحفيزية ذكية حسب النشاط
        $motivationTitle = '';
        $motivationText  = '';
        $motivationType  = 'info'; // bootstrap: success / warning / info / danger

        if ($totalBookings === 0) {
            $motivationType  = 'info';
            $motivationTitle = 'ابدأ رحلتك 🎯';
            $motivationText  = 'لا يوجد لديك حجوزات بعد. اضغط على "ابحث عن معلّم" وابدأ أول حجز بسهولة.';
        } elseif ($pendingBookings > 0) {
            $motivationType  = 'warning';
            $motivationTitle = 'تنبيه بسيط ⏳';
            $motivationText  = 'لديك حجوزات معلّقة. راجع صفحة الحجوزات لمتابعة التأكيد أو الإلغاء.';
        } else {
            // آخر نشاط (آخر حجز)
            $lastActivity = (clone $baseQuery)->orderByDesc('created_at')->first();
            $days = $lastActivity && $lastActivity->created_at
                ? Carbon::parse($lastActivity->created_at)->diffInDays($now)
                : 0;

            if ($days >= 14) {
                $motivationType  = 'info';
                $motivationTitle = 'نفتقد نشاطك 🌟';
                $motivationText  = 'مرّ وقت على آخر حجز. جرّب حجز جلسة جديدة للمحافظة على الاستمرارية.';
            } else {
                $motivationType  = 'success';
                $motivationTitle = 'أحسنت 👏';
                $motivationText  = 'أداؤك ممتاز! استمر على نفس الوتيرة، واستفد من متابعة الحجوزات أولاً بأول.';
            }
        }

        return view('student.dashboard', compact(
            'totalBookings',
            'confirmedBookings',
            'pendingBookings',
            'cancelledBookings',
            'latestBooking',
            'recentBookings',
            'hasPending',
            // stage 3
            'bookingsThisMonth',
            'confirmedThisMonth',
            'pendingThisMonth',
            'progressPercent',
            'motivationTitle',
            'motivationText',
            'motivationType'
        ));
    }
}
