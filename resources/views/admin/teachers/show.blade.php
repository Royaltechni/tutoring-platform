@extends('layouts.app')

@section('page_title', 'ملف المعلّم (مراجعة الأدمن)')

@push('styles')
<style>
    .teacher-show { width: 100%; }
    .teacher-show .nav-pills .nav-link { border-radius: 12px; }

    .admin-review-card .badge { font-size: .85rem; }
    .doc-thumb {
        width: 100%;
        max-height: 180px;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid rgba(0,0,0,.08);
        background: #fff;
    }
    .doc-box {
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 14px;
        padding: 12px;
        background: #fff;
    }
    .kv { display:flex; gap:10px; align-items:flex-start; }
    .kv .k { min-width: 140px; color:#6c757d; }
    .kv .v { flex:1; }
    .kv + .kv { margin-top: 8px; }
    .admin-actions .btn { min-width: 160px; }
    .status-pill { border-radius: 12px; }

    /* ✅ Stack helpers */
    .stack-col{ display:flex; flex-direction:column; gap:12px; }
    .stack-col > *{ margin:0 !important; }
    @media (max-width: 991.98px){
        .stack-col{ gap:10px; }
    }

    /* ✅ Sticky Right Column */
    .admin-right-sticky {
        position: sticky;
        top: 16px;
    }

    /* ✅ Audit Log */
    .audit-box { padding: 14px; }
    .audit-header{
        display:flex; align-items:flex-start; justify-content:space-between; gap:10px;
        padding-bottom: 10px; margin-bottom: 10px;
        border-bottom: 1px solid rgba(0,0,0,.06);
    }
    .audit-title{ font-weight:900; margin:0; }
    .audit-meta{ color:#6c757d; font-size:.85rem; display:flex; flex-wrap:wrap; align-items:center; gap:8px; }
    .audit-meta .pill{ background: rgba(0,0,0,.04); border:1px solid rgba(0,0,0,.06); padding:4px 10px; border-radius:999px; }

    .audit-controls{
        display:flex; flex-wrap:wrap; gap:10px;
        margin-top: 10px;
        align-items:center;
    }
    .audit-search{
        flex: 1;
        min-width: 220px;
        position: relative;
    }
    .audit-search input{
        border-radius: 999px;
        padding-inline-start: 38px;
    }
    .audit-search .icon{
        position:absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        opacity: .55;
        font-size: 14px;
        pointer-events:none;
    }
    .audit-sort select{
        border-radius: 999px;
        min-width: 170px;
    }
    .audit-reset,
    .audit-export,
    .audit-print{
        border-radius: 999px;
        font-weight: 800;
        white-space: nowrap;
    }

    .audit-filters{
        display:flex; flex-wrap:wrap; gap:8px;
        margin-top: 10px;
    }
    .audit-filter-btn{
        border-radius: 999px;
        border: 1px solid rgba(0,0,0,.10);
        background: rgba(0,0,0,.02);
        padding: 6px 10px;
        font-size: .85rem;
        font-weight: 800;
        color:#212529;
        cursor:pointer;
        transition: .15s ease-in-out;
        line-height:1;
        display:inline-flex;
        align-items:center;
        gap:8px;
    }
    .audit-filter-btn:hover{ background: rgba(0,0,0,.05); }
    .audit-filter-btn.active{
        border-color: rgba(13,110,253,.28);
        background: rgba(13,110,253,.10);
        color:#0d6efd;
    }
    .audit-filter-btn .count{
        font-weight:900;
        font-size:.82rem;
        opacity:.85;
    }

    .audit-list{
        max-height: 460px;
        overflow:auto;
        padding: 6px 2px 2px;
        margin-top: 8px;
    }

    .audit-empty{
        border: 1px dashed rgba(0,0,0,.14);
        background: rgba(0,0,0,.02);
        border-radius: 14px;
        padding: 12px;
        color:#6c757d;
        text-align: end;
    }

    .audit-item{
        position: relative;
        padding: 14px 14px 14px 14px;
        border: 1px solid rgba(0,0,0,.06);
        border-radius: 16px;
        background: #fff;
        margin-bottom: 12px;
        box-shadow: 0 6px 14px rgba(0,0,0,.03);
    }
    .audit-item:last-child{ margin-bottom: 0; }

    .audit-item::before{
        content:"";
        position:absolute;
        right: 18px;
        top: 54px;
        bottom: 14px;
        width: 2px;
        background: rgba(0,0,0,.06);
    }

    .audit-dot{
        position:absolute;
        right: 11px;
        top: 18px;
        width: 16px;
        height: 16px;
        border-radius: 999px;
        border: 3px solid #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,.12);
        background: #6c757d;
    }
    .audit-dot.success{ background: #198754; }
    .audit-dot.danger { background: #dc3545; }
    .audit-dot.warning{ background: #ffc107; }
    .audit-dot.info{ background: #0d6efd; }

    .audit-top{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        padding-right: 32px;
    }
    .audit-who{
        font-weight:900;
        line-height:1.25;
    }
    .audit-when{
        color:#6c757d;
        font-size:.85rem;
        white-space:nowrap;
    }

    .audit-status-row{
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        gap:8px;
        margin-top: 10px;
        padding-right: 32px;
    }

    .status-chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: .88rem;
        border:1px solid rgba(0,0,0,.06);
        background: rgba(0,0,0,.03);
    }
    .status-chip .arrow{ opacity:.7; }

    .status-chip.success{
        background: rgba(25,135,84,.12);
        border-color: rgba(25,135,84,.20);
    }
    .status-chip.danger{
        background: rgba(220,53,69,.12);
        border-color: rgba(220,53,69,.20);
    }
    .status-chip.warning{
        background: rgba(255,193,7,.18);
        border-color: rgba(255,193,7,.28);
    }
    .status-chip.info{
        background: rgba(13,110,253,.10);
        border-color: rgba(13,110,253,.18);
    }

    .meta-chip{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: .82rem;
        border:1px solid rgba(0,0,0,.06);
        background: rgba(0,0,0,.02);
        color:#495057;
        max-width: 100%;
    }
    .meta-chip code{
        background: transparent;
        padding: 0;
        font-size: .82rem;
        color: inherit;
    }

    .audit-note{
        margin-top: 10px;
        margin-right: 32px;
        border-radius: 14px;
        padding: 10px 12px;
        border: 1px solid rgba(13,110,253,.12);
        background: rgba(13,110,253,.06);
        font-size: .92rem;
    }
    .audit-note.danger{
        border-color: rgba(220,53,69,.18);
        background: rgba(220,53,69,.07);
    }
    .audit-note.warning{
        border-color: rgba(255,193,7,.28);
        background: rgba(255,193,7,.14);
    }
    .audit-note .t{ font-weight:900; margin-bottom:4px; }

    .audit-details{
        margin-top: 10px;
        margin-right: 32px;
    }
    .audit-details summary{
        cursor:pointer;
        user-select:none;
        color:#0d6efd;
        font-size:.88rem;
        font-weight:800;
        list-style: none;
        display:inline-flex;
        align-items:center;
        gap:8px;
    }
    .audit-details summary::-webkit-details-marker{ display:none; }
    .audit-details summary .chev{
        width: 18px; height: 18px;
        display:inline-flex; align-items:center; justify-content:center;
        border-radius: 6px;
        border:1px solid rgba(13,110,253,.18);
        background: rgba(13,110,253,.06);
        font-size: 12px;
        transition: transform .15s ease;
    }
    .audit-details[open] summary .chev{ transform: rotate(180deg); }

    .audit-details .box{
        margin-top: 8px;
        border: 1px dashed rgba(0,0,0,.12);
        border-radius: 12px;
        padding: 10px;
        background: rgba(0,0,0,.02);
        font-size: .85rem;
        color:#495057;
        word-break: break-word;
        position: relative;
    }
    .audit-details .box pre{
        margin:0;
        white-space: pre-wrap;
        font-size: .82rem;
        padding-top: 4px;
    }
    .audit-copy{
        position:absolute;
        left: 10px;
        top: 10px;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 800;
    }

    .audit-toast{
        position: fixed;
        left: 18px;
        bottom: 18px;
        z-index: 9999;
        min-width: 220px;
        max-width: 320px;
        background: #111827;
        color: #fff;
        padding: 10px 12px;
        border-radius: 12px;
        box-shadow: 0 12px 30px rgba(0,0,0,.25);
        opacity: 0;
        transform: translateY(10px);
        transition: .18s ease;
        font-size: .9rem;
    }
    .audit-toast.show{
        opacity: 1;
        transform: translateY(0);
    }

    .soft-info{ background: rgba(13,110,253,.06); border:1px solid rgba(13,110,253,.12); border-radius:14px; padding:10px 12px; }
    .soft-info .t{ font-weight:900; }

    .page-blocker{
        position: fixed;
        inset: 0;
        background: rgba(255,255,255,.70);
        backdrop-filter: blur(2px);
        z-index: 9998;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }
    .page-blocker.show{ display:flex; }
    .page-blocker .box{
        background: #fff;
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 16px;
        padding: 14px 16px;
        box-shadow: 0 14px 40px rgba(0,0,0,.10);
        display:flex;
        align-items:center;
        gap:12px;
        font-weight: 900;
    }
    .spinner{
        width: 22px;
        height: 22px;
        border-radius: 999px;
        border: 3px solid rgba(0,0,0,.12);
        border-top-color: rgba(13,110,253,.9);
        animation: spin .8s linear infinite;
    }
    @keyframes spin{ to { transform: rotate(360deg); } }

    .no-print{ }

    /* ✅ طباعة الصفحة كاملة (الوضع الطبيعي) */
    @media print {
        .no-print,
        .btn,
        .audit-controls,
        .audit-filters,
        .audit-copy,
        .audit-toast,
        .page-blocker,
        .admin-right-sticky { display: none !important; }

        .teacher-show.container{ max-width: 100% !important; padding: 0 !important; }
        .doc-box{ box-shadow: none !important; }
        .audit-list{ max-height: none !important; overflow: visible !important; }
        .audit-item{ page-break-inside: avoid; }
        details.audit-details{ display:none !important; }
    }
</style>
@endpush

@section('content')
<div class="teacher-show container py-4" dir="rtl">

    @php
        $backRoute = route('admin.teachers.index');
        $profile = $teacher->teacherProfile ?? null;

        $toList = function ($value) {
            if (empty($value)) return [];
            if (is_array($value)) return array_values(array_filter(array_map('trim', $value)));
            if (is_string($value)) return array_values(array_filter(array_map('trim', explode(',', $value))));
            return [];
        };

        $toJsonList = function ($value) use ($toList) {
            if (empty($value)) return [];
            if (is_array($value)) return $value;
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) return $decoded;
                return $toList($value);
            }
            return [];
        };

        $fileUrl = function ($path) {
            if (!$path) return null;
            $p = trim($path);
            $isExternal = str_starts_with($p, 'http://') || str_starts_with($p, 'https://');
            if ($isExternal) return $p;

            $u = ltrim($p, '/');
            if (str_starts_with($u, 'public/')) $u = substr($u, 7);
            if (str_starts_with($u, 'storage/')) return asset($u);
            return asset('storage/'.$u);
        };

        $isImage = fn($url) => $url ? (bool) preg_match('/\.(png|jpe?g|gif|webp)(\?.*)?$/i', $url) : false;
        $isPdf   = fn($url) => $url ? (bool) preg_match('/\.pdf(\?.*)?$/i', $url) : false;

        $safeVal = function ($v, $fallback='-') {
            if (is_null($v)) return $fallback;
            if (is_string($v) && trim($v)==='') return $fallback;
            return $v;
        };

        $photo = ($profile && $profile->profile_photo_path)
            ? asset('storage/'.$profile->profile_photo_path)
            : asset('images/teacher-placeholder.png');

        $countryName = $profile->country ?? ($teacher->country ?? '-');
        $cityNames = [];

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
        } catch (\Throwable $e) {}

        $cityTextFallback = $profile->city ?? ($teacher->city ?? null);

        $videoType = null;
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
                $full = $fileUrl($url);
                if ($full && preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $full)) {
                    $videoType = 'mp4'; $videoSrc = $full;
                }
            } else {
                if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $url)) {
                    $videoType = 'mp4'; $videoSrc = $url;
                } elseif (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
                    $videoType = 'youtube'; $vid = null;

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
                    $videoType = 'vimeo'; $vid = null;
                    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) $vid = $m[1] ?? null;

                    $videoSrc = $vid ? 'https://player.vimeo.com/video/'.$vid : null;
                    if (!$videoSrc) $videoType = null;
                }
            }
        }

        $mainSubject = $profile->main_subject ?? $teacher->main_subject ?? '-';

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

        $curriculaList = !empty($teacher->curricula)
            ? $toList($teacher->curricula)
            : ($profile ? $toList($profile->curricula ?? null) : []);

        $subjectsList  = $toList($profile->subjects ?? ($teacher->subjects ?? null));
        $languagesList = $toList($profile->languages ?? ($teacher->languages ?? null));

        $teachingStyle = $profile->teaching_style ?? $teacher->teaching_style ?? null;
        $cancelPolicy  = $profile->cancel_policy  ?? $teacher->cancel_policy  ?? null;
        $availability  = $profile->availability   ?? $teacher->availability   ?? null;

        $rating       = (float)($teacher->average_rating ?? 0);
        $fullStars    = floor($rating);
        $ratingsCount = (int)($teacher->ratings_count ?? 0);

        $teacherName = $teacher->name ?? trim(($teacher->first_name ?? '').' '.($teacher->last_name ?? ''));
        $email = $teacher->email ?? ($profile->email ?? null);

        $phone =
            $profile->phone_mobile
            ?? $profile->phone
            ?? $profile->mobile
            ?? $profile->phone_number
            ?? $profile->mobile_number
            ?? $teacher->phone
            ?? $teacher->mobile
            ?? $teacher->phone_number
            ?? $teacher->mobile_number
            ?? null;

        $whatsapp =
            $profile->whatsapp_number
            ?? $profile->whatsapp
            ?? $teacher->whatsapp_number
            ?? $teacher->whatsapp
            ?? null;

        $address =
            $profile->address_details
            ?? $profile->address
            ?? $profile->location_text
            ?? $profile->full_address
            ?? null;

        $social = [
            'website'   => $profile->website_url   ?? $profile->website   ?? $teacher->website_url   ?? $teacher->website   ?? null,
            'facebook'  => $profile->facebook_url  ?? $profile->facebook  ?? $teacher->facebook_url  ?? $teacher->facebook  ?? null,
            'instagram' => $profile->instagram_url ?? $profile->instagram ?? $teacher->instagram_url ?? $teacher->instagram ?? null,
            'tiktok'    => $profile->tiktok_url    ?? $profile->tiktok    ?? $teacher->tiktok_url    ?? $teacher->tiktok    ?? null,
            'youtube'   => $profile->youtube_url   ?? $profile->youtube   ?? $teacher->youtube_url   ?? $teacher->youtube   ?? null,
            'linkedin'  => $profile->linkedin_url  ?? $profile->linkedin  ?? $teacher->linkedin_url  ?? $teacher->linkedin  ?? null,
        ];

        $status =
            $profile->account_status
            ?? $teacher->teacher_status
            ?? $teacher->account_status
            ?? 'pending';

        $rejectionReason =
            $profile->rejection_reason
            ?? $profile->admin_rejection_reason
            ?? $teacher->rejection_reason
            ?? $teacher->admin_rejection_reason
            ?? null;

        $adminNote =
            $profile->admin_note
            ?? $profile->admin_notes
            ?? $teacher->admin_note
            ?? $teacher->admin_notes
            ?? null;

        $statusBadge = 'secondary';
        $statusText  = 'غير محدّد';
        if ($status === 'approved') { $statusBadge='success'; $statusText='مقبول / مفعَّل'; }
        elseif ($status === 'pending') { $statusBadge='warning'; $statusText='قيد المراجعة'; }
        elseif ($status === 'rejected') { $statusBadge='danger'; $statusText='مرفوض'; }

        $idDocPath =
            $profile->id_document_path
            ?? $profile->id_card_path
            ?? $profile->identity_path
            ?? $profile->passport_path
            ?? null;

        $teachingPermitPath =
            $profile->teaching_permit_path
            ?? $profile->teaching_license_path
            ?? $profile->permit_path
            ?? $profile->license_path
            ?? null;

        $supportingDocs =
            $profile->supporting_docs
            ?? $profile->supporting_documents
            ?? $profile->documents
            ?? null;

        $supportingDocsList = $toJsonList($supportingDocs);

        $docs = [
            'هوية / جواز' => $fileUrl($idDocPath),
            'تصريح / رخصة تدريس' => $fileUrl($teachingPermitPath),
        ];
        $docs = array_filter($docs, fn($u) => !empty($u));

        $supportingUrls = [];
        foreach ($supportingDocsList as $item) {
            if (is_string($item)) {
                $u = $fileUrl($item);
                if ($u) $supportingUrls[] = $u;
            } elseif (is_array($item)) {
                $candidate = $item['url'] ?? $item['path'] ?? $item['file'] ?? null;
                $u = $fileUrl($candidate);
                if ($u) $supportingUrls[] = $u;
            }
        }
        $supportingUrls = array_values(array_unique($supportingUrls));

        // ✅ شروط تفعيل أزرار الأدمن
        $isSubmitted = !empty($profile?->submitted_at);
        $canReview   = $isSubmitted && ($status === 'pending');

        $reviewBlockMsg = null;
        if (!$isSubmitted) {
            $reviewBlockMsg = 'هذا الملف ما زال "مسودة" ولم يتم إرساله للمراجعة بعد، لذلك لا يمكن قبول/رفض الحساب.';
        } elseif ($status !== 'pending') {
            $reviewBlockMsg = 'تم اتخاذ قرار بالفعل على هذا الملف (مقبول/مرفوض)، ولا يمكن تنفيذ قبول/رفض مرة أخرى.';
        }

        // ✅ Audit Log
        $auditSource = $auditLogs ?? ($teacher->auditLogs ?? collect([]));

        $isPaginator = $auditSource instanceof \Illuminate\Contracts\Pagination\Paginator
                    || $auditSource instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;

        $auditItems = $isPaginator ? collect($auditSource->items()) : (is_iterable($auditSource) ? collect($auditSource) : collect([]));
        $auditTotal = $isPaginator
            ? (method_exists($auditSource, 'total') ? (int)$auditSource->total() : (int)$auditSource->count())
            : (int)$auditItems->count();

        $normalizeStatus = function ($s) {
            if (is_null($s)) return null;
            $s = strtolower(trim((string)$s));
            if ($s === '') return null;
            return $s;
        };

        $statusLabel = function ($s) {
            $s = strtolower((string)$s);
            if ($s === 'approved') return 'مقبول';
            if ($s === 'pending')  return 'قيد المراجعة';
            if ($s === 'rejected') return 'مرفوض';
            return $s ?: '-';
        };

        $statusClass = function ($to, $from=null) use ($normalizeStatus) {
            $to = $normalizeStatus($to);
            if ($to === 'approved') return 'success';
            if ($to === 'rejected') return 'danger';
            if ($to === 'pending')  return 'warning';

            $from = $normalizeStatus($from);
            if ($from && $to && $from !== $to) return 'info';
            return 'secondary';
        };

        $statusKey = function ($to, $from=null) use ($normalizeStatus) {
            $to = $normalizeStatus($to);
            if ($to === 'approved') return 'approved';
            if ($to === 'rejected') return 'rejected';
            if ($to === 'pending')  return 'pending';

            $from = $normalizeStatus($from);
            if ($from && $to && $from !== $to) return 'updated';
            return 'other';
        };

        $statusIcon = function ($to, $from=null) use ($normalizeStatus) {
            $to = $normalizeStatus($to);
            if ($to === 'approved') return '✅';
            if ($to === 'rejected') return '⛔';
            if ($to === 'pending')  return '⏳';

            $from = $normalizeStatus($from);
            if ($from && $to && $from !== $to) return '✎';
            return '•';
        };

        // ✅ generic getter for objects/arrays (حتى لو شكل الـ logs مختلف)
        $get = function ($item, array $keys, $default=null) {
            foreach ($keys as $k) {
                if (is_array($item) && array_key_exists($k, $item) && $item[$k] !== null) return $item[$k];
                if (is_object($item) && isset($item->{$k}) && $item->{$k} !== null) return $item->{$k};
                if (is_object($item) && method_exists($item, $k)) {
                    try { $v = $item->{$k}(); if ($v !== null) return $v; } catch (\Throwable $e) {}
                }
            }
            return $default;
        };
    @endphp

    <div class="d-flex mb-3 gap-2 no-print">
        <a href="{{ $backRoute }}"
           style="margin-left:auto"
           class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
            <span style="font-size:18px; line-height:1;">←</span>
            <span>الرجوع إلى قائمة المعلّمين</span>
        </a>

        <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="pagePrintBtn">
            🖨️ <span>طباعة</span>
        </button>
    </div>

    @include('partials.teacher.quick-stats', [
        'teacher' => $teacher,
        'profile' => $profile,
        'canOnline' => $canOnline,
        'canOnsite' => $canOnsite,
        'rating' => $rating,
        'ratingsCount' => $ratingsCount,
    ])

    <div class="d-flex justify-content-start mb-3">
        <span class="btn btn-{{ $statusBadge }} status-pill px-3 py-2">
            الحالة: {{ $statusText }}
        </span>
        <span class="ms-2"></span>
        <span class="btn btn-{{ $isSubmitted ? 'primary' : 'secondary' }} status-pill px-3 py-2">
            المرحلة: {{ $isSubmitted ? 'تم الإرسال' : 'مسودة' }}
        </span>
    </div>

    <div class="row g-3">

        <div class="col-lg-4">
            <div class="stack-col">

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
                    'teacher' => $teacher,
                    'profile' => $profile,
                    'videoType' => $videoType,
                    'videoSrc'  => $videoSrc,
                ])

                <div class="admin-right-sticky">
                    <div class="stack-col">

                        <div class="doc-box">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">إجراءات الأدمن</h6>
                                <span class="badge bg-light text-dark">Approve / Reject</span>
                            </div>

                            @if($status === 'rejected' && $rejectionReason)
                                <div class="alert alert-danger text-end">
                                    <div class="fw-bold mb-1">سبب الرفض الحالي:</div>
                                    <div>{{ $rejectionReason }}</div>
                                </div>
                            @endif

                            @if($adminNote)
                                <div class="alert alert-secondary text-end">
                                    <div class="fw-bold mb-1">ملاحظة أدمن:</div>
                                    <div>{{ $adminNote }}</div>
                                </div>
                            @endif

                            @if(!$canReview && $reviewBlockMsg)
                                <div class="alert alert-warning text-end">
                                    {{ $reviewBlockMsg }}
                                </div>
                            @endif

                            <div class="mb-2">
                                <label class="form-label text-end mb-1">ملاحظة للأدمن (اختياري)</label>
                                <textarea id="sharedAdminNote" class="form-control" rows="2"
                                          placeholder="اكتب ملاحظة مختصرة تُحفظ مع القرار...">{{ old('admin_note') }}</textarea>
                                <div class="text-muted small mt-1">
                                    هذه الملاحظة سيتم إرسالها مع القبول أو الرفض.
                                </div>
                            </div>

                            <div class="admin-actions d-flex flex-column gap-2">

                                <form method="POST"
                                      action="{{ route('admin.teachers.approve', $teacher->id) }}"
                                      id="approveForm">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="admin_note" value="">

                                    <button type="button"
                                            class="btn btn-success d-flex justify-content-center align-items-center gap-2 js-review-action"
                                            data-action="approve"
                                            {{ $canReview ? '' : 'disabled' }}
                                            title="{{ $canReview ? 'قبول/تفعيل' : ($reviewBlockMsg ?? 'غير متاح') }}">
                                        ✅ قبول / تفعيل
                                    </button>
                                </form>

                                <form method="POST"
                                      action="{{ route('admin.teachers.reject', $teacher->id) }}"
                                      id="rejectForm">
                                    @csrf
                                    @method('PATCH')

                                    <label class="form-label text-end mb-1">سبب الرفض (يفضّل كتابته)</label>
                                    <textarea name="rejection_reason" class="form-control" rows="2"
                                              placeholder="اكتب سببًا واضحًا للرفض...">{{ old('rejection_reason') }}</textarea>

                                    <input type="hidden" name="admin_note" value="">

                                    <button type="button"
                                            class="btn btn-danger d-flex justify-content-center align-items-center gap-2 mt-2 js-review-action"
                                            data-action="reject"
                                            {{ $canReview ? '' : 'disabled' }}
                                            title="{{ $canReview ? 'رفض' : ($reviewBlockMsg ?? 'غير متاح') }}">
                                        ⛔ رفض
                                    </button>
                                </form>

                            </div>
                        </div>

                        <div class="doc-box">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">المستندات الرسمية والملفات الداعمة</h6>
                                <span class="badge bg-light text-dark">Documents</span>
                            </div>

                            <div class="row g-2">
                                @forelse($docs as $label => $url)
                                    <div class="col-12">
                                        <div class="p-2 border rounded-3">
                                            <div class="fw-bold mb-2 text-end">{{ $label }}</div>

                                            @if($isImage($url))
                                                <a href="{{ $url }}" target="_blank" rel="noopener">
                                                    <img src="{{ $url }}" alt="{{ $label }}" class="doc-thumb">
                                                </a>
                                            @elseif($isPdf($url))
                                                <div class="text-end">
                                                    <div class="text-muted small mb-2">PDF</div>
                                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">فتح الملف</a>
                                                    <a href="{{ $url }}" download class="btn btn-outline-secondary btn-sm">تحميل</a>
                                                </div>
                                            @else
                                                <div class="text-end">
                                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">فتح</a>
                                                    <a href="{{ $url }}" download class="btn btn-outline-secondary btn-sm">تحميل</a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <div class="alert alert-secondary text-end mb-0">
                                            لا توجد مستندات رسمية محفوظة (تحقق من أسماء أعمدة المسارات في TeacherProfile).
                                        </div>
                                    </div>
                                @endforelse

                                @if(count($supportingUrls))
                                    <div class="col-12"><hr class="my-2"></div>
                                    <div class="col-12">
                                        <div class="fw-bold mb-2 text-end">ملفات داعمة</div>
                                    </div>

                                    @foreach($supportingUrls as $idx => $u)
                                        <div class="col-12">
                                            <div class="p-2 border rounded-3">
                                                <div class="fw-bold mb-2 text-end">ملف داعم #{{ $idx+1 }}</div>

                                                @if($isImage($u))
                                                    <a href="{{ $u }}" target="_blank" rel="noopener">
                                                        <img src="{{ $u }}" alt="Supporting {{ $idx+1 }}" class="doc-thumb">
                                                    </a>
                                                @elseif($isPdf($u))
                                                    <div class="text-end">
                                                        <div class="text-muted small mb-2">PDF</div>
                                                        <a href="{{ $u }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">فتح الملف</a>
                                                        <a href="{{ $u }}" download class="btn btn-outline-secondary btn-sm">تحميل</a>
                                                    </div>
                                                @else
                                                    <div class="text-end">
                                                        <a href="{{ $u }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">فتح</a>
                                                        <a href="{{ $u }}" download class="btn btn-outline-secondary btn-sm">تحميل</a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <div class="col-lg-8">
            <div class="stack-col">

                <div class="doc-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">بيانات التواصل</h6>
                        <span class="badge bg-light text-dark">للتحقق</span>
                    </div>

                    <div class="kv"><div class="k">الاسم</div><div class="v">{{ $safeVal($teacherName) }}</div></div>

                    <div class="kv">
                        <div class="k">الإيميل</div>
                        <div class="v">
                            @if($email && $email !== '-')
                                <a href="mailto:{{ $email }}">{{ $email }}</a>
                            @else - @endif
                        </div>
                    </div>

                    <div class="kv"><div class="k">الهاتف / الموبايل</div><div class="v">{{ $safeVal($phone) }}</div></div>

                    <div class="kv">
                        <div class="k">واتساب</div>
                        <div class="v">
                            @if($whatsapp)
                                <span>{{ $whatsapp }}</span>
                                <span class="text-muted small">—</span>
                                <a class="small" target="_blank" rel="noopener"
                                   href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}">
                                    فتح واتساب
                                </a>
                            @else - @endif
                        </div>
                    </div>

                    <div class="kv"><div class="k">البلد</div><div class="v">{{ $safeVal($countryName) }}</div></div>

                    <div class="kv">
                        <div class="k">المدينة</div>
                        <div class="v">
                            @if(!empty($cityNames)) {{ implode('، ', $cityNames) }}
                            @elseif($cityTextFallback) {{ $cityTextFallback }}
                            @else - @endif
                        </div>
                    </div>

                    <div class="kv"><div class="k">العنوان</div><div class="v">{{ $safeVal($address) }}</div></div>

                    <hr class="my-3">

                    <div class="kv">
                        <div class="k">حسابات التواصل</div>
                        <div class="v">
                            @php
                                $hasSocial = false;
                                foreach($social as $k=>$v){ if(!empty($v)) { $hasSocial=true; break; } }
                            @endphp

                            @if($hasSocial)
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($social as $key => $val)
                                        @if(!empty($val))
                                            <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="{{ $val }}">
                                                {{ strtoupper($key) }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>

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
                    'teachingStyle' => $teachingStyle,
                    'availability' => $availability,
                    'cancelPolicy' => $cancelPolicy,
                    'rating' => $rating,
                    'ratingsCount' => $ratingsCount,
                    'idPrefix' => 'admin',
                ])

                <div class="doc-box audit-box" id="auditBox">
                    <div class="audit-header">
                        <div>
                            <h6 class="audit-title mb-1">سجل القرارات والتغييرات (Logs)</h6>
                            <div class="audit-meta">
                                <span class="pill">الإجمالي: <strong id="auditTotal">{{ $auditTotal }}</strong></span>
                                <span class="pill">المعروض: <strong id="auditShown">{{ $auditTotal }}</strong></span>
                            </div>
                        </div>

                        <div class="no-print">
                            <span class="badge bg-light text-dark">Audit</span>
                        </div>
                    </div>

                    <div class="audit-controls no-print">
                        <div class="audit-search">
                            <input type="text" class="form-control" id="auditSearch" placeholder="ابحث في السجل...">
                            <span class="icon">🔎</span>
                        </div>

                        <div class="audit-sort">
                            <select class="form-select" id="auditSort">
                                <option value="new">الأحدث أولاً</option>
                                <option value="old">الأقدم أولاً</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-outline-secondary audit-reset" id="auditReset">إعادة ضبط</button>
                        <button type="button" class="btn btn-outline-primary audit-export" id="auditExport">تصدير CSV</button>
                        <button type="button" class="btn btn-outline-dark audit-print" id="auditPrint">طباعة</button>
                    </div>

                    <div class="audit-filters no-print" id="auditFilters">
                        <button type="button" class="audit-filter-btn active" data-filter="all">الكل <span class="count" id="cntAll">{{ $auditTotal }}</span></button>
                        <button type="button" class="audit-filter-btn" data-filter="approved">مقبول <span class="count" id="cntApproved">0</span></button>
                        <button type="button" class="audit-filter-btn" data-filter="rejected">مرفوض <span class="count" id="cntRejected">0</span></button>
                        <button type="button" class="audit-filter-btn" data-filter="pending">قيد المراجعة <span class="count" id="cntPending">0</span></button>
                        <button type="button" class="audit-filter-btn" data-filter="updated">تحديث <span class="count" id="cntUpdated">0</span></button>
                        <button type="button" class="audit-filter-btn" data-filter="other">أخرى <span class="count" id="cntOther">0</span></button>
                    </div>

                    @if($auditItems->count() === 0)
                        <div class="audit-empty">
                            لا يوجد Logs حالياً. (لو كنت متأكد إن فيه Logs، غالبًا الكنترولر لم يمرّر <code>$auditLogs</code> أو العلاقة <code>auditLogs</code> غير موجودة).
                        </div>
                    @else
                        <div class="audit-list" id="auditList">
                            @foreach($auditItems as $it)
                                @php
                                    $from = $get($it, ['from_status','old_status','previous_status','from','status_from'], null);
                                    $to   = $get($it, ['to_status','new_status','current_status','to','status_to','status'], null);

                                    $key  = $statusKey($to, $from);
                                    $cls  = $statusClass($to, $from);
                                    $ico  = $statusIcon($to, $from);

                                    $who =
                                        $get($it, ['admin_name','actor_name','user_name','by_name'], null)
                                        ?? (is_object($it) && isset($it->admin) && $it->admin ? ($it->admin->name ?? null) : null)
                                        ?? (is_object($it) && isset($it->user)  && $it->user  ? ($it->user->name ?? null)  : null)
                                        ?? '—';

                                    $note =
                                        $get($it, ['admin_note','note','comment','message','details'], null);

                                    $reason =
                                        $get($it, ['rejection_reason','reason'], null);

                                    $created = $get($it, ['created_at','createdAt','time','at'], null);

                                    $ts = null; $when = '-';
                                    try {
                                        if ($created instanceof \Carbon\Carbon) { $when = $created->format('Y-m-d H:i'); $ts = $created->timestamp; }
                                        elseif (is_string($created) && $created) { $c = \Carbon\Carbon::parse($created); $when = $c->format('Y-m-d H:i'); $ts = $c->timestamp; }
                                    } catch (\Throwable $e) {}

                                    $ip = $get($it, ['ip','ip_address'], null);
                                    $ua = $get($it, ['user_agent','ua'], null);

                                    $searchText = trim(($who ?? '').' '.($statusLabel($from)).' '.($statusLabel($to)).' '.($note ?? '').' '.($reason ?? '').' '.($ip ?? ''));
                                @endphp

                                <div class="audit-item"
                                     data-status="{{ $key }}"
                                     data-ts="{{ $ts ?? 0 }}"
                                     data-search="{{ e(mb_strtolower($searchText)) }}">
                                    <span class="audit-dot {{ $cls }}"></span>

                                    <div class="audit-top">
                                        <div class="audit-who">{{ $who }}</div>
                                        <div class="audit-when">{{ $when }}</div>
                                    </div>

                                    <div class="audit-status-row">
                                        <span class="status-chip {{ $cls }}">
                                            <span>{{ $ico }}</span>
                                            <span>{{ $statusLabel($from) }}</span>
                                            <span class="arrow">→</span>
                                            <span>{{ $statusLabel($to) }}</span>
                                        </span>

                                        @if($ip)
                                            <span class="meta-chip">IP: <code>{{ $ip }}</code></span>
                                        @endif
                                    </div>

                                    @if($note || $reason)
                                        @php
                                            $noteClass = $cls === 'danger' ? 'danger' : ($cls === 'warning' ? 'warning' : '');
                                        @endphp
                                        <div class="audit-note {{ $noteClass }}">
                                            <div class="t">ملاحظة/سبب:</div>
                                            @if($note)
                                                <div>• {{ $note }}</div>
                                            @endif
                                            @if($reason)
                                                <div>• <strong>سبب الرفض:</strong> {{ $reason }}</div>
                                            @endif
                                        </div>
                                    @endif

                                    @if($ua)
                                        <details class="audit-details">
                                            <summary><span class="chev">⌄</span> تفاصيل إضافية</summary>
                                            <div class="box">
                                                <button type="button" class="btn btn-sm btn-outline-secondary audit-copy" data-copy="ua">نسخ</button>
                                                <div class="fw-bold mb-1">User Agent</div>
                                                <pre data-copy-target="ua">{{ $ua }}</pre>
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="soft-info">
                    <div class="t">ملاحظة</div>
                    <div class="text-muted small">هذه صفحة عرض ملف المعلّم للأدمن (مراجعة + توثيق + قرار قبول/رفض).</div>
                </div>

            </div>
        </div>

    </div>

</div>

<div class="audit-toast" id="auditToast">تم ✅</div>

<div class="page-blocker" id="pageBlocker" aria-hidden="true">
    <div class="box">
        <span class="spinner"></span>
        <span>جاري تنفيذ الإجراء…</span>
    </div>
</div>

<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" dir="rtl">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalTitle">تأكيد الإجراء</h5>
                <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-end">
                <div id="confirmModalBody">هل أنت متأكد؟</div>
                <div class="text-muted small mt-2" id="confirmModalHint"></div>
            </div>
            <div class="modal-footer justify-content-start">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="confirmModalYes">تأكيد</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.__PRINT_TEACHER_NAME__ = @json($teacherName ?? '');
    window.__PRINT_ADMIN_NAME__   = @json(auth()->user()->name ?? null);
</script>

<script>
(function () {
    const approveForm = document.getElementById('approveForm');
    const rejectForm  = document.getElementById('rejectForm');
    const sharedNote  = document.getElementById('sharedAdminNote');
    const blocker     = document.getElementById('pageBlocker');
    const toastEl     = document.getElementById('auditToast');

    const modalEl     = document.getElementById('confirmActionModal');
    const modalTitle  = document.getElementById('confirmModalTitle');
    const modalBody   = document.getElementById('confirmModalBody');
    const modalHint   = document.getElementById('confirmModalHint');
    const modalYes    = document.getElementById('confirmModalYes');

    let pendingSubmit = null;

    function showToast(msg) {
        if (!toastEl) return;
        toastEl.textContent = msg || 'تم ✅';
        toastEl.classList.add('show');
        setTimeout(() => toastEl.classList.remove('show'), 1600);
    }

    function setBlock(on) {
        if (!blocker) return;
        blocker.classList.toggle('show', !!on);
    }

    function setHiddenAdminNote(form) {
        if (!form) return;
        const input = form.querySelector('input[name="admin_note"]');
        if (input) input.value = (sharedNote?.value || '').trim();
    }

    function getRejectReason() {
        const t = rejectForm?.querySelector('textarea[name="rejection_reason"]');
        return (t?.value || '').trim();
    }

    function openConfirm({title, body, hint, onYes}) {
        pendingSubmit = onYes;

        modalTitle.textContent = title || 'تأكيد الإجراء';
        modalBody.textContent  = body  || 'هل أنت متأكد؟';
        modalHint.textContent  = hint  || '';

        if (window.bootstrap && window.bootstrap.Modal && modalEl) {
            const m = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            m.show();
        } else {
            const ok = window.confirm((title ? (title + "\n") : "") + (body || "هل أنت متأكد؟"));
            if (ok && typeof pendingSubmit === 'function') pendingSubmit();
        }
    }

    modalYes?.addEventListener('click', function () {
        if (typeof pendingSubmit === 'function') {
            if (window.bootstrap && window.bootstrap.Modal && modalEl) {
                const m = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                m.hide();
            }
            pendingSubmit();
        }
    });

    // ✅ طباعة الصفحة كاملة (زر أعلى الصفحة)
    document.getElementById('pagePrintBtn')?.addEventListener('click', () => window.print());

    // ✅ Actions: approve / reject
    document.querySelectorAll('.js-review-action').forEach(btn => {
        btn.addEventListener('click', function () {
            const action = this.getAttribute('data-action');

            if (action === 'approve') {
                setHiddenAdminNote(approveForm);

                openConfirm({
                    title: 'تأكيد القبول/التفعيل',
                    body: 'هل تريد قبول/تفعيل هذا المعلّم؟',
                    hint: 'سيتم حفظ ملاحظة الأدمن (إن وجدت) مع القرار.',
                    onYes: () => {
                        setBlock(true);
                        approveForm.submit();
                    }
                });
            }

            if (action === 'reject') {
                setHiddenAdminNote(rejectForm);

                const reason = getRejectReason();
                if (!reason) {
                    showToast('اكتب سبب الرفض أولاً ✍️');
                    rejectForm?.querySelector('textarea[name="rejection_reason"]')?.focus();
                    return;
                }

                openConfirm({
                    title: 'تأكيد الرفض',
                    body: 'هل تريد رفض هذا المعلّم؟',
                    hint: 'سيتم إرسال سبب الرفض + ملاحظة الأدمن (إن وجدت).',
                    onYes: () => {
                        setBlock(true);
                        rejectForm.submit();
                    }
                });
            }
        });
    });

    // ==========================
    // ✅ Audit UI (Search/Filter/Sort/Export/Print)
    // ==========================
    const listEl   = document.getElementById('auditList');
    const searchEl = document.getElementById('auditSearch');
    const sortEl   = document.getElementById('auditSort');

    const totalEl  = document.getElementById('auditTotal');
    const shownEl  = document.getElementById('auditShown');

    const btnReset = document.getElementById('auditReset');
    const btnExport= document.getElementById('auditExport');
    const btnPrint = document.getElementById('auditPrint');

    const filterWrap = document.getElementById('auditFilters');
    let currentFilter = 'all';

    function items() {
        if (!listEl) return [];
        return Array.from(listEl.querySelectorAll('.audit-item'));
    }

    function updateCounts() {
        const all = items();

        const counts = { approved:0, rejected:0, pending:0, updated:0, other:0 };
        all.forEach(it => {
            const st = it.getAttribute('data-status') || 'other';
            if (counts[st] === undefined) counts.other++;
            else counts[st]++;
        });

        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = String(v); };
        set('cntApproved', counts.approved);
        set('cntRejected', counts.rejected);
        set('cntPending', counts.pending);
        set('cntUpdated', counts.updated);
        set('cntOther', counts.other);

        if (totalEl) totalEl.textContent = String(all.length);
        const shown = all.filter(it => it.style.display !== 'none').length;
        if (shownEl) shownEl.textContent = String(shown);
        const allCnt = document.getElementById('cntAll');
        if (allCnt) allCnt.textContent = String(all.length);
    }

    function apply() {
        const q = (searchEl?.value || '').trim().toLowerCase();

        items().forEach(it => {
            const st = (it.getAttribute('data-status') || 'other');
            const hay = (it.getAttribute('data-search') || '').toLowerCase();

            const okFilter = (currentFilter === 'all') ? true : (st === currentFilter);
            const okSearch = (!q) ? true : hay.includes(q);

            it.style.display = (okFilter && okSearch) ? '' : 'none';
        });

        const mode = sortEl?.value || 'new';
        const arr = items().slice().sort((a,b) => {
            const ta = parseInt(a.getAttribute('data-ts') || '0', 10);
            const tb = parseInt(b.getAttribute('data-ts') || '0', 10);
            return mode === 'old' ? (ta - tb) : (tb - ta);
        });

        arr.forEach(n => listEl?.appendChild(n));

        updateCounts();
    }

    filterWrap?.addEventListener('click', (e) => {
        const btn = e.target.closest('.audit-filter-btn');
        if (!btn) return;
        currentFilter = btn.getAttribute('data-filter') || 'all';

        filterWrap.querySelectorAll('.audit-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        apply();
    });

    searchEl?.addEventListener('input', apply);
    sortEl?.addEventListener('change', apply);

    btnReset?.addEventListener('click', () => {
        if (searchEl) searchEl.value = '';
        if (sortEl) sortEl.value = 'new';
        currentFilter = 'all';
        filterWrap?.querySelectorAll('.audit-filter-btn').forEach(b => b.classList.remove('active'));
        filterWrap?.querySelector('.audit-filter-btn[data-filter="all"]')?.classList.add('active');
        apply();
        showToast('تمت إعادة الضبط ✅');
    });

    // ✅✅ طباعة الـ Logs فقط (حل نهائي بدون صفحات فاضية)
    function printAuditOnlyNewWindow() {
    const auditBox = document.getElementById('auditBox');
    if (!auditBox) return;

    const clone = auditBox.cloneNode(true);

    // احذف التحكمات والفلاتر داخل النسخة
    clone.querySelectorAll('.audit-controls, .audit-filters, .audit-copy, details.audit-details, .no-print')
        .forEach(el => el.remove());

    // اطبع فقط العناصر الظاهرة (بعد الفلترة/البحث)
    clone.querySelectorAll('.audit-item').forEach(it => {
        if (it.style.display === 'none') it.remove();
    });

    // ✅ بيانات الهيدر
    const teacherName = (window.__PRINT_TEACHER_NAME__ || '').trim();
    const adminName   = (window.__PRINT_ADMIN_NAME__ || '').trim();

    const dt = new Intl.DateTimeFormat('ar-EG', {
        dateStyle: 'full',
        timeStyle: 'short',
        timeZone: 'Asia/Dubai'
    }).format(new Date());

    const headerHtml = `
        <div class="print-header">
            <div class="ph-title">سجل القرارات والتغييرات (Logs)</div>
            <div class="ph-meta">
                <div><strong>اسم المعلّم:</strong> ${teacherName || '—'}</div>
                <div><strong>تاريخ الطباعة:</strong> ${dt}</div>
                ${adminName ? `<div><strong>اسم الأدمن:</strong> ${adminName}</div>` : ''}
            </div>
        </div>
    `;

    // انسخ روابط/ستايلات الهيد من الصفحة الأصلية عشان نفس التصميم
    const headHtml = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
        .map(el => el.outerHTML).join("\n");

    const extraPrintCss = `
        <style>
            body { direction: rtl; margin: 18px; }
            .doc-box { border: none !important; box-shadow: none !important; }
            .audit-list { max-height: none !important; overflow: visible !important; }
            .audit-item { page-break-inside: avoid; break-inside: avoid; }

            .print-header{
                border:1px solid rgba(0,0,0,.12);
                border-radius:14px;
                padding:12px 14px;
                margin-bottom:14px;
                background:#fff;
            }
            .ph-title{
                font-weight:900;
                font-size:1.05rem;
                margin-bottom:6px;
            }
            .ph-meta{
                color:#495057;
                font-size:.92rem;
                display:flex;
                flex-direction:column;
                gap:4px;
            }
            @page { margin: 12mm; }
        </style>
    `;

    const w = window.open('', '_blank', 'width=900,height=700');
    if (!w) {
        alert('من فضلك اسمح بفتح Popups للطباعة.');
        return;
    }

    w.document.open();
    w.document.write(`
        <html lang="ar" dir="rtl">
        <head>
            <meta charset="utf-8">
            <title>Teacher Audit Logs</title>
            ${headHtml}
            ${extraPrintCss}
        </head>
        <body>
            ${headerHtml}
            ${clone.outerHTML}
            <script>
                window.onload = function(){
                    window.focus();
                    window.print();
                    setTimeout(function(){ window.close(); }, 250);
                }
            <\/script>
        </body>
        </html>
    `);
    w.document.close();
    }


    btnPrint?.addEventListener('click', () => printAuditOnlyNewWindow());

    // ✅ Export CSV (للظاهر فقط) + UTF-8 BOM للعربي في Excel
    btnExport?.addEventListener('click', () => {
        const visible = items().filter(it => it.style.display !== 'none');

        const rows = [];
        rows.push(['who','time','status','note'].join(','));

        visible.forEach(it => {
            const who = (it.querySelector('.audit-who')?.textContent || '').trim();
            const tm  = (it.querySelector('.audit-when')?.textContent || '').trim();
            const st  = (it.querySelector('.status-chip')?.textContent || '').replace(/\s+/g,' ').trim();
            const note= (it.querySelector('.audit-note')?.innerText || '').replace(/\s+/g,' ').trim();

            const esc = (s) => '"' + String(s || '').replace(/"/g,'""') + '"';
            rows.push([esc(who), esc(tm), esc(st), esc(note)].join(','));
        });

        const csv = '\ufeff' + rows.join('\r\n');
        const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'teacher_audit_logs.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        showToast('تم التصدير ✅');
    });

    // Copy buttons inside details (لو فضلت موجودة في UI)
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.audit-copy');
        if (!btn) return;

        const box = btn.closest('.box');
        const target = box?.querySelector('pre');
        const text = target?.innerText || '';
        if (!text) return;

        try {
            await navigator.clipboard.writeText(text);
            showToast('تم النسخ ✅');
        } catch {
            const t = document.createElement('textarea');
            t.value = text;
            document.body.appendChild(t);
            t.select();
            document.execCommand('copy');
            t.remove();
            showToast('تم النسخ ✅');
        }
    });

    updateCounts();
    apply();

})();
</script>
@endpush
