@php
    // ==============================
    // Teacher Quick Stats (Shared)
    // ==============================

    /** @var \App\Models\User $teacher */
    $profile = $profile ?? ($teacher->teacherProfile ?? null);

    $experienceYears = $experienceYears ?? ($profile?->experience_years ?? null);

    $rating = isset($rating) ? (float)$rating : (float)($teacher->average_rating ?? 0);
    $ratingsCount = isset($ratingsCount) ? (int)$ratingsCount : (int)($teacher->ratings_count ?? 0);

    // ✅ لو ما اتبعتش canOnline/canOnsite نحسبهم تلقائيًا
    if (!isset($canOnline)) {
        $onlineHour = $profile->hourly_rate_online ?? $teacher->hourly_rate_online ?? null;
        $onlineHalf = $profile->half_hour_rate_online ?? $teacher->half_hour_rate_online ?? null;
        $onlineFlag = $profile->teaches_online ?? $teacher->teaches_online ?? false;

        $canOnline = (bool)$onlineFlag || !is_null($onlineHour) || !is_null($onlineHalf);
    }

    if (!isset($canOnsite)) {
        $onsiteHour = $profile->hourly_rate_onsite ?? $teacher->hourly_rate_onsite ?? null;
        $onsiteHalf = $profile->half_hour_rate_onsite ?? $teacher->half_hour_rate_onsite ?? null;
        $onsiteFlag = $profile->teaches_onsite ?? $teacher->teaches_onsite ?? false;

        $canOnsite = (bool)$onsiteFlag || !is_null($onsiteHour) || !is_null($onsiteHalf);
    }

    $gradeMin = $gradeMin ?? ($teacher->grade_min ?? null);
    $gradeMax = $gradeMax ?? ($teacher->grade_max ?? null);
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="fw-bold">الخبرة</div>
                <div class="text-muted small">عدد سنوات التدريس</div>
                <div class="fs-5">{{ $experienceYears ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="fw-bold">التقييم</div>
                <div class="fs-5">{{ number_format($rating, 1) }}</div>
                <div class="text-muted small">{{ $ratingsCount }} تقييم</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="fw-bold">طرق التدريس</div>
                <div class="fs-6 mt-1">
                    @if($canOnline && $canOnsite)
                        أونلاين + حضوري
                    @elseif($canOnline)
                        أونلاين
                    @elseif($canOnsite)
                        حضوري
                    @else
                        —
                    @endif
                </div>
                <div class="text-muted small">نوع الحصص</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="fw-bold">الصفوف</div>
                <div class="fs-6">
                    @if($gradeMin || $gradeMax)
                        {{ $gradeMin ?? '?' }} - {{ $gradeMax ?? '?' }}
                    @else
                        —
                    @endif
                </div>
                <div class="text-muted small">المراحل</div>
            </div>
        </div>
    </div>
</div>
