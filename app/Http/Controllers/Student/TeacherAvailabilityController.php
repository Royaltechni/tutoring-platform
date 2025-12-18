<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TeacherProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TeacherAvailabilityController extends Controller
{
    /**
     * GET /student/teachers/{teacher}/available-slots?date=YYYY-MM-DD&mode=online|onsite&duration=30|60
     */
    public function availableSlots(Request $request, $teacherId)
    {
        $date     = $request->query('date');
        $mode     = $request->query('mode', 'online'); // UI فقط (لن نفلتر به في التعارض)
        $duration = (int) $request->query('duration', 60);

        if (!$date) {
            return response()->json(['slots' => []]);
        }

        if (!in_array($duration, [30, 60], true)) {
            $duration = 60;
        }

        $teacher = User::where('role', 'teacher')->findOrFail($teacherId);
        $profile = TeacherProfile::where('user_id', $teacher->id)->first();

        if (!$profile) {
            return response()->json(['slots' => []]);
        }

        // ✅ فاصل الراحة بين الحصص (من teacher_profiles.break_minutes)
        $breakMinutes = $this->sanitizeBreakMinutes($profile->break_minutes ?? 0);

        // ✅ step ذكي حسب break_minutes (عشان يطلع 6:45 / 6:50 ... إلخ)
        $stepMinutes = $this->computeStepMinutes($breakMinutes);

        /**
         * ✅ 1) نولّد السلات بخطوة stepMinutes
         * مع ضمان إن (وقت البداية + مدة الحصة) <= نهاية دوام المعلّم
         */
        $allowedTimes = $this->allowedTimesFromProfile($profile, $date, $mode, $stepMinutes, $duration);

        if (empty($allowedTimes)) {
            return response()->json(['slots' => []]);
        }

        /**
         * ✅ 2) فلترة أي وقت متداخل مع حجوزات قائمة (pending + confirmed)
         * ⚠️ لا نفلتر بالـ mode: أي حجز يمنع نفس الوقت (أونلاين/حضوري)
         * ✅ مع احتساب فاصل الراحة بعد كل حجز + بعد الحجز الجديد أيضًا
         */
        $available = $this->removeBookedTimes(
            $allowedTimes,
            $teacher->id,
            $profile,
            $date,
            $duration,
            $breakMinutes
        );

        // تنسيق للـ dropdown
        $slots = array_map(function ($t) {
            return [
                'value' => $t,
                'label' => Carbon::createFromFormat('H:i', $t)->format('h:i A'),
            ];
        }, $available);

        return response()->json([
            'slots' => $slots,
        ]);
    }

    /**
     * ✅ يشيل الأوقات اللي عليها حجوزات بالفعل (pending/confirmed) بناءً على التداخل
     * - لا يعتمد على mode
     * - busyIntervals فيها break بعد الحجوزات القائمة
     * - وهنا نضيف break للحجز الجديد أيضًا
     */
    private function removeBookedTimes(
        array $allowedTimes,
        int $teacherId,
        TeacherProfile $profile,
        string $date,
        int $slotDuration,
        int $breakMinutes
    ): array {
        if (empty($allowedTimes)) return [];

        $tz = $profile->time_zone ?: 'Asia/Dubai';

        $busyIntervals = $this->busyIntervals($teacherId, $profile, $date, $breakMinutes);

        $available = [];
        foreach ($allowedTimes as $t) {
            try {
                $slotStart = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $t, $tz);
            } catch (\Throwable $e) {
                continue;
            }

            // ✅ الحجز الجديد: مدة الحصة + فاصل الراحة (حتى لا يبدأ حجز جديد قبل انتهاء الراحة)
            $slotEnd = $slotStart->copy()->addMinutes($slotDuration + max(0, $breakMinutes));

            if ($this->overlapsAny($slotStart, $slotEnd, $busyIntervals)) {
                continue;
            }

            $available[] = $t;
        }

        return array_values($available);
    }

    /**
     * ✅ intervals للحجوزات القائمة في اليوم على توقيت المعلم
     * - لا نفلتر بالـ mode هنا
     * - نضيف break_minutes بعد كل حجز كفترة مشغولة إضافية
     */
    private function busyIntervals(int $teacherId, TeacherProfile $profile, string $date, int $breakMinutes): array
    {
        $tz = $profile->time_zone ?: 'Asia/Dubai';
        [$rangeStart, $rangeEnd] = $this->dayRangeWithBuffer($date, $tz, 14);

        $bookings = Booking::query()
            ->where('teacher_id', $teacherId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('first_lesson_at', [
                $rangeStart->toDateTimeString(),
                $rangeEnd->toDateTimeString(),
            ])
            ->get(['first_lesson_at', 'duration_minutes']);

        $intervals = [];

        foreach ($bookings as $b) {
            try {
                $start = Carbon::parse($b->first_lesson_at)->timezone($tz);

                // نتأكد إن الحجز فعلاً في نفس اليوم بالتوقيت المحلي للمعلم
                if ($start->format('Y-m-d') !== $date) {
                    continue;
                }

                $dur = (int) ($b->duration_minutes ?? 60);
                if (!in_array($dur, [30, 60], true)) $dur = 60;

                // نهاية الحجز + فاصل الراحة
                $end = $start->copy()->addMinutes($dur + max(0, $breakMinutes));

                $intervals[] = [$start, $end];
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $intervals;
    }

    /**
     * ✅ هل الفترة [start,end) تتداخل مع أي Interval؟
     */
    private function overlapsAny(Carbon $start, Carbon $end, array $busyIntervals): bool
    {
        foreach ($busyIntervals as $it) {
            if (!is_array($it) || count($it) !== 2) continue;

            [$bStart, $bEnd] = $it;

            if (!$bStart instanceof Carbon || !$bEnd instanceof Carbon) continue;

            // Overlap: start < bEnd && end > bStart
            if ($start->lt($bEnd) && $end->gt($bStart)) {
                return true;
            }
        }
        return false;
    }

    private function dayRangeWithBuffer(string $date, string $tz, int $bufferHours = 14): array
    {
        $start = Carbon::parse($date, $tz)->startOfDay()->subHours($bufferHours);
        $end   = Carbon::parse($date, $tz)->endOfDay()->addHours($bufferHours);
        return [$start, $end];
    }

    /**
     * ✅ يرجّع allowed times فقط (H:i) من جدول التوفر
     * - $stepMinutes = خطوة توليد أوقات البداية
     * - $requiredDuration = مدة الحصة المختارة (30/60) لضمان إن وقت البداية يكفي حتى نهاية الدوام
     */
    private function allowedTimesFromProfile(
        TeacherProfile $profile,
        string $date,
        string $mode,
        int $stepMinutes,
        int $requiredDuration
    ): array {
        $raw = $profile->availability
            ?? $profile->availability_schedule
            ?? $profile->weekly_availability
            ?? $profile->schedule
            ?? null;

        $schedule = $this->asArray($raw);
        if (empty($schedule)) return [];

        $tz = $profile->time_zone ?: 'Asia/Dubai';
        $day = Carbon::parse($date, $tz);

        $shortMap = [6=>'sat', 0=>'sun', 1=>'mon', 2=>'tue', 3=>'wed', 4=>'thu', 5=>'fri'];
        $longMap  = [6=>'saturday', 0=>'sunday', 1=>'monday', 2=>'tuesday', 3=>'wednesday', 4=>'thursday', 5=>'friday'];

        $dayShort = $shortMap[$day->dayOfWeek] ?? null;
        $dayLong  = $longMap[$day->dayOfWeek] ?? null;

        $dayConf = null;
        if ($dayShort && isset($schedule[$dayShort])) $dayConf = $schedule[$dayShort];
        if (!$dayConf && $dayLong && isset($schedule[$dayLong])) $dayConf = $schedule[$dayLong];
        if (!$dayConf && isset($schedule[$day->dayOfWeek])) $dayConf = $schedule[$day->dayOfWeek];

        $dayConf = $this->asArray($dayConf);
        if (empty($dayConf)) return [];

        $enabled = (bool)($dayConf['enabled'] ?? $dayConf['available'] ?? $dayConf['is_available'] ?? false);
        $from = $dayConf['from'] ?? $dayConf['start'] ?? $dayConf['from_time'] ?? null;
        $to   = $dayConf['to']   ?? $dayConf['end']   ?? $dayConf['to_time']   ?? null;

        if (!$enabled || !$from || !$to) return [];

        $from = $this->normalizeTime($from, $tz);
        $to   = $this->normalizeTime($to, $tz);
        if (!$from || !$to) return [];

        $start = Carbon::parse($date . ' ' . $from, $tz);
        $end   = Carbon::parse($date . ' ' . $to, $tz);

        if ($end->lte($start)) return [];

        if (!in_array($requiredDuration, [30, 60], true)) {
            $requiredDuration = 60;
        }

        // حماية: step لازم يكون منطقي
        if ($stepMinutes < 1) $stepMinutes = 30;
        if ($stepMinutes > 60) $stepMinutes = 60;

        $allowed = [];
        $cursor = $start->copy();

        // ✅ شرط مهم: وقت البداية + مدة الحصة <= نهاية الدوام
        while ($cursor->copy()->addMinutes($requiredDuration)->lte($end)) {
            $allowed[] = $cursor->format('H:i');
            $cursor->addMinutes($stepMinutes);
        }

        return $allowed;
    }

    private function asArray($value): array
    {
        if (is_array($value)) return $value;
        if (is_object($value)) return (array) $value;
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private function normalizeTime($t, string $tz): ?string
    {
        $t = trim((string)$t);
        if ($t === '') return null;

        try {
            if (stripos($t, 'AM') !== false || stripos($t, 'PM') !== false) {
                return Carbon::createFromFormat('h:i A', strtoupper($t), $tz)->format('H:i');
            }

            if (preg_match('/^\d{1,2}:\d{2}$/', $t)) {
                return Carbon::createFromFormat('H:i', $t, $tz)->format('H:i');
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function sanitizeBreakMinutes($v): int
    {
        $v = (int) $v;
        if ($v < 0) $v = 0;

        // حد أقصى عملي
        if ($v > 180) $v = 180;

        return $v;
    }

    /**
     * ✅ خطوة توليد الأوقات بناءً على break_minutes
     * الهدف: يطلع أوقات “منطقية” بعد نهاية الحصة:
     * - 0  => 30 (بسيط)
     * - 15 => 15 (يطلع 6:45)
     * - 20 => 10 (يطلع 6:50)
     * - أي رقم آخر => 5 (مرن وعملي)
     */
    private function computeStepMinutes(int $breakMinutes): int
    {
        if ($breakMinutes <= 0) return 30;
        if ($breakMinutes === 15) return 15;
        if ($breakMinutes === 20) return 10;
        return 5;
    }
}
