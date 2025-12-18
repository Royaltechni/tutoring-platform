@extends('layouts.student')

@section('page_title', 'ملف المعلّم')

@push('styles')
<style>
    .teacher-show { width: 100%; }
    .teacher-show .teacher-card-img{
        width: 140px;
        height: 140px;
        object-fit: cover;
        object-position: top;
    }
    .teacher-show .nav-pills .nav-link{ border-radius: 12px; }
</style>
@endpush

@section('content')
<div class="teacher-show" dir="rtl">

    @php
        $backRoute = route('student.teachers.index');

        $profile = $teacher->teacherProfile ?? null;

        $photo = ($profile && $profile->profile_photo_path)
            ? asset('storage/'.$profile->profile_photo_path)
            : asset('images/teacher-placeholder.png');

        // Helpers
        $toList = function ($value) {
            if (empty($value)) return [];
            if (is_array($value)) return array_values(array_filter(array_map('trim', $value)));
            if (is_string($value)) return array_values(array_filter(array_map('trim', explode(',', $value))));
            return [];
        };

        // Country + cities
        $countryName = $profile->country ?? ($teacher->country ?? '-');
        $cityNames   = [];

        try {
            if ($profile && !empty($profile->country_id)) {
                $countryModel = \App\Models\Country::find($profile->country_id);
                if ($countryModel) {
                    $countryName = app()->getLocale() === 'ar'
                        ? ($countryModel->name_ar ?: $countryModel->name_en)
                        : ($countryModel->name_en ?: $countryModel->name_ar);
                }
            }

            $ids = $profile->onsite_city_ids ?? null;
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                if (is_array($decoded)) $ids = $decoded;
            }

            if (is_array($ids) && count($ids)) {
                $cities = \App\Models\City::whereIn('id', $ids)->get();
                foreach ($cities as $c) {
                    $cityNames[] = app()->getLocale() === 'ar'
                        ? ($c->name_ar ?: $c->name_en)
                        : ($c->name_en ?: $c->name_ar);
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $cityTextFallback = $profile->city ?? ($teacher->city ?? null);

        // Video (youtube/vimeo/mp4)
        $videoType = null; // youtube|vimeo|mp4
        $videoSrc  = null;

        $rawVideo =
            ($profile->intro_video_url ?? null)
            ?? ($profile->intro_video_path ?? null)
            ?? ($profile->intro_video ?? null)
            ?? ($teacher->intro_video_url ?? null)
            ?? ($teacher->intro_video_path ?? null)
            ?? ($teacher->intro_video ?? null);

        if ($rawVideo) {
            $url = trim($rawVideo);
            $isExternal = str_starts_with($url, 'http://') || str_starts_with($url, 'https://');

            if (!$isExternal) {
                $u = ltrim($url, '/');
                if (str_starts_with($u, 'public/')) $u = substr($u, 7);

                $isStoragePrefixed = str_starts_with($u, 'storage/');
                $full = $isStoragePrefixed ? asset($u) : asset('storage/'.$u);

                if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $full)) {
                    $videoType = 'mp4';
                    $videoSrc  = $full;
                }
            } else {
                if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $url)) {
                    $videoType = 'mp4';
                    $videoSrc  = $url;
                } elseif (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
                    $videoType = 'youtube';
                    $vid = null;

                    if (preg_match('~youtube\.com/embed/([a-zA-Z0-9_-]+)~', $url, $m)) $vid = $m[1] ?? null;
                    if (!$vid && preg_match('~youtube\.com/shorts/([a-zA-Z0-9_-]+)~', $url, $m)) $vid = $m[1] ?? null;

                    if (!$vid && str_contains($url, 'youtu.be/')) {
                        $parsed = parse_url($url);
                        $vid = ltrim($parsed['path'] ?? '', '/');
                    }
                    if (!$vid && str_contains($url, 'youtube.com/watch')) {
                        $parsed = parse_url($url);
                        $query  = [];
                        parse_str($parsed['query'] ?? '', $query);
                        $vid = $query['v'] ?? null;
                    }

                    $videoSrc = $vid ? 'https://www.youtube.com/embed/'.$vid : null;
                    if (!$videoSrc) $videoType = null;
                } elseif (str_contains($url, 'vimeo.com')) {
                    $videoType = 'vimeo';
                    $vid = null;

                    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) $vid = $m[1] ?? null;

                    $videoSrc = $vid ? 'https://player.vimeo.com/video/'.$vid : null;
                    if (!$videoSrc) $videoType = null;
                }
            }
        }

        // Main subject
        $mainSubject = $profile->main_subject ?? $teacher->main_subject ?? '-';

        // Prices + flags
        $onlineHour  = $profile->hourly_rate_online    ?? $teacher->hourly_rate_online    ?? null;
        $onlineHalf  = $profile->half_hour_rate_online ?? $teacher->half_hour_rate_online ?? null;
        $onsiteHour  = $profile->hourly_rate_onsite    ?? $teacher->hourly_rate_onsite    ?? null;
        $onsiteHalf  = $profile->half_hour_rate_onsite ?? $teacher->half_hour_rate_onsite ?? null;

        if (is_null($onlineHalf) && !is_null($onlineHour)) $onlineHalf = $onlineHour / 2;
        if (is_null($onsiteHalf) && !is_null($onsiteHour)) $onsiteHalf = $onsiteHour / 2;

        $onlineFlag = (bool)($profile->teaches_online ?? $teacher->teaches_online ?? false);
        $onsiteFlag = (bool)($profile->teaches_onsite ?? $teacher->teaches_onsite ?? false);

        $canOnline = $onlineFlag || !is_null($onlineHour) || !is_null($onlineHalf);
        $canOnsite = $onsiteFlag || !is_null($onsiteHour) || !is_null($onsiteHalf);

        $currency = 'AED';

        // Lists
        $curriculaList = !empty($teacher->curricula)
            ? $toList($teacher->curricula)
            : ($profile ? $toList($profile->curricula ?? null) : []);

        $subjectsList  = $toList($profile->subjects ?? ($teacher->subjects ?? null));
        $languagesList = $toList($profile->languages ?? ($teacher->languages ?? null));

        // Rating
        $rating       = (float)($teacher->average_rating ?? 0);
        $fullStars    = floor($rating);
        $ratingsCount = (int)($teacher->ratings_count ?? 0);
    @endphp

    {{-- ✅ Debug مؤقت (فعّله عند الحاجة فقط) --}}
    {{--
    <pre style="background:#111;color:#0f0;padding:12px;border-radius:8px;direction:ltr;">
teacher_id: {{ $teacher->id }}
has_profile: {{ $profile ? 'yes' : 'no' }}
teaching_style: {{ $profile->teaching_style ?? 'NULL' }}
loaded_relations: {{ implode(',', array_keys($teacher->getRelations())) }}
    </pre>
    --}}

    {{-- زر الرجوع --}}
    <div class="d-flex mb-3 justify-content-start">
    <a href="{{ $backRoute }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
        <span style="font-size:18px; line-height:1;">←</span>
        <span>الرجوع إلى قائمة المعلّمين</span>
    </a>
    </div>


    {{-- Quick Stats --}}
    @include('partials.teacher.quick-stats', [
        'teacher' => $teacher,
        'profile' => $profile,
        'canOnline' => $canOnline,
        'canOnsite' => $canOnsite,
        'rating' => $rating,
        'ratingsCount' => $ratingsCount,
    ])

    <div class="row g-3">

        {{-- ✅ Sidebar (يمين على md+) --}}
        <div class="col-md-4 order-md-2">

            @include('partials.teacher.profile-card', [
                'teacher' => $teacher,
                'profile' => $profile,
                'photo' => $photo,
                'mainSubject' => $mainSubject,
                'countryName' => $countryName,
                'cityNames' => $cityNames,
                'cityTextFallback' => $cityTextFallback,
                'fullStars' => $fullStars,
                'rating' => $rating,
                'ratingsCount' => $ratingsCount,
                'curriculaList' => $curriculaList,
                'subjectsList' => $subjectsList,
                'languagesList' => $languagesList,
                'showVerifiedBadges' => true,
            ])

            @includeWhen(($videoType && $videoSrc), 'partials.teacher.intro-video', [
                'videoType' => $videoType,
                'videoSrc'  => $videoSrc,
            ])

            @include('partials.teacher.onsite-places', [
                'canOnsite' => $canOnsite,
                'countryName' => $countryName,
                'cityNames' => $cityNames,
                'cityTextFallback' => $cityTextFallback,
            ])

        </div>

        {{-- ✅ Details (يسار على md+) --}}
        <div class="col-md-8 order-md-1">

            {{-- ✅ tabs هو المصدر الواحد للـ teaching_style / availability / cancel_policy --}}
            @include('partials.teacher.tabs', [
                'profile' => $profile,
                'teacher' => $teacher,
                'currency' => $currency,
                'canOnline' => $canOnline,
                'canOnsite' => $canOnsite,
                'onlineHalf' => $onlineHalf,
                'onlineHour' => $onlineHour,
                'onsiteHalf' => $onsiteHalf,
                'onsiteHour' => $onsiteHour,
                'rating' => $rating,
                'ratingsCount' => $ratingsCount,
                'idPrefix' => 'student', // ✅ يمنع تعارض IDs
            ])

            @include('partials.teacher.booking-cta', [
                'teacher'   => $teacher,
                'canOnline' => $canOnline,
                'canOnsite' => $canOnsite,
                // اختياري:
                // 'showMessageBtn' => true,
                // 'showTrialBtn'   => ($teacher->offers_trial ?? false),
            ])

        </div>
    </div>

</div>
@endsection
