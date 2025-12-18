@php
    // ==============================
    // Teacher Profile Card (Shared)
    // ==============================

    /** @var \App\Models\User $teacher */
    $profile = $profile ?? ($teacher->teacherProfile ?? null);

    $photo = $photo ?? (
        ($profile && !empty($profile->profile_photo_path))
            ? asset('storage/'.$profile->profile_photo_path)
            : asset('images/teacher-placeholder.png')
    );

    $mainSubject = $mainSubject ?? ($profile->main_subject ?? ($teacher->main_subject ?? '-'));
    $countryName = $countryName ?? ($profile->country ?? ($teacher->country ?? '-'));

    $cityNames = $cityNames ?? [];
    $cityTextFallback = $cityTextFallback ?? ($profile->city ?? ($teacher->city ?? null));

    $rating = isset($rating) ? (float)$rating : (float)($teacher->average_rating ?? 0);
    $fullStars = isset($fullStars) ? (int)$fullStars : (int)floor($rating);
    $ratingsCount = isset($ratingsCount) ? (int)$ratingsCount : (int)($teacher->ratings_count ?? 0);

    // ✅ قوائم (نطبّعها array)
    $curriculaList = $curriculaList ?? [];
    if (is_string($curriculaList)) $curriculaList = explode(',', $curriculaList);
    $curriculaList = array_values(array_filter(array_map('trim', (array)$curriculaList)));

    $subjectsList = $subjectsList ?? [];
    if (is_string($subjectsList)) $subjectsList = explode(',', $subjectsList);
    $subjectsList = array_values(array_filter(array_map('trim', (array)$subjectsList)));

    $languagesList = $languagesList ?? [];
    if (is_string($languagesList)) $languagesList = explode(',', $languagesList);
    $languagesList = array_values(array_filter(array_map('trim', (array)$languagesList)));

    $showVerifiedBadges = $showVerifiedBadges ?? true;

    // ✅ هل نعرض أزرار فتح المستندات؟ (للأدمن غالبًا)
    $showAdminDocLinks = $showAdminDocLinks ?? false;

    // ✅ روابط الملفات
    $idDocUrl = null;
    if (!empty($profile?->id_document_path)) {
        $p = ltrim($profile->id_document_path, '/');
        if (str_starts_with($p, 'public/')) $p = substr($p, 7);
        $idDocUrl = str_starts_with($p, 'storage/') ? asset($p) : asset('storage/'.$p);
    }

    $permitUrl = null;
    if (!empty($profile?->teaching_permit_path)) {
        $p = ltrim($profile->teaching_permit_path, '/');
        if (str_starts_with($p, 'public/')) $p = substr($p, 7);
        $permitUrl = str_starts_with($p, 'storage/') ? asset($p) : asset('storage/'.$p);
    }

    // ✅ حالة الحساب (من profile أو من user)
    $status =
        $profile->account_status
        ?? $teacher->teacher_status
        ?? 'pending';

    $statusBadge = 'secondary';
    $statusText  = 'غير محدّد';

    if ($status === 'approved') {
        $statusBadge = 'success';
        $statusText  = 'مقبول';
    } elseif ($status === 'pending') {
        $statusBadge = 'warning';
        $statusText  = 'قيد المراجعة';
    } elseif ($status === 'rejected') {
        $statusBadge = 'danger';
        $statusText  = 'مرفوض';
    }
@endphp

@once
    @push('styles')
        <style>
            /* Teacher Profile Card (Shared) - loaded once */
            .teacher-card-img{
                width: 140px;
                height: 140px;
                object-fit: cover;
                object-position: top;
            }
        </style>
    @endpush
@endonce

<div class="card text-center mb-3" dir="rtl">
    <div class="card-body">

        <img src="{{ $photo }}"
             alt="Teacher"
             class="rounded-circle mb-3 teacher-card-img">

        <h3 class="mb-1">{{ $teacher->name }}</h3>

        @if(!empty($profile?->headline))
            <p class="text-muted mb-2">{{ $profile->headline }}</p>
        @endif

        <p class="mb-1"><strong>المادة الرئيسية:</strong> {{ $mainSubject }}</p>

        <p class="mb-1">
            <strong>الموقع:</strong>
            {{ $countryName }}
            @if(!empty($cityNames) && count($cityNames))
                - {{ implode('، ', $cityNames) }}
            @elseif(!empty($cityTextFallback))
                - {{ $cityTextFallback }}
            @endif
        </p>

        <p class="mb-1">
            <strong>التقييم:</strong>
            @for($i=1; $i<=5; $i++)
                @if($i <= $fullStars)
                    <span class="text-warning">★</span>
                @else
                    <span class="text-muted">☆</span>
                @endif
            @endfor
            <span class="ms-1">
                ({{ number_format($rating, 1) }})
                <small class="text-muted">{{ $ratingsCount }} تقييم</small>
            </span>
        </p>

        {{-- المناهج --}}
        <div class="mt-3 text-end">
            <strong>المناهج:</strong>
            <div class="mt-2 d-flex flex-wrap gap-2 justify-content-center">
                @if(count($curriculaList))
                    @foreach($curriculaList as $c)
                        <span class="badge bg-secondary">{{ $c }}</span>
                    @endforeach
                @else
                    <span class="text-muted small">لم تُحدّد</span>
                @endif
            </div>
        </div>

        {{-- المواد --}}
        <div class="mt-3 text-end">
            <strong>المواد:</strong>
            <div class="mt-2 d-flex flex-wrap gap-2 justify-content-center">
                @if(count($subjectsList))
                    @foreach($subjectsList as $s)
                        <span class="badge bg-dark">{{ $s }}</span>
                    @endforeach
                @else
                    <span class="text-muted small">لم تُحدّد</span>
                @endif
            </div>
        </div>

        {{-- لغات الشرح --}}
        <div class="mt-3 text-end">
            <strong>لغات الشرح:</strong>
            <div class="mt-2 d-flex flex-wrap gap-2 justify-content-center">
                @if(count($languagesList))
                    @foreach($languagesList as $l)
                        <span class="badge bg-info text-dark">{{ $l }}</span>
                    @endforeach
                @else
                    <span class="text-muted small">لم تُحدّد</span>
                @endif
            </div>
        </div>

        {{-- شارات التحقق + حالة الحساب --}}
        @if($showVerifiedBadges)
            <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center">

                {{-- حالة الحساب --}}
                <span class="badge bg-{{ $statusBadge }}">الحالة: {{ $statusText }}</span>

                {{-- تحقق المستندات --}}
                @if(!empty($idDocUrl))
                    <span class="badge bg-success">هوية/جواز مرفوع</span>
                @else
                    <span class="badge bg-secondary">هوية غير مرفوعة</span>
                @endif

                @if(!empty($permitUrl))
                    <span class="badge bg-primary">تصريح تدريس مرفوع</span>
                @else
                    <span class="badge bg-secondary">تصريح غير مرفوع</span>
                @endif

            </div>

            {{-- روابط فتح الملفات (للأدمن فقط لو فعلتها) --}}
            @if($showAdminDocLinks && (!empty($idDocUrl) || !empty($permitUrl)))
                <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center">
                    @if(!empty($idDocUrl))
                        <a href="{{ $idDocUrl }}" target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-success">
                            فتح الهوية
                        </a>
                    @endif
                    @if(!empty($permitUrl))
                        <a href="{{ $permitUrl }}" target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-primary">
                            فتح التصريح
                        </a>
                    @endif
                </div>
            @endif
        @endif

    </div>
</div>
