<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Notifications\NewBookingForTeacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function index()
    {
        $student = Auth::user();

        $bookings = Booking::with(['city', 'teacherProfile.user'])
            ->where('user_id', $student->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('student.bookings.index', compact('bookings'));
    }

    public function show($id)
    {
        $student = Auth::user();

        $booking = Booking::with(['city', 'teacherProfile.user'])
            ->where('user_id', $student->id)
            ->findOrFail($id);

        return view('student.bookings.show', compact('booking'));
    }

    /**
     * /student/bookings/create?teacher_id=XX
     */
    public function create(Request $request)
    {
        $teacherId = (int) $request->query('teacher_id');

        // ✅ قوائم افتراضية (في حال عدم وجود teacher_id)
        $defaultGrades    = range(1, 12);
        $defaultCurricula = ['UAE', 'British', 'American', 'IB', 'Other'];

        if (!$teacherId) {
            return view('student.bookings.create', [
                'teacher'        => null,
                'teacherProfile' => null,
                'grades'         => $defaultGrades,
                'curricula'      => $defaultCurricula,
                'cities'         => collect([]),
            ]);
        }

        $teacher = User::where('role', 'teacher')
            ->with(['teacherProfile.onsiteCities'])
            ->findOrFail($teacherId);

        $teacherProfile = $teacher->teacherProfile;

        if (!$teacherProfile) {
            return view('student.bookings.create', [
                'teacher'        => $teacher,
                'teacherProfile' => null,
                'grades'         => $defaultGrades,
                'curricula'      => [],
                'cities'         => collect([]),
            ]);
        }

        // ✅ المدن المتاحة للحضور
        $cities = collect([]);
        try {
            if (method_exists($teacherProfile, 'onsiteCities')) {
                $cities = $teacherProfile->onsiteCities()
                    ->orderBy('name_en')
                    ->get(['cities.id', 'cities.name_ar', 'cities.name_en']);
            }
        } catch (\Throwable $e) {
            $cities = collect([]);
        }

        // grades
        $min = $teacherProfile->min_grade;
        $max = $teacherProfile->max_grade;

        $grades = $defaultGrades;
        if (is_numeric($min) && is_numeric($max)) {
            $min = (int) $min;
            $max = (int) $max;

            if ($min < 1)  $min = 1;
            if ($max > 12) $max = 12;
            if ($max < $min) { $tmp = $min; $min = $max; $max = $tmp; }

            $grades = range($min, $max);
        }

        // curricula
        $curricula = $this->curriculaAsArray($teacherProfile->curricula);

        return view('student.bookings.create', [
            'teacher'        => $teacher,
            'teacherProfile' => $teacherProfile,
            'grades'         => $grades,
            'curricula'      => $curricula,
            'cities'         => $cities,
        ]);
    }

    /**
     * POST /student/bookings
     */
    public function store(Request $request)
    {
        $student = Auth::user();
        if (!$student || $student->role !== 'student') abort(403);

        $validated = $request->validate([
            'teacher_id'        => ['required', 'exists:users,id'],
            'subject'           => ['required', 'string', 'max:255'],
            'grade'             => ['required', 'integer', 'min:1', 'max:12'],
            'curriculum'        => ['required', 'string', 'max:255'],
            'mode'              => ['required', 'in:online,onsite'],
            'duration_minutes'  => ['required', 'in:30,60'],
            'lessons_count'     => ['required', 'in:1,5,10'],
            'first_lesson_date' => ['required', 'date'],
            'first_lesson_time' => ['required', 'date_format:H:i'],
            'city_id'           => ['nullable', 'integer'],
            'location'          => ['nullable', 'string', 'max:255'],
            'notes'             => ['nullable', 'string'],
            'attachments.*'     => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ]);

        $teacher = User::where('role', 'teacher')->findOrFail($validated['teacher_id']);
        $profile = TeacherProfile::where('user_id', $teacher->id)->first();

        if (!$profile) {
            return back()->withErrors(['teacher_id' => 'ملف هذا المعلّم غير مكتمل حاليًا.'])->withInput();
        }

        // ✅ break_minutes
        $breakMinutes = $this->sanitizeBreakMinutes($profile->break_minutes ?? 0);
        $stepMinutes  = $this->computeStepMinutes($breakMinutes);

        // 1) تحقق الصف
        $grade = (int) $validated['grade'];

        $min = is_numeric($profile->min_grade) ? (int)$profile->min_grade : null;
        $max = is_numeric($profile->max_grade) ? (int)$profile->max_grade : null;

        if ($min !== null && $max !== null) {
            if ($min < 1)  $min = 1;
            if ($max > 12) $max = 12;
            if ($max < $min) { $tmp = $min; $min = $max; $max = $tmp; }

            if ($grade < $min || $grade > $max) {
                return back()->withErrors([
                    'grade' => "هذا المعلّم يدرّس من الصف {$min} إلى الصف {$max} فقط."
                ])->withInput();
            }
        }

        // 2) تحقق المناهج
        $allowedCurricula = $this->curriculaAsArray($profile->curricula);

        if (empty($allowedCurricula)) {
            return back()->withErrors([
                'curriculum' => 'هذا المعلّم لم يحدد المناهج التي يدرّسها بعد. لا يمكن إكمال الحجز.'
            ])->withInput();
        }

        if (!in_array($validated['curriculum'], $allowedCurricula, true)) {
            return back()->withErrors([
                'curriculum' => 'المنهج المختار غير متاح لهذا المعلّم.'
            ])->withInput();
        }

        // باقي الشروط
        if ($validated['mode'] === 'online' && empty($profile->teaches_online)) {
            return back()->withErrors(['mode' => 'هذا المعلّم لا يقدّم حصص أونلاين.'])->withInput();
        }

        if ($validated['mode'] === 'onsite') {
            if (empty($profile->teaches_onsite)) {
                return back()->withErrors(['mode' => 'هذا المعلّم لا يقدّم حصص حضورية.'])->withInput();
            }
            if (empty($validated['city_id'])) {
                return back()->withErrors(['city_id' => 'اختر المدينة للحصة الحضورية.'])->withInput();
            }
        }

        // ✅ مدة الحصة الحقيقية
        $duration = (int) $validated['duration_minutes'];
        if (!in_array($duration, [30, 60], true)) $duration = 60;

        // ✅ 1) نولّد السلات بخطوة stepMinutes (متوافقة مع break)
        $allowedTimes = $this->allowedTimesFromProfile(
            $profile,
            $validated['first_lesson_date'],
            $validated['mode'],
            $stepMinutes,
            $duration
        );

        // ✅ 2) فلترة أي وقت متداخل مع حجوزات قائمة (pending + confirmed) + break
        // ⚠️ مهم: لا نستخدم mode في التعارض (أي حجز يمنع نفس الوقت)
        $allowedTimes = $this->removeBookedTimes(
            $allowedTimes,
            $teacher->id,
            $profile,
            $validated['first_lesson_date'],
            $duration,
            $breakMinutes
        );

        if (!in_array($validated['first_lesson_time'], $allowedTimes, true)) {
            return back()->withErrors([
                'first_lesson_time' => 'الوقت المختار غير متاح (قد يكون متداخلًا مع حجز قائم). اختر من المواعيد المتاحة فقط.'
            ])->withInput();
        }

        // ✅ حساب السعر
        $basePrice = 0;
        if ($validated['mode'] === 'online') {
            $basePrice = ($duration === 30)
                ? ($profile->half_hour_rate_online ?? 0)
                : ($profile->hourly_rate_online ?? 0);
        } else {
            $basePrice = ($duration === 30)
                ? ($profile->half_hour_rate_onsite ?? 0)
                : ($profile->hourly_rate_onsite ?? 0);
        }

        if (!$basePrice || $basePrice <= 0) {
            return back()->withErrors([
                'duration_minutes' => 'سعر هذه المدة غير مُحدد لدى المعلّم. برجاء اختيار مدة أخرى أو التواصل مع الإدارة.'
            ])->withInput();
        }

        $lessonsCount = (int) $validated['lessons_count'];
        $discountPercent = $lessonsCount >= 10 ? 15 : ($lessonsCount >= 5 ? 10 : 0);

        $pricePerLesson = round($basePrice * (1 - $discountPercent / 100), 2);
        $totalPrice     = round($pricePerLesson * $lessonsCount, 2);

        $tz = $profile->time_zone ?: 'Asia/Dubai';
        $firstLessonAt  = Carbon::parse($validated['first_lesson_date'] . ' ' . $validated['first_lesson_time'], $tz);

        // ✅ هنا “قفل” الفترة: مدة الحصة + break
        $firstLessonEnd = $firstLessonAt->copy()->addMinutes($duration + max(0, $breakMinutes));

        // ✅ حماية نهائية ضد التزامن (Overlap-safe)
        if ($this->hasOverlappingBooking($teacher->id, $profile, $firstLessonAt, $firstLessonEnd, $breakMinutes)) {
            return back()->withErrors([
                'first_lesson_time' => 'هذا الموعد غير متاح لأنه متداخل مع حجز قائم. اختر وقتًا آخر.'
            ])->withInput();
        }

        $booking = new Booking();
        $booking->user_id            = $student->id;
        $booking->teacher_id         = $teacher->id;
        $booking->teacher_profile_id = $profile->id;

        $booking->subject          = $validated['subject'];
        $booking->grade            = $validated['grade'];
        $booking->curriculum       = $validated['curriculum'];
        $booking->mode             = $validated['mode'];
        $booking->duration_minutes = $duration;
        $booking->lessons_count    = $validated['lessons_count'];
        $booking->first_lesson_at  = $firstLessonAt;

        $booking->city_id   = $validated['city_id'] ?? null;
        $booking->location  = $validated['location'] ?? null;
        $booking->notes     = $validated['notes'] ?? null;

        $booking->price_per_lesson = $pricePerLesson;
        $booking->total_price      = $totalPrice;

        $booking->payment_status = 'pending';
        $booking->booking_type   = 'normal';
        $booking->status         = 'pending';

        $booking->uuid         = Str::uuid()->toString();
        $booking->total_amount = $totalPrice;
        $booking->currency     = 'AED';
        $booking->booking_date = $firstLessonAt;
        $booking->address      = $booking->location;

        // ✅ أعمدة طلب الإلغاء
        $booking->cancel_requested_at   = null;
        $booking->cancel_requested_by   = null;
        $booking->cancel_request_reason = null;
        $booking->cancel_request_status = null;
        $booking->cancel_handled_at     = null;
        $booking->cancel_handled_by     = null;
        $booking->cancel_handle_note    = null;

        $booking->save();

        // ✅ حفظ المرفقات
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$file) continue;

                $path = $file->store('booking_attachments', 'public');

                DB::table('booking_attachments')->insert([
                    'booking_id'       => $booking->id,
                    'uploaded_by_type' => 'student',
                    'uploaded_by_id'   => $student->id,
                    'original_name'    => $file->getClientOriginalName(),
                    'file_path'        => $path,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }

        // ✅ إشعار المعلّم بحجز جديد
        try {
            $teacher->notify(new NewBookingForTeacher($booking));
        } catch (\Throwable $e) {
        }

        return redirect()
            ->route('student.bookings.show', $booking->id)
            ->with('success', 'تم إنشاء الحجز بنجاح، في انتظار تأكيد المعلّم.');
    }

    /**
     * ✅ إلغاء مباشر (pending فقط)
     */
    public function cancel(Booking $booking)
    {
        $student = Auth::user();
        if (!$student || $student->role !== 'student') abort(403);

        if ((int)$booking->user_id !== (int)$student->id) abort(403);

        if ($booking->status === 'confirmed') {
            return back()->with('error', 'لا يمكن إلغاء الحجز المؤكد مباشرة. استخدم "طلب إلغاء".');
        }

        if (in_array($booking->status, ['cancelled', 'canceled'], true)) {
            return back()->with('success', 'هذا الحجز ملغي بالفعل.');
        }

        $booking->status = 'cancelled';
        $booking->status_updated_by = $student->id;
        $booking->status_updated_at = now();
        $booking->status_updated_source = 'student';
        $booking->save();

        return redirect()
            ->route('student.bookings.show', $booking->id)
            ->with('success', 'تم إلغاء الحجز بنجاح.');
    }

    /**
     * ✅ طلب إلغاء (للـ confirmed فقط) بدون تغيير status
     */
    public function requestCancel(Request $request, Booking $booking)
    {
        $student = Auth::user();
        if (!$student || $student->role !== 'student') abort(403);

        if ((int)$booking->user_id !== (int)$student->id) abort(403);

        if (in_array($booking->status, ['cancelled', 'canceled'], true)) {
            return back()->with('error', 'هذا الحجز ملغي بالفعل.');
        }

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'طلب الإلغاء متاح فقط للحجوزات المؤكدة.');
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (($booking->cancel_request_status ?? null) === 'pending') {
            return back()->with('success', 'تم إرسال طلب الإلغاء بالفعل وهو قيد المراجعة.');
        }

        if (($booking->cancel_request_status ?? null) === 'approved') {
            return back()->with('error', 'تمت الموافقة على طلب الإلغاء بالفعل.');
        }

        $booking->cancel_requested_at   = now();
        $booking->cancel_requested_by   = $student->id;
        $booking->cancel_request_reason = $data['reason'] ?? null;
        $booking->cancel_request_status = 'pending';

        $booking->cancel_handled_at   = null;
        $booking->cancel_handled_by   = null;
        $booking->cancel_handle_note  = null;

        $booking->save();

        return back()->with('success', 'تم إرسال طلب الإلغاء بنجاح، وسيقوم المعلّم بمراجعته.');
    }

    /**
     * ✅ يرجّع allowed times فقط (H:i) من جدول التوفر
     * - $stepMinutes = خطوة توليد أوقات البداية
     * - $requiredDuration = مدة الحصة المختارة (30/60)
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

        if ($stepMinutes < 1) $stepMinutes = 30;
        if ($stepMinutes > 60) $stepMinutes = 60;

        $allowed = [];
        $cursor = $start->copy();

        while ($cursor->copy()->addMinutes($requiredDuration)->lte($end)) {
            $allowed[] = $cursor->format('H:i');
            $cursor->addMinutes($stepMinutes);
        }

        return $allowed;
    }

    /**
     * ✅ يشيل الأوقات المتداخلة مع حجوزات قائمة (pending/confirmed) + break
     * - لا يعتمد على mode
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

        $busyIntervals = $this->busyIntervals($teacherId, $profile, $date, $breakMinutes);

        $tz = $profile->time_zone ?: 'Asia/Dubai';

        $available = [];
        foreach ($allowedTimes as $t) {
            try {
                $slotStart = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $t, $tz);
            } catch (\Throwable $e) {
                continue;
            }

            // ✅ الحجز الجديد: مدة + break
            $slotEnd = $slotStart->copy()->addMinutes($slotDuration + max(0, $breakMinutes));

            if ($this->overlapsAny($slotStart, $slotEnd, $busyIntervals)) {
                continue;
            }

            $available[] = $t;
        }

        return array_values($available);
    }

    /**
     * ✅ intervals للحجوزات القائمة في اليوم على توقيت المعلم + break
     * - لا نفلتر بالـ mode
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

                if ($start->format('Y-m-d') !== $date) {
                    continue;
                }

                $dur = (int) ($b->duration_minutes ?? 60);
                if (!in_array($dur, [30, 60], true)) $dur = 60;

                $end = $start->copy()->addMinutes($dur + max(0, $breakMinutes));

                $intervals[] = [$start, $end];
            } catch (\Throwable $e) {
            }
        }

        return $intervals;
    }

    private function overlapsAny(Carbon $start, Carbon $end, array $busyIntervals): bool
    {
        foreach ($busyIntervals as $it) {
            if (!is_array($it) || count($it) !== 2) continue;

            [$bStart, $bEnd] = $it;

            if (!$bStart instanceof Carbon || !$bEnd instanceof Carbon) continue;

            if ($start->lt($bEnd) && $end->gt($bStart)) {
                return true;
            }
        }
        return false;
    }

    private function hasOverlappingBooking(
        int $teacherId,
        TeacherProfile $profile,
        Carbon $newStart,
        Carbon $newEnd,
        int $breakMinutes
    ): bool {
        $tz = $profile->time_zone ?: 'Asia/Dubai';
        $date = $newStart->copy()->timezone($tz)->format('Y-m-d');

        $intervals = $this->busyIntervals($teacherId, $profile, $date, $breakMinutes);

        return $this->overlapsAny(
            $newStart->copy()->timezone($tz),
            $newEnd->copy()->timezone($tz),
            $intervals
        );
    }

    private function dayRangeWithBuffer(string $date, string $tz, int $bufferHours = 14): array
    {
        $start = Carbon::parse($date, $tz)->startOfDay()->subHours($bufferHours);
        $end   = Carbon::parse($date, $tz)->endOfDay()->addHours($bufferHours);
        return [$start, $end];
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
        if ($v > 180) $v = 180;
        return $v;
    }

    /**
     * ✅ نفس المنطق المستخدم في Controller الخاص بالمواعيد
     */
    private function computeStepMinutes(int $breakMinutes): int
    {
        if ($breakMinutes <= 0) return 30;
        if ($breakMinutes === 15) return 15;
        if ($breakMinutes === 20) return 10;
        return 5;
    }

    /**
     * ✅ curricula parser (robust)
     * يقبل Array أو JSON String أو CSV
     */
    private function curriculaAsArray($raw): array
    {
        if (is_array($raw)) {
            $list = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $list = $decoded;
            } else {
                $list = str_contains($raw, ',') ? explode(',', $raw) : [$raw];
            }
        } else {
            $list = [];
        }

        $list = array_values(array_filter(array_map('trim', $list), fn($v) => $v !== ''));
        return $list;
    }
}
