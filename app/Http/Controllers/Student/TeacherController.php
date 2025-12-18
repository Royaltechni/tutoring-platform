<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TeacherProfile;
use Carbon\Carbon;

class TeacherController extends Controller
{
    /**
     * ✅ أعمدة نحتاجها فعلاً من teacher_profiles في صفحات الطالب
     * (خصوصاً teaching_style)
     */
    private function profileSelectColumns(): array
    {
        return [
            'id',
            'user_id',

            // approval/status
            'account_status',

            // profile media
            'profile_photo_path',
            'intro_video_url',
            'intro_video_path',
            'intro_video',

            // location
            'country',
            'city',
            'country_id',
            'onsite_city_ids',

            // teaching options
            'teaches_online',
            'teaches_onsite',

            // rates
            'hourly_rate_online',
            'half_hour_rate_online',
            'hourly_rate_onsite',
            'half_hour_rate_onsite',

            // subjects/curricula/languages
            'main_subject',
            'bio',
            'subjects',
            'curricula',
            'languages',

            // ✅ fields المشكلة هنا غالباً
            'teaching_style',
            'cancel_policy',
            'availability',

            // ratings/experience (لو بتستخدمها في cards/stats)
            'average_rating',
            'experience_years',

            // availability flags + timezone (لو مستخدمة عندك)
            'available_today',
            'available_tomorrow',
            'time_zone',
        ];
    }

    /**
     * عرض قائمة المعلّمين مع الفلاتر.
     * ✅ يظهر فقط المعلّمين المُفعّلين (approved) للطلاب
     */
    public function index(Request $request)
    {
        // ✅ نبدأ من teachers المُفعّلين فقط
        $query = User::where('role', 'teacher')
            ->where('teacher_status', 'approved') // ✅ المصدر الأساسي
            ->with([
                'teacherProfile' => function ($q) {
                    // ✅ أهم تعديل: نجبر جلب teaching_style وباقي الأعمدة
                    $q->select($this->profileSelectColumns());
                },
                'teacherProfile.onsiteCities',   // ✅ تحميل المدن الحضور المختارة
                'teacherProfile.onsiteCountry',  // (اختياري) تحميل الدولة الحضور
            ]);

        /**
         * =========================
         * Filters
         * =========================
         */

        // فلتر الدولة (من teacher_profiles)
        if ($request->filled('country')) {
            $country = $request->country;
            $query->whereHas('teacherProfile', function ($q) use ($country) {
                $q->where('country', $country);
            });
        }

        // فلتر المدينة (legacy من teacher_profiles.city) - كما هو عندك
        if ($request->filled('city')) {
            $city = $request->city;
            $query->whereHas('teacherProfile', function ($q) use ($city) {
                $q->where('city', $city);
            });
        }

        // فلتر المادة الرئيسية (من teacher_profiles)
        if ($request->filled('subject')) {
            $subject = $request->subject;
            $query->whereHas('teacherProfile', function ($q) use ($subject) {
                $q->where('main_subject', $subject);
            });
        }

        // فلتر الصف (Grade) ✅ من users (عندك grade_min/max في users)
        if ($request->filled('grade')) {
            $grade = (int) $request->grade;

            $query->where(function ($q) use ($grade) {
                $q->whereNull('grade_min')
                  ->orWhere('grade_min', '<=', $grade);
            })->where(function ($q) use ($grade) {
                $q->whereNull('grade_max')
                  ->orWhere('grade_max', '>=', $grade);
            });
        }

        // فلتر نوع المنهج ✅ من users.curricula (string)
        if ($request->filled('curriculum')) {
            $curriculum = $request->curriculum;
            $query->where('curricula', 'LIKE', '%' . $curriculum . '%');
        }

        // أونلاين / حضوري (من teacher_profiles)
        if ($request->filled('mode')) {
            if ($request->mode === 'online') {
                $query->whereHas('teacherProfile', function ($q) {
                    $q->where('teaches_online', 1);
                });
            } elseif ($request->mode === 'onsite') {
                $query->whereHas('teacherProfile', function ($q) {
                    $q->where('teaches_onsite', 1);
                });
            }
        }

        // أقل تقييم (لو موجود في teacher_profiles)
        if ($request->filled('rating_min')) {
            $ratingMin = (float) $request->rating_min;
            $query->whereHas('teacherProfile', function ($q) use ($ratingMin) {
                $q->where('average_rating', '>=', $ratingMin);
            });
        }

        // فلتر السعر (من - إلى) على أساس أقل سعر ساعة (أونلاين أو حضوري) (teacher_profiles)
        if ($request->filled('price_min')) {
            $priceMin = (float) $request->price_min;
            $query->whereHas('teacherProfile', function ($q) use ($priceMin) {
                $q->whereRaw('COALESCE(hourly_rate_online, hourly_rate_onsite) >= ?', [$priceMin]);
            });
        }

        if ($request->filled('price_max')) {
            $priceMax = (float) $request->price_max;
            $query->whereHas('teacherProfile', function ($q) use ($priceMax) {
                $q->whereRaw('COALESCE(hourly_rate_online, hourly_rate_onsite) <= ?', [$priceMax]);
            });
        }

        // متاح اليوم / غدًا (لو موجود في teacher_profiles)
        if ($request->filled('availability')) {
            if ($request->availability === 'today') {
                $query->whereHas('teacherProfile', function ($q) {
                    $q->where('available_today', 1);
                });
            } elseif ($request->availability === 'tomorrow') {
                $query->whereHas('teacherProfile', function ($q) {
                    $q->where('available_tomorrow', 1);
                });
            }
        }

        /**
         * =========================
         * Pagination + Sorting
         * =========================
         */
        $teachers = $query
            ->orderByDesc(
                TeacherProfile::select('average_rating')
                    ->whereColumn('teacher_profiles.user_id', 'users.id')
                    ->limit(1)
            )
            ->orderByDesc(
                TeacherProfile::select('experience_years')
                    ->whereColumn('teacher_profiles.user_id', 'users.id')
                    ->limit(1)
            )
            ->paginate(12)
            ->withQueryString();

        /**
         * =========================
         * تجهيز بيانات الفلاتر (Distinct)
         * ✅ لازم تبقى من teacher_profiles لكن للـ approved فقط
         * =========================
         */
        $profilesBase = TeacherProfile::query()
            ->whereHas('user', function ($q) {
                $q->where('role', 'teacher')
                  ->where('teacher_status', 'approved');
            });

        $countries = (clone $profilesBase)
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        $cities = (clone $profilesBase)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $subjects = (clone $profilesBase)
            ->whereNotNull('main_subject')
            ->where('main_subject', '!=', '')
            ->distinct()
            ->orderBy('main_subject')
            ->pluck('main_subject');

        $grades = range(1, 12);

        // curricula من users (approved فقط)
        $curriculaRaw = User::where('role', 'teacher')
            ->where('teacher_status', 'approved')
            ->whereNotNull('curricula')
            ->pluck('curricula')
            ->toArray();

        $curricula = [];
        foreach ($curriculaRaw as $item) {
            $parts = array_map('trim', explode(',', $item));
            foreach ($parts as $part) {
                if ($part !== '' && !in_array($part, $curricula)) {
                    $curricula[] = $part;
                }
            }
        }
        sort($curricula);

        return view('student.teachers.index', [
            'teachers'   => $teachers,
            'countries'  => $countries,
            'cities'     => $cities,
            'subjects'   => $subjects,
            'grades'     => $grades,
            'curricula'  => $curricula,
            'filters'    => $request->all(),
        ]);
    }

    /**
     * عرض صفحة تفاصيل معلّم واحد.
     * ✅ الطالب يشوف فقط approved
     * ✅ الأدمن/المعلم ممكن يشوف أي حالة (لو دخل من نفس الراوت)
     */
    public function show($teacherId)
    {
        $currentUser = auth()->user();

        $query = User::where('role', 'teacher')
            ->with([
                'teacherProfile' => function ($q) {
                    // ✅ أهم تعديل: نجبر جلب teaching_style وباقي الأعمدة
                    $q->select($this->profileSelectColumns());
                },
                'teacherProfile.onsiteCities',
                'teacherProfile.onsiteCountry',
            ]);

        // ✅ الطالب أو الضيف: ممنوع يشوف غير approved
        if (!$currentUser || $currentUser->role === 'student') {
            $query->where('teacher_status', 'approved');
        }

        $teacher = $query->findOrFail($teacherId);

        $curricula = [];
        if (!empty($teacher->curricula)) {
            $curricula = array_map('trim', explode(',', $teacher->curricula));
        }

        return view('student.teachers.show', [
            'teacher'   => $teacher,
            'curricula' => $curricula,
        ]);
    }

    /**
     * ✅ API: يرجّع المواعيد المتاحة لليوم المختار (لـ dropdown في صفحة الحجز)
     * GET /student/teachers/{teacher}/available-slots?date=YYYY-MM-DD&mode=online|onsite&duration=30|60
     */
    public function availableSlots(Request $request, $teacherId)
    {
        $request->validate([
            'date'     => ['required', 'date'],
            'mode'     => ['required', 'in:online,onsite'],
            'duration' => ['required', 'in:30,60'],
        ]);

        $teacher = User::where('role', 'teacher')
            ->with([
                'teacherProfile' => function ($q) {
                    // ✅ خليها نفس الأعمدة عشان ما يحصلش اختلاف
                    $q->select($this->profileSelectColumns());
                },
            ])
            ->findOrFail($teacherId);

        // للطالب/الضيف: يشوف فقط approved
        $currentUser = auth()->user();
        if (!$currentUser || $currentUser->role === 'student') {
            abort_unless($teacher->teacher_status === 'approved', 404);
        }

        $profile = $teacher->teacherProfile;
        if (!$profile) {
            return response()->json(['slots' => []]);
        }

        $slots = $this->buildSlotsFromAvailability(
            $profile,
            $request->date,
            $request->mode,
            (int) $request->duration
        );

        return response()->json(['slots' => $slots]);
    }

    /**
     * ================
     * Helpers
     * ================
     */
    private function availabilityArray(TeacherProfile $profile): array
    {
        $av = $profile->availability ?? null;

        if (is_array($av)) return $av;

        if (is_string($av) && $av !== '') {
            $decoded = json_decode($av, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function normalizeDayKey(Carbon $date): array
    {
        $full = strtolower($date->format('l'));
        $short = strtolower($date->format('D'));

        $arabicMap = [
            'saturday'  => 'السبت',
            'sunday'    => 'الأحد',
            'monday'    => 'الاثنين',
            'tuesday'   => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday'  => 'الخميس',
            'friday'    => 'الجمعة',
        ];

        return [
            $full,
            $short,
            $arabicMap[$full] ?? null,
        ];
    }

    private function pickDayConfig(array $availability, Carbon $date): ?array
    {
        [$full, $short, $ar] = $this->normalizeDayKey($date);

        $candidates = array_filter([
            $full, $short,
            strtoupper($short),
            ucfirst($full),
            $ar,
        ]);

        foreach ($candidates as $k) {
            if (isset($availability[$k]) && is_array($availability[$k])) {
                return $availability[$k];
            }
        }

        return null;
    }

    private function parseTimeToCarbon(string $dateYmd, string $time, string $tz): ?Carbon
    {
        try {
            return Carbon::parse($dateYmd . ' ' . $time, $tz);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildSlotsFromAvailability(TeacherProfile $profile, string $dateYmd, string $mode, int $duration): array
    {
        if ($mode === 'online' && !(bool)($profile->teaches_online ?? false)) return [];
        if ($mode === 'onsite' && !(bool)($profile->teaches_onsite ?? false)) return [];

        $tz = $profile->time_zone ?: 'Asia/Dubai';
        $date = Carbon::parse($dateYmd, $tz);

        $availability = $this->availabilityArray($profile);
        if (empty($availability)) return [];

        $cfg = $this->pickDayConfig($availability, $date);
        if (!$cfg) return [];

        $enabled = (bool)($cfg['enabled'] ?? $cfg['is_available'] ?? $cfg['available'] ?? false);
        if (!$enabled) return [];

        $from = $cfg['from'] ?? $cfg['start'] ?? $cfg['from_time'] ?? null;
        $to   = $cfg['to']   ?? $cfg['end']   ?? $cfg['to_time']   ?? null;
        if (!$from || !$to) return [];

        $start = $this->parseTimeToCarbon($dateYmd, (string)$from, $tz);
        $end   = $this->parseTimeToCarbon($dateYmd, (string)$to, $tz);
        if (!$start || !$end) return [];

        if ($end->lte($start)) return [];

        $lastStart = $end->copy()->subMinutes($duration);
        if ($lastStart->lt($start)) return [];

        $now = Carbon::now($tz);

        $slots = [];
        $cursor = $start->copy();

        while ($cursor->lte($lastStart)) {
            if ($date->isSameDay($now) && $cursor->lte($now)) {
                $cursor->addMinutes(30);
                continue;
            }

            $slots[] = [
                'value' => $cursor->format('H:i'),
                'label' => $cursor->format('h:i A'),
            ];

            $cursor->addMinutes(30);
        }

        return $slots;
    }
}
