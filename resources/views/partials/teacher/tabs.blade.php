@php
    // ==============================
    // Teacher Tabs (Shared)
    // ==============================

    // ✅ Inputs (تقبل اللي جاي من الصفحة أو تستنتجه)
    $profile = $profile ?? ($teacher->teacherProfile ?? null);

    $canOnline = (bool)($canOnline ?? false);
    $canOnsite = (bool)($canOnsite ?? false);

    $onlineHalf = $onlineHalf ?? null;
    $onlineHour = $onlineHour ?? null;
    $onsiteHalf = $onsiteHalf ?? null;
    $onsiteHour = $onsiteHour ?? null;

    $currency = $currency ?? 'AED';

    $teachingStyle = $teachingStyle ?? ($profile->teaching_style ?? null);
    $availability  = $availability  ?? ($profile->availability ?? null);
    $cancelPolicy  = $cancelPolicy  ?? ($profile->cancel_policy ?? null);

    // ✅ تنظيف النصوص (عشان لو فيها مسافات بس)
    if (is_string($teachingStyle)) { $teachingStyle = trim($teachingStyle); if ($teachingStyle === '') $teachingStyle = null; }
    if (is_string($availability))  { $availability  = trim($availability);  if ($availability  === '') $availability  = null; }
    if (is_string($cancelPolicy))  { $cancelPolicy  = trim($cancelPolicy);  if ($cancelPolicy  === '') $cancelPolicy  = null; }

    $rating       = isset($rating) ? (float)$rating : (float)($teacher->average_rating ?? 0);
    $ratingsCount = isset($ratingsCount) ? (int)$ratingsCount : (int)($teacher->ratings_count ?? 0);

    // ✅ مهم لتفادي تعارض IDs بين صفحات مختلفة أو تكرار الـ partial
    // استخدمه كده: 'idPrefix' => 'student' أو 'admin'
    $idPrefix = $idPrefix ?? 'teacher';
    $tid = $idPrefix . '-tabs-' . ($teacher->id ?? '0');

    // ✅ Admin Mode (افتراضي false)
    $isAdminView = (bool)($isAdminView ?? false);

    // IDs
    $tabsId       = $tid;
    $paneAbout    = $tid.'-pane-about';
    $panePrices   = $tid.'-pane-prices';
    $paneStyle    = $tid.'-pane-style';
    $paneAvail    = $tid.'-pane-availability';
    $panePolicy   = $tid.'-pane-policy';
    $paneReviews  = $tid.'-pane-reviews';
    $paneAdmin    = $tid.'-pane-admin-review';

    $tabAbout     = $tid.'-tab-about';
    $tabPrices    = $tid.'-tab-prices';
    $tabStyle     = $tid.'-tab-style';
    $tabAvail     = $tid.'-tab-availability';
    $tabPolicy    = $tid.'-tab-policy';
    $tabReviews   = $tid.'-tab-reviews';
    $tabAdmin     = $tid.'-tab-admin-review';

    // ==============================
    // Admin: Contact + docs (optional inputs)
    // ==============================
    $adminContact = $adminContact ?? [];

    $teacherName = $adminContact['name'] ?? ($teacher->name ?? '-');
    $email       = $adminContact['email'] ?? ($teacher->email ?? null);
    $phone       = $adminContact['phone'] ?? null;
    $whatsapp    = $adminContact['whatsapp'] ?? null;
    $country     = $adminContact['country'] ?? ($profile->country ?? ($teacher->country ?? '-'));
    $city        = $adminContact['city'] ?? ($profile->city ?? ($teacher->city ?? null));

    // status + reasons (from profile meta accessors)
    $status = $adminContact['status']
        ?? ($profile->account_status ?? ($teacher->teacher_status ?? 'pending'));

    $rejectionReason = $adminContact['rejection_reason'] ?? ($profile->rejection_reason ?? null);
    $adminNote       = $adminContact['admin_note'] ?? ($profile->admin_note ?? null);

    $statusBadge = 'secondary';
    $statusText  = 'غير محدّد';
    if ($status === 'approved') { $statusBadge='success'; $statusText='مقبول'; }
    elseif ($status === 'pending') { $statusBadge='warning'; $statusText='قيد المراجعة'; }
    elseif ($status === 'rejected') { $statusBadge='danger'; $statusText='مرفوض'; }

    // file urls
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

    $idDocUrl    = $adminContact['id_doc_url'] ?? $fileUrl($profile->id_document_path ?? null);
    $permitUrl   = $adminContact['permit_url'] ?? $fileUrl($profile->teaching_permit_path ?? null);

@endphp

<style>
  /* ✅ خليها خارج الـ ul */
  #{{ $tabsId }}-tabs { flex-wrap: nowrap !important; overflow-x: auto; }
  #{{ $tabsId }}-tabs .nav-link { white-space: nowrap; border-radius: 12px; }
</style>

<ul class="nav nav-pills mb-3 justify-content-end flex-wrap gap-2"
    id="{{ $tabsId }}-tabs" role="tablist" dir="rtl">

    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="{{ $tabAbout }}" data-bs-toggle="pill" data-bs-target="#{{ $paneAbout }}" type="button" role="tab">
            نبذة
        </button>
    </li>

    <li class="nav-item" role="presentation">
        <button class="nav-link" id="{{ $tabPrices }}" data-bs-toggle="pill" data-bs-target="#{{ $panePrices }}" type="button" role="tab">
            الأسعار
        </button>
    </li>

    <li class="nav-item" role="presentation">
        <button class="nav-link" id="{{ $tabStyle }}" data-bs-toggle="pill" data-bs-target="#{{ $paneStyle }}" type="button" role="tab">
            أسلوب التدريس
        </button>
    </li>

    <li class="nav-item" role="presentation">
        <button class="nav-link" id="{{ $tabAvail }}" data-bs-toggle="pill" data-bs-target="#{{ $paneAvail }}" type="button" role="tab">
            التوفر
        </button>
    </li>

    <li class="nav-item" role="presentation">
        <button class="nav-link" id="{{ $tabPolicy }}" data-bs-toggle="pill" data-bs-target="#{{ $panePolicy }}" type="button" role="tab">
            السياسات
        </button>
    </li>

    <li class="nav-item" role="presentation">
        <button class="nav-link" id="{{ $tabReviews }}" data-bs-toggle="pill" data-bs-target="#{{ $paneReviews }}" type="button" role="tab">
            التقييمات
        </button>
    </li>

    {{-- ✅ تبويب الأدمن (يظهر فقط في صفحة الأدمن) --}}
    @if($isAdminView)
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="{{ $tabAdmin }}" data-bs-toggle="pill" data-bs-target="#{{ $paneAdmin }}" type="button" role="tab">
                مراجعة الأدمن
            </button>
        </li>
    @endif
</ul>

<div class="tab-content" id="{{ $tabsId }}-content">

    {{-- نبذة --}}
    <div class="tab-pane fade show active" id="{{ $paneAbout }}" role="tabpanel" aria-labelledby="{{ $tabAbout }}">
        <div class="card mb-3" dir="rtl">
            <div class="card-header text-end">نبذة عن المعلّم</div>
            <div class="card-body text-end">
                @if($profile && $profile->bio)
                    <p class="mb-0">{{ $profile->bio }}</p>
                @else
                    <p class="text-muted mb-0">لم يتم إضافة نبذة بعد.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- الأسعار --}}
    <div class="tab-pane fade" id="{{ $panePrices }}" role="tabpanel" aria-labelledby="{{ $tabPrices }}">
        <div class="card mb-3" dir="rtl">
            <div class="card-header text-end">الأسعار</div>
            <div class="card-body text-end">

                @if(!$canOnline && !$canOnsite)
                    <p class="text-muted mb-0">لم يتم إدخال بيانات الأسعار لهذا المعلّم بعد.</p>
                @else
                    <div class="row">
                        @if($canOnline)
                            <div class="col-md-6 mb-3">
                                <h6 class="fw-bold">أونلاين</h6>
                                <p class="mb-1"><strong>نصف ساعة:</strong> {{ !is_null($onlineHalf) ? $onlineHalf.' '.$currency : 'لم تُحدّد' }}</p>
                                <p class="mb-0"><strong>ساعة كاملة:</strong> {{ !is_null($onlineHour) ? $onlineHour.' '.$currency : 'لم تُحدّد' }}</p>
                            </div>
                        @endif

                        @if($canOnsite)
                            <div class="col-md-6 mb-3">
                                <h6 class="fw-bold">حضوري</h6>
                                <p class="mb-1"><strong>نصف ساعة:</strong> {{ !is_null($onsiteHalf) ? $onsiteHalf.' '.$currency : 'لم تُحدّد' }}</p>
                                <p class="mb-0"><strong>ساعة كاملة:</strong> {{ !is_null($onsiteHour) ? $onsiteHour.' '.$currency : 'لم تُحدّد' }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                <hr>

                <h6 class="fw-bold">حصة تجريبية</h6>
                @if($teacher->offers_trial)
                    <p class="mb-1">
                        متاحة لمدة {{ $teacher->trial_duration_minutes ?? 30 }} دقيقة
                        بسعر {{ $teacher->trial_price ?? 0 }} {{ $currency }}
                        @if(($teacher->trial_price ?? 0) == 0) (مجانية) @endif
                    </p>
                @else
                    <p class="text-muted mb-0">لا توجد حصة تجريبية مفعّلة حاليًا.</p>
                @endif

            </div>
        </div>
    </div>

    {{-- ✅ أسلوب التدريس --}}
    <div class="tab-pane fade" id="{{ $paneStyle }}" role="tabpanel" aria-labelledby="{{ $tabStyle }}">
        <div class="card mb-3" dir="rtl">
            <div class="card-header text-end">أسلوب التدريس</div>
            <div class="card-body text-end">

                @php
                    $fallbackStyle = null;
                    if ($canOnline && $canOnsite) $fallbackStyle = 'يقدّم حصص أونلاين وحضوري.';
                    elseif ($canOnline) $fallbackStyle = 'يقدّم حصص أونلاين.';
                    elseif ($canOnsite) $fallbackStyle = 'يقدّم حصص حضوري.';
                @endphp

                @if(!empty($teachingStyle))
                    <p class="mb-0">{{ $teachingStyle }}</p>
                @elseif($fallbackStyle)
                    <p class="mb-0">{{ $fallbackStyle }}</p>
                    <div class="text-muted small mt-2">* يمكن للمعلم إضافة تفاصيل أكثر داخل ملفه لاحقًا.</div>
                @else
                    <p class="text-muted mb-0">لم يتم إضافة أسلوب التدريس بعد.</p>
                @endif

            </div>
        </div>
    </div>

    {{-- التوفر --}}
    <div class="tab-pane fade" id="{{ $paneAvail }}" role="tabpanel" aria-labelledby="{{ $tabAvail }}">
        <div class="card mb-3" dir="rtl">
            <div class="card-header text-end">التوفر والجدول</div>
            <div class="card-body text-end">

                @php
                    // 1) Decoding availability
                    $avRaw = $availability ?? null;
                    if (is_string($avRaw) && $avRaw !== '') {
                        $decoded = json_decode($avRaw, true);
                        $availabilityArr = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($avRaw)) {
                        $availabilityArr = $avRaw;
                    } else {
                        $availabilityArr = [];
                    }

                    // 2) ترتيب الأيام
                    $daysOrder = [
                        'sat' => 'السبت',
                        'sun' => 'الأحد',
                        'mon' => 'الاثنين',
                        'tue' => 'الثلاثاء',
                        'wed' => 'الأربعاء',
                        'thu' => 'الخميس',
                        'fri' => 'الجمعة',
                    ];

                    // 3) Helper لقراءة الحالة/الأوقات مع اختلاف المفاتيح
                    $getEnabled = function($d) {
                        return (bool)($d['enabled'] ?? $d['is_available'] ?? $d['available'] ?? false);
                    };
                    $getFrom = function($d) {
                        return $d['from'] ?? $d['start'] ?? $d['from_time'] ?? null;
                    };
                    $getTo = function($d) {
                        return $d['to'] ?? $d['end'] ?? $d['to_time'] ?? null;
                    };
                @endphp

                @if(empty($availabilityArr))
                    <p class="text-muted mb-0">لم يتم تحديد التوفر بعد.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-end">اليوم</th>
                                    <th class="text-end">الحالة</th>
                                    <th class="text-end">من</th>
                                    <th class="text-end">إلى</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($daysOrder as $key => $label)
                                    @php
                                        $day = $availabilityArr[$key]
                                            ?? $availabilityArr[strtoupper($key)] ?? $availabilityArr[ucfirst($key)]
                                            ?? $availabilityArr[$label]
                                            ?? null;

                                        $enabled = is_array($day) ? $getEnabled($day) : false;
                                        $from = is_array($day) ? $getFrom($day) : null;
                                        $to   = is_array($day) ? $getTo($day) : null;
                                    @endphp

                                    <tr>
                                        <td class="text-end fw-semibold">{{ $label }}</td>
                                        <td class="text-end">
                                            @if($enabled)
                                                <span class="badge bg-success">متاح</span>
                                            @else
                                                <span class="badge bg-secondary">غير متاح</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $enabled ? ($from ?? '-') : '-' }}</td>
                                        <td class="text-end">{{ $enabled ? ($to ?? '-') : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="text-muted small mt-2">
                        *اختر نوع الحصة ثم التاريخ لعرض الأوقات المتاحة.
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- السياسات --}}
    <div class="tab-pane fade" id="{{ $panePolicy }}" role="tabpanel" aria-labelledby="{{ $tabPolicy }}">
        <div class="card mb-3" dir="rtl">
            <div class="card-header text-end">سياسة الإلغاء وإعادة الجدولة</div>
            <div class="card-body text-end">
                @if(!empty($cancelPolicy))
                    <p class="mb-0">{{ $cancelPolicy }}</p>
                @else
                    <p class="text-muted mb-0">لم يتم تحديد السياسات بعد.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- التقييمات --}}
    <div class="tab-pane fade" id="{{ $paneReviews }}" role="tabpanel" aria-labelledby="{{ $tabReviews }}">
        <div class="card mb-3" dir="rtl">
            <div class="card-header text-end">تقييمات الطلاب</div>
            <div class="card-body text-end">
                <p class="mb-2">
                    <strong>المتوسط:</strong> {{ number_format($rating, 1) }}
                    <span class="text-muted">({{ $ratingsCount }} تقييم)</span>
                </p>
                <div class="text-muted">قريبًا: سيتم عرض أحدث المراجعات النصية للطلاب داخل هذه الصفحة.</div>
            </div>
        </div>
    </div>

    {{-- ✅ تبويب مراجعة الأدمن --}}
    @if($isAdminView)
        <div class="tab-pane fade" id="{{ $paneAdmin }}" role="tabpanel" aria-labelledby="{{ $tabAdmin }}">
            <div class="card mb-3" dir="rtl">
                <div class="card-header text-end d-flex justify-content-between align-items-center">
                    <span>مراجعة الأدمن</span>
                    <span class="badge bg-{{ $statusBadge }}">الحالة: {{ $statusText }}</span>
                </div>
                <div class="card-body text-end">

                    @if($status === 'rejected' && !empty($rejectionReason))
                        <div class="alert alert-danger text-end">
                            <div class="fw-bold mb-1">سبب الرفض:</div>
                            <div>{{ $rejectionReason }}</div>
                        </div>
                    @endif

                    @if(!empty($adminNote))
                        <div class="alert alert-secondary text-end">
                            <div class="fw-bold mb-1">ملاحظة أدمن:</div>
                            <div>{{ $adminNote }}</div>
                        </div>
                    @endif

                    <h6 class="fw-bold mb-2">بيانات التواصل</h6>
                    <div class="row g-2">
                        <div class="col-md-6"><strong>الاسم:</strong> {{ $teacherName }}</div>
                        <div class="col-md-6">
                            <strong>الإيميل:</strong>
                            @if($email)
                                <a href="mailto:{{ $email }}">{{ $email }}</a>
                            @else
                                -
                            @endif
                        </div>
                        <div class="col-md-6"><strong>الهاتف:</strong> {{ $phone ?? '-' }}</div>
                        <div class="col-md-6">
                            <strong>واتساب:</strong>
                            @if($whatsapp)
                                {{ $whatsapp }}
                                <span class="text-muted small">—</span>
                                <a class="small" target="_blank" rel="noopener"
                                   href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}">
                                    فتح واتساب
                                </a>
                            @else
                                -
                            @endif
                        </div>
                        <div class="col-md-6"><strong>البلد:</strong> {{ $country ?? '-' }}</div>
                        <div class="col-md-6"><strong>المدينة:</strong> {{ $city ?? '-' }}</div>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-2">مستندات التحقق</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @if($idDocUrl)
                            <a href="{{ $idDocUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">
                                فتح الهوية / الجواز
                            </a>
                        @else
                            <span class="badge bg-secondary">لا توجد هوية مرفوعة</span>
                        @endif

                        @if($permitUrl)
                            <a href="{{ $permitUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                فتح تصريح التدريس
                            </a>
                        @else
                            <span class="badge bg-secondary">لا يوجد تصريح تدريس</span>
                        @endif
                    </div>

                    <div class="text-muted small mt-2">
                        * هذا التبويب للعرض فقط. إجراءات القبول/الرفض موجودة أعلى صفحة الأدمن.
                    </div>

                </div>
            </div>
        </div>
    @endif

</div>
