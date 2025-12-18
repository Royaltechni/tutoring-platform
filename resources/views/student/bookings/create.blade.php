@extends('layouts.student')

@section('page_title', 'حجز حصة جديدة')

@section('content')
<div class="container py-4">

    <h1 class="mb-4">حجز حصة جديدة</h1>

    {{-- لو مفيش معلّم محدد --}}
    @if(!$teacher)
        <div class="alert alert-warning">
            من فضلك اختر معلّم أولاً من صفحة
            <a href="{{ route('student.teachers.index') }}">البحث عن معلّم</a>.
        </div>
    @else
        @php
            $profile = $teacherProfile ?? null;

            $canOnline = (bool)($profile?->teaches_online ?? false);
            $canOnsite = (bool)($profile?->teaches_onsite ?? false);

            $onlineHour  = $profile->hourly_rate_online ?? null;
            $onlineHalf  = $profile->half_hour_rate_online ?? null;
            $onsiteHour  = $profile->hourly_rate_onsite ?? null;
            $onsiteHalf  = $profile->half_hour_rate_onsite ?? null;

            $currency = 'AED';

            $photo = ($profile && $profile->profile_photo_path)
                ? asset('storage/'.$profile->profile_photo_path)
                : asset('images/teacher-placeholder.png');

            // ✅ قيود الصفوف من البروفايل
            $minGrade = (int)($profile?->min_grade ?? 1);
            $maxGrade = (int)($profile?->max_grade ?? 12);

            if ($minGrade < 1) $minGrade = 1;
            if ($maxGrade > 12) $maxGrade = 12;
            if ($maxGrade < $minGrade) { $tmp = $minGrade; $minGrade = $maxGrade; $maxGrade = $tmp; }

            // ✅ curricula: القيم جاية من الكنترولر (array) — لو فاضية يبقى المعلم لم يحدد مناهج
            $curriculaArr = is_array($curricula ?? null) ? $curricula : [];

            // ✅ ده كان سبب المشكلة: $curriculaStrict لم يكن يُمرَّر من الكنترولر
            // هنا نحسبه مباشرة: True فقط إذا فعلاً فيه مناهج
            $curriculaStrict = !empty($curriculaArr);

            // ✅ grades: استخدم اللي جاي من الكنترولر، ولو رجع 1-12 بالغلط اعمل fallback من min/max
            $gradesList = [];
            if (is_array($grades ?? null) && count($grades) > 0) {
                $gradesList = $grades;
            }

            // fallback قوي
            if (empty($gradesList)) {
                $gradesList = range($minGrade, $maxGrade);
            } else {
                // لو الكنترولر رجّع 1-12 لكن المعلم محدد 3-7، نفلترها
                $gradesList = array_values(array_filter($gradesList, fn($g) => (int)$g >= $minGrade && (int)$g <= $maxGrade));
                if (empty($gradesList)) {
                    $gradesList = range($minGrade, $maxGrade);
                }
            }
        @endphp

        {{-- بطاقة المعلّم المختار --}}
        <div class="card mb-4">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 text-center">
                    <img src="{{ $photo }}"
                         alt="Teacher"
                         class="rounded-circle"
                         style="width: 80px; height: 80px; object-fit: cover;">
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1">{{ $teacher->name }}</h4>

                    @if(!empty($profile?->headline))
                        <p class="mb-1 text-muted">{{ $profile->headline }}</p>
                    @endif

                    <p class="mb-1">
                        <strong>المادة:</strong> {{ $profile?->main_subject ?? ($teacher->main_subject ?? '-') }}
                    </p>

                    <p class="mb-1">
                        <strong>طريقة التدريس:</strong>
                        @if($canOnline && $canOnsite)
                            أونلاين وحضوري
                        @elseif($canOnline)
                            أونلاين فقط
                        @elseif($canOnsite)
                            حضوري فقط
                        @else
                            لم تُحدّد بعد
                        @endif
                    </p>

                    {{-- ✅ قيود الصفوف والمناهج --}}
                    <p class="mb-1">
                        <strong>الصفوف التي يدرّسها:</strong>
                        <span class="text-muted">
                            من الصف {{ $minGrade }} إلى الصف {{ $maxGrade }}
                        </span>
                    </p>

                    <p class="mb-1">
                        <strong>المناهج التي يدرّسها:</strong>
                        <span class="text-muted">
                            @if($curriculaStrict)
                                {{ implode(' - ', $curriculaArr) }}
                            @else
                                غير محددة
                            @endif
                        </span>
                    </p>

                    <p class="mb-0">
                        <strong>أسعار المعلّم:</strong>
                        <span class="d-inline-block ms-2">
                            أونلاين (30د): {{ $onlineHalf ? $onlineHalf.' '.$currency : '—' }}
                            | (60د): {{ $onlineHour ? $onlineHour.' '.$currency : '—' }}
                        </span>
                        <span class="d-inline-block ms-2">
                            حضوري (30د): {{ $onsiteHalf ? $onsiteHalf.' '.$currency : '—' }}
                            | (60د): {{ $onsiteHour ? $onsiteHour.' '.$currency : '—' }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- نموذج الحجز --}}
    @if($teacher)
    <div class="card">
        <div class="card-body">

            {{-- تنبيه لو المناهج غير محددة --}}
            @if(!$curriculaStrict)
                <div class="alert alert-warning">
                    هذا المعلّم لم يحدد المناهج التي يدرّسها بعد. (لن يُسمح بإكمال الحجز إلا بعد تحديد المنهج من لوحة المعلّم)
                </div>
            @endif

            <form method="POST"
                  action="{{ route('student.bookings.store') }}"
                  enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">

                <div class="row g-3">

                    {{-- المادة --}}
                    <div class="col-md-6">
                        <label class="form-label">المادة</label>
                        <input type="text" name="subject" class="form-control"
                               value="{{ old('subject', $profile?->main_subject ?? '') }}" required>
                        @error('subject')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- الصف --}}
                    <div class="col-md-3">
                        <label class="form-label">الصف</label>
                        <select name="grade" class="form-select" required>
                            <option value="">اختر الصف</option>
                            @foreach($gradesList as $g)
                                <option value="{{ $g }}"
                                    {{ (string)old('grade') === (string)$g ? 'selected' : '' }}>
                                    الصف {{ $g }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            متاح فقط: من الصف {{ $minGrade }} إلى الصف {{ $maxGrade }}
                        </div>
                        @error('grade')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- المنهج --}}
                    <div class="col-md-3">
                        <label class="form-label">نوع المنهج</label>
                        <select name="curriculum" class="form-select" required {{ !$curriculaStrict ? 'disabled' : '' }}>
                            <option value="">{{ $curriculaStrict ? 'اختر المنهج' : 'غير متاح حالياً' }}</option>
                            @if($curriculaStrict)
                                @foreach($curriculaArr as $curr)
                                    <option value="{{ $curr }}"
                                        {{ old('curriculum') == $curr ? 'selected' : '' }}>
                                        {{ $curr }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('curriculum')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- طريقة الحصة --}}
                    <div class="col-md-4">
                        <label class="form-label d-block">طريقة الحصة</label>

                        @if($canOnline && $canOnsite)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input"
                                       type="radio"
                                       name="mode"
                                       id="mode_online"
                                       value="online"
                                       {{ old('mode', 'online') == 'online' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mode_online">أونلاين</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input"
                                       type="radio"
                                       name="mode"
                                       id="mode_onsite"
                                       value="onsite"
                                       {{ old('mode') == 'onsite' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mode_onsite">حضوري</label>
                            </div>

                        @elseif($canOnline)
                            <p class="mb-1">هذا المعلّم يقدّم حصص <strong>أونلاين فقط</strong>.</p>
                            <input type="hidden" name="mode" value="online">

                        @elseif($canOnsite)
                            <p class="mb-1">هذا المعلّم يقدّم حصص <strong>حضورية فقط</strong>.</p>
                            <input type="hidden" name="mode" value="onsite">

                        @else
                            <p class="text-danger mb-0">
                                لم يتم تحديد طريقة الحصص لهذا المعلّم بعد، برجاء التواصل مع الإدارة.
                            </p>
                        @endif

                        @error('mode')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- مدة الحصة --}}
                    <div class="col-md-4">
                        <label class="form-label d-block">مدة الحصة الواحدة</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input"
                                   type="radio"
                                   name="duration_minutes"
                                   id="duration_30"
                                   value="30"
                                   {{ old('duration_minutes', '60') == '30' ? 'checked' : '' }}>
                            <label class="form-check-label" for="duration_30">30 دقيقة</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input"
                                   type="radio"
                                   name="duration_minutes"
                                   id="duration_60"
                                   value="60"
                                   {{ old('duration_minutes', '60') == '60' ? 'checked' : '' }}>
                            <label class="form-check-label" for="duration_60">60 دقيقة</label>
                        </div>
                        @error('duration_minutes')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- عدد الحصص --}}
                    <div class="col-md-4">
                        <label class="form-label">عدد الحصص (الباقة)</label>
                        <select name="lessons_count" id="lessons_count" class="form-select">
                            @foreach([1,5,10] as $count)
                                <option value="{{ $count }}"
                                    {{ old('lessons_count', 1) == $count ? 'selected' : '' }}>
                                    {{ $count }} حصة
                                </option>
                            @endforeach
                        </select>
                        @error('lessons_count')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            5 حصص: خصم 10% — 10 حصص: خصم 15%
                        </div>
                    </div>

                    {{-- المدينة + العنوان (للحصة الحضورية فقط) --}}
                    @if($canOnsite)
                        <div class="col-12" id="city-group"
                            style="display: {{ old('mode', 'online') == 'onsite' ? 'block' : 'none' }};">
                            <label class="form-label">المدينة (للحصة الحضورية)</label>
                            <select name="city_id" class="form-select">
                                <option value="">اختر المدينة</option>
                                @foreach($cities as $c)
                                    <option value="{{ $c->id }}" {{ (string)old('city_id') === (string)$c->id ? 'selected' : '' }}>
                                        {{ $c->name_ar ?: $c->name_en }}
                                    </option>
                                @endforeach
                            </select>
                            @error('city_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12" id="location-group"
                            style="display: {{ old('mode', 'online') == 'onsite' ? 'block' : 'none' }};">
                            <label class="form-label">تفاصيل العنوان (اختياري)</label>
                            <input type="text" name="location" class="form-control"
                                value="{{ old('location') }}"
                                placeholder="الحي / أقرب معلم واضح (اختياري)">
                            @error('location')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    {{-- موعد أول حصة --}}
                    <div class="col-md-6">
                        <label class="form-label">تاريخ أول حصة</label>
                        <input type="date" name="first_lesson_date" class="form-control"
                               value="{{ old('first_lesson_date') }}" required>
                        @error('first_lesson_date')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">وقت أول حصة</label>
                        <select name="first_lesson_time" id="first_lesson_time" class="form-select" required>
                            <option value="">اختر التاريخ أولاً لعرض المواعيد المتاحة</option>
                        </select>

                        <div class="form-text" id="slots_hint">سيتم عرض المواعيد المتاحة حسب جدول المعلّم.</div>

                        @error('first_lesson_time')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ✅ ملخص السعر --}}
                    <div class="col-12">
                        <div class="alert alert-info mb-0" id="priceBox">
                            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                                <div>
                                    <strong>السعر لكل حصة:</strong>
                                    <span id="pricePerLesson">—</span> {{ $currency }}
                                    <span class="text-muted ms-2" id="discountInfo"></span>
                                </div>
                                <div>
                                    <strong>الإجمالي:</strong>
                                    <span id="totalPrice">—</span> {{ $currency }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ملاحظات --}}
                    <div class="col-12">
                        <label class="form-label">ملاحظات إضافية للمعلم</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="مثال: الطالب يحتاج تركيز على مهارات الامتحان">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- المرفقات --}}
                    <div class="col-12">
                        <label class="form-label">ملفات مرفقة (واجب، أوراق، صور...)</label>
                        <input type="file" name="attachments[]" class="form-control" multiple>
                        <div class="form-text">
                            يمكنك رفع PDF أو صور (JPG/PNG) أو DOC/DOCX — بحد أقصى 5MB لكل ملف.
                        </div>
                        @error('attachments.*')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary" {{ !$curriculaStrict ? 'disabled' : '' }}>
                            تأكيد الحجز
                        </button>
                        <a href="{{ route('student.teachers.index') }}" class="btn btn-outline-secondary">
                            إلغاء والعودة لقائمة المعلّمين
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>
    @endif
</div>

{{-- Scripts كما هي عندك بدون تغيير --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const modeOnline   = document.getElementById('mode_online');
    const modeOnsite   = document.getElementById('mode_onsite');

    const cityGroup    = document.getElementById('city-group');
    const locationWrap = document.getElementById('location-group');

    const pricePerLessonEl = document.getElementById('pricePerLesson');
    const totalPriceEl     = document.getElementById('totalPrice');
    const discountInfoEl   = document.getElementById('discountInfo');
    const lessonsCountEl   = document.getElementById('lessons_count');

    const duration30 = document.getElementById('duration_30');
    const duration60 = document.getElementById('duration_60');

    const PRICES = {
        online: { 30: {{ (float)($onlineHalf ?? 0) }}, 60: {{ (float)($onlineHour ?? 0) }} },
        onsite: { 30: {{ (float)($onsiteHalf ?? 0) }}, 60: {{ (float)($onsiteHour ?? 0) }} },
    };

    function getMode() {
        const el = document.querySelector('input[name="mode"]:checked') || document.querySelector('input[name="mode"]');
        return el ? el.value : 'online';
    }

    function getDuration() {
        if (duration30 && duration30.checked) return 30;
        return 60;
    }

    function getLessonsCount() {
        return parseInt((lessonsCountEl?.value || '1'), 10);
    }

    function discountPercent(count) {
        if (count >= 10) return 15;
        if (count >= 5) return 10;
        return 0;
    }

    function toggleOnsiteFields() {
        const mode = getMode();
        const show = (mode === 'onsite');

        if (cityGroup) cityGroup.style.display = show ? 'block' : 'none';
        if (locationWrap) locationWrap.style.display = show ? 'block' : 'none';
    }

    function updatePriceUI() {
        const mode = getMode();
        const duration = getDuration();
        const count = getLessonsCount();

        const base = (PRICES[mode] && PRICES[mode][duration]) ? Number(PRICES[mode][duration]) : 0;

        if (!base || base <= 0) {
            pricePerLessonEl.textContent = '—';
            totalPriceEl.textContent = '—';
            discountInfoEl.textContent = '';
            return;
        }

        const disc = discountPercent(count);
        const perLesson = +(base * (1 - disc / 100)).toFixed(2);
        const total = +(perLesson * count).toFixed(2);

        pricePerLessonEl.textContent = perLesson.toFixed(2);
        totalPriceEl.textContent = total.toFixed(2);

        discountInfoEl.textContent = disc ? `(خصم ${disc}%)` : '';
    }

    if (modeOnline) modeOnline.addEventListener('change', () => { toggleOnsiteFields(); updatePriceUI(); });
    if (modeOnsite) modeOnsite.addEventListener('change', () => { toggleOnsiteFields(); updatePriceUI(); });

    if (duration30) duration30.addEventListener('change', updatePriceUI);
    if (duration60) duration60.addEventListener('change', updatePriceUI);
    if (lessonsCountEl) lessonsCountEl.addEventListener('change', updatePriceUI);

    toggleOnsiteFields();
    updatePriceUI();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const teacherId = @json($teacher?->id);
    const dateInput = document.querySelector('input[name="first_lesson_date"]');

    const modeOnline = document.getElementById('mode_online');
    const modeOnsite = document.getElementById('mode_onsite');

    const dur30 = document.getElementById('duration_30');
    const dur60 = document.getElementById('duration_60');

    const timeSelect = document.getElementById('first_lesson_time');
    const hint = document.getElementById('slots_hint');

    const locationWrap = document.getElementById('location-group');
    const cityWrap = document.getElementById('city-group');

    function currentMode(){
        const el = document.querySelector('input[name="mode"]:checked') || document.querySelector('input[name="mode"]');
        return el ? el.value : 'online';
    }

    function currentDuration(){
        if (dur30 && dur30.checked) return 30;
        return 60;
    }

    function toggleOnsiteFields(){
        const onsite = (currentMode() === 'onsite');
        if (locationWrap) locationWrap.style.display = onsite ? 'block' : 'none';
        if (cityWrap) cityWrap.style.display = onsite ? 'block' : 'none';
    }

    async function loadSlots(){
        if (!teacherId || !dateInput || !timeSelect) return;

        const dateVal = dateInput.value;
        if (!dateVal){
            timeSelect.innerHTML = `<option value="">اختر التاريخ أولاً</option>`;
            return;
        }

        const mode = currentMode();
        const duration = currentDuration();

        timeSelect.innerHTML = `<option value="">جارٍ تحميل المواعيد...</option>`;
        if (hint) hint.textContent = 'جارٍ تحميل المواعيد المتاحة...';

        try{
            const url = `{{ url('/student/teachers') }}/${teacherId}/available-slots?date=${encodeURIComponent(dateVal)}&mode=${mode}&duration=${duration}`;
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const data = await res.json();

            const slots = (data && data.slots) ? data.slots : [];
            if (!slots.length){
                timeSelect.innerHTML = `<option value="">لا توجد مواعيد متاحة في هذا اليوم</option>`;
                if (hint) hint.textContent = 'اختر يومًا آخر.';
                return;
            }

            slots.sort((a,b) => (a.value > b.value ? 1 : -1));

            const oldVal = @json(old('first_lesson_time'));
            let html = `<option value="">اختر الوقت</option>`;
            for (const s of slots){
                const selected = (oldVal && oldVal === s.value) ? 'selected' : '';
                html += `<option value="${s.value}" ${selected}>${s.label}</option>`;
            }
            timeSelect.innerHTML = html;
            if (hint) hint.textContent = 'اختر وقتًا من المواعيد المتاحة فقط.';
        }catch(e){
            timeSelect.innerHTML = `<option value="">حدث خطأ أثناء تحميل المواعيد</option>`;
            if (hint) hint.textContent = 'حاول مرة أخرى.';
        }
    }

    if (dateInput) dateInput.addEventListener('change', loadSlots);
    if (modeOnline) modeOnline.addEventListener('change', () => { toggleOnsiteFields(); loadSlots(); });
    if (modeOnsite) modeOnsite.addEventListener('change', () => { toggleOnsiteFields(); loadSlots(); });
    if (dur30) dur30.addEventListener('change', loadSlots);
    if (dur60) dur60.addEventListener('change', loadSlots);

    toggleOnsiteFields();
    loadSlots();
});
</script>

@endsection
