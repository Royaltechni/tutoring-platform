@extends('layouts.student')

@section('page_title', 'ابحث عن معلّم')

@section('content')
<style>
    .teacher-card-img {
        width: 100%;
        height: 260px;          /* ارتفاع ثابت للكادر */
        object-fit: contain;    /* يعرِض الصورة كاملة بدون قص */
        background-color: #fff; /* خلفية بيضاء حول الصورة */
        padding: 8px;           /* مسافة بسيطة حول الصورة */
        border-top-left-radius: 18px;
        border-top-right-radius: 18px;
        display: block;
    }
</style>

<div class="container py-4">
    <h1 class="mb-4">ابحث عن معلّم</h1>

    {{-- فورم الفلاتر --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('student.teachers.index') }}">
                <div class="row g-3">

                    {{-- الدولة --}}
                    <div class="col-md-3">
                        <label class="form-label">الدولة</label>
                        <select name="country" class="form-select">
                            <option value="">الكل</option>
                            @foreach($countries as $country)
                                <option value="{{ $country }}"
                                    {{ (isset($filters['country']) && $filters['country'] == $country) ? 'selected' : '' }}>
                                    {{ $country }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- المدينة --}}
                    <div class="col-md-3">
                        <label class="form-label">المدينة</label>
                        <select name="city" class="form-select">
                            <option value="">الكل</option>
                            @foreach($cities as $city)
                                <option value="{{ $city }}"
                                    {{ (isset($filters['city']) && $filters['city'] == $city) ? 'selected' : '' }}>
                                    {{ $city }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- المادة --}}
                    <div class="col-md-3">
                        <label class="form-label">المادة</label>
                        <select name="subject" class="form-select">
                            <option value="">الكل</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject }}"
                                    {{ (isset($filters['subject']) && $filters['subject'] == $subject) ? 'selected' : '' }}>
                                    {{ $subject }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- الصف --}}
                    <div class="col-md-3">
                        <label class="form-label">الصف</label>
                        <select name="grade" class="form-select">
                            <option value="">الكل</option>
                            @foreach($grades as $grade)
                                <option value="{{ $grade }}"
                                    {{ (isset($filters['grade']) && $filters['grade'] == $grade) ? 'selected' : '' }}>
                                    الصف {{ $grade }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- المنهج --}}
                    <div class="col-md-3">
                        <label class="form-label">نوع المنهج</label>
                        <select name="curriculum" class="form-select">
                            <option value="">الكل</option>
                            @foreach($curricula as $curr)
                                <option value="{{ $curr }}"
                                    {{ (isset($filters['curriculum']) && $filters['curriculum'] == $curr) ? 'selected' : '' }}>
                                    {{ $curr }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- أونلاين / حضوري --}}
                    <div class="col-md-3">
                        <label class="form-label">طريقة الدرس</label>
                        <select name="mode" class="form-select">
                            <option value="">الكل</option>
                            <option value="online"  {{ (isset($filters['mode']) && $filters['mode'] == 'online') ? 'selected' : '' }}>أونلاين</option>
                            <option value="onsite"  {{ (isset($filters['mode']) && $filters['mode'] == 'onsite') ? 'selected' : '' }}>حضوري</option>
                        </select>
                    </div>

                    {{-- أقل تقييم --}}
                    <div class="col-md-3">
                        <label class="form-label">أقل تقييم</label>
                        <select name="rating_min" class="form-select">
                            <option value="">بدون</option>
                            @foreach([1,2,3,4,5] as $r)
                                <option value="{{ $r }}"
                                    {{ (isset($filters['rating_min']) && $filters['rating_min'] == $r) ? 'selected' : '' }}>
                                    {{ $r }} ⭐ فأعلى
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- السعر من --}}
                    <div class="col-md-3">
                        <label class="form-label">السعر (من)</label>
                        <input type="number" step="0.01" name="price_min" class="form-control"
                               value="{{ $filters['price_min'] ?? '' }}" placeholder="مثال: 50">
                    </div>

                    {{-- السعر إلى --}}
                    <div class="col-md-3">
                        <label class="form-label">السعر (إلى)</label>
                        <input type="number" step="0.01" name="price_max" class="form-control"
                               value="{{ $filters['price_max'] ?? '' }}" placeholder="مثال: 150">
                    </div>

                    {{-- التوفّر --}}
                    <div class="col-md-3">
                        <label class="form-label">التوفّر</label>
                        <select name="availability" class="form-select">
                            <option value="">الكل</option>
                            <option value="today"    {{ (isset($filters['availability']) && $filters['availability'] == 'today') ? 'selected' : '' }}>متاح اليوم</option>
                            <option value="tomorrow" {{ (isset($filters['availability']) && $filters['availability'] == 'tomorrow') ? 'selected' : '' }}>متاح غدًا</option>
                        </select>
                    </div>

                    {{-- أزرار --}}
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            بحث
                        </button>
                        <a href="{{ route('student.teachers.index') }}" class="btn btn-outline-secondary">
                            إعادة تعيين
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- قائمة المعلّمين --}}
    @if($teachers->count() === 0)
        <div class="alert alert-info">
            لا يوجد معلّمون مطابقون لخيارات البحث الحالية.
        </div>
    @else
        <div class="row g-4">
            @foreach($teachers as $teacher)
                @php
                    $profile = $teacher->teacherProfile;
                @endphp

                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">

                        {{-- صورة المعلّم --}}
                        @php
                            $photo = $profile && $profile->profile_photo_path
                                ? asset('storage/'.$profile->profile_photo_path)
                                : asset('images/teacher-placeholder.png');
                        @endphp

                        <img
                            src="{{ $photo }}"
                            alt="صورة المعلّم"
                            class="card-img-top teacher-card-img"
                        >

                        <div class="card-body">
                            <h5 class="card-title text-center">
                                {{ $teacher->name }}
                            </h5>

                            @if($profile && $profile->headline)
                                <p class="text-muted text-center mb-2">
                                    {{ $profile->headline }}
                                </p>
                            @endif

                            <p class="mb-1">
                                <strong>المادة:</strong>
                                {{ $profile->main_subject ?? '-' }}
                            </p>

                            <p class="mb-1">
                                <strong>الموقع:</strong>
                                @if($profile && $profile->country)
                                    {{ $profile->country }}
                                    @if($profile->city)
                                        - {{ $profile->city }}
                                    @endif
                                @else
                                    -
                                @endif
                            </p>

                            <p class="mb-1">
                                <strong>الخبرة:</strong>
                                @if($profile && $profile->experience_years)
                                    {{ $profile->experience_years }} سنة
                                @else
                                    لم تُحدّد
                                @endif
                            </p>

                            {{-- التقييم --}}
                            <p class="mb-1">
                                <strong>التقييم:</strong>
                                @php
                                    $rating = (float) ($teacher->average_rating ?? 0);
                                    $fullStars = floor($rating);
                                @endphp

                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $fullStars)
                                        <span class="text-warning">★</span>
                                    @else
                                        <span class="text-muted">☆</span>
                                    @endif
                                @endfor

                                <span class="ms-1">
                                    ({{ number_format($rating, 1) }})
                                    <small class="text-muted">
                                        {{ $teacher->ratings_count ?? 0 }} تقييم
                                    </small>
                                </span>
                            </p>

                            {{-- أقل سعر ساعة --}}
                            @php
                                $basePrice = null;
                                if ($profile) {
                                    if (!is_null($profile->hourly_rate_online)) {
                                        $basePrice = $profile->hourly_rate_online;
                                    } elseif (!is_null($profile->hourly_rate_onsite)) {
                                        $basePrice = $profile->hourly_rate_onsite;
                                    }
                                }
                            @endphp
                            <p class="mb-1">
                                <strong>السعر يبدأ من:</strong>
                                @if($basePrice)
                                    {{ $basePrice }} / ساعة
                                @else
                                    لم يُحدّد
                                @endif
                            </p>

                            {{-- شارات الأونلاين / الحضوري --}}
                            <p class="mb-0 mt-2">
                                @if($profile && $profile->teaches_online)
                                    <span class="badge bg-success">أونلاين</span>
                                @endif
                                @if($profile && $profile->teaches_onsite)
                                    <span class="badge bg-info text-dark">حضوري</span>
                                @endif
                                @if(!$profile || (!$profile->teaches_online && !$profile->teaches_onsite))
                                    <span class="badge bg-secondary">لم تُحدّد طريقة الدرس</span>
                                @endif
                            </p>
                        </div>

                        <div class="card-footer bg-white">
                            <div class="d-grid gap-2">
                                {{-- زر عرض التفاصيل --}}
                                <a href="{{ route('student.teachers.show', $teacher->id) }}"
                                   class="btn btn-outline-primary btn-sm">
                                    عرض التفاصيل
                                </a>

                                {{-- زر احجز حصة مباشرة --}}
                                <a href="{{ route('student.bookings.create', ['teacher_id' => $teacher->id]) }}"
                                   class="btn btn-primary btn-sm">
                                    احجز حصة مع هذا المعلّم
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- الباجيناشن --}}
        <div class="mt-4">
            {{ $teachers->links() }}
        </div>
    @endif
</div>
@endsection
