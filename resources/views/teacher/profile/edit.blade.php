@extends('layouts.teacher')

@section('page_title', 'تعديل ملفي')

@section('content')
<style>
    .required-star{
        color:#dc3545;
        font-weight:900;
        margin-inline-start: 4px;
    }

    /* ✅ Equal label height so inputs align perfectly */
    .price-label{
        min-height: 48px;
        display:flex;
        align-items:flex-start;
        gap:6px;
        line-height:1.25;
        margin-bottom: 8px;
    }
    .price-label .req{
        color:#dc3545;
        font-weight:800;
        line-height:1;
        margin-top: 2px;
        flex: 0 0 auto;
    }

    /* ✅ Prices spacing */
    .online-prices, .onsite-prices{
        margin-top: 14px;
    }

    /* ✅ Cities search icon inside the box (no extra height) */
    .cities-box{
        position: relative;
        border-radius: 12px;
        padding: 10px;
        padding-top: 40px; /* مساحة للأيقونة + input بدون ما يأخذوا سطر فوق */
        border: 1px solid rgba(0,0,0,.15);
        background: #fff;
    }

    .cities-search-btn{
        position:absolute;
        top:8px;
        inset-inline-end:8px; /* RTL/LTR friendly */
        border-radius: 10px;
        padding: 2px 10px;
        line-height: 1.2;
        z-index: 2;
    }

    .cities-search-input{
        position:absolute;
        top:8px;
        inset-inline-start:8px;
        inset-inline-end:52px; /* قبل زر البحث */
        z-index: 2;
    }

    /* ✅ keep list scroll ثابت */
    #onsite_cities_container{
        max-height: 240px;
        overflow: auto;
        padding: 4px 4px;
    }

    /* ✅ Small polish for day table */
    .table td, .table th{ vertical-align: middle; }
</style>

<div class="container py-4">
@php
    $status = $user->teacher_status ?? 'pending';
    $badgeClass = 'secondary';
    $statusText = 'غير محدّد';

    if ($status === 'approved') {
        $badgeClass = 'success';
        $statusText = 'حسابك مفعَّل ويمكن للطلاب رؤيتك في البحث.';
    } elseif ($status === 'pending') {
        $badgeClass = 'warning';
        $statusText = 'حسابك قيد المراجعة من إدارة المنصّة، ولن يظهر للطلاب حتى يتم التفعيل.';
    } elseif ($status === 'rejected') {
        $badgeClass = 'danger';
        $statusText = 'تم رفض حسابك من إدارة المنصّة. برجاء التواصل مع الدعم لمزيد من التفاصيل.';
    }

    // ✅ قائمة المناهج
    $allCurricula = ['UAE', 'British', 'American', 'IB', 'Other'];

    /**
     * ✅✅ إصلاح مهم:
     * المناهج والصفوف مخزنة في teacher_profiles وليس users
     */
    if (old('curricula')) {
        $selectedCurricula = old('curricula', []);
    } elseif (!empty($profile->curricula) && is_array($profile->curricula)) {
        $selectedCurricula = $profile->curricula;
    } else {
        $selectedCurricula = [];
    }

    // دول ومدن
    $countries = $countries ?? collect([]);
    $cities = $cities ?? collect([]);

    $isAr = app()->getLocale() === 'ar';

    $selectedCountryId = old('country_id', $profile->country_id ?? ($uaeDefaultCountryId ?? null));
    $selectedOnsiteCityIds = old('onsite_city_ids', $selectedOnsiteCityIds ?? []);

    // إضافات
    $subjectsValue = old('subjects', $profile->subjects ?? '');

    $languagesRaw = old('languages', $profile->languages ?? '');
    $selectedLanguages = [];
    if (is_array($languagesRaw)) {
        $selectedLanguages = $languagesRaw;
    } else {
        $selectedLanguages = array_filter(array_map('trim', explode(',', (string)$languagesRaw)));
    }

    $otherLanguage = old('other_language', '');
    $teachingStyleValue = old('teaching_style', $profile->teaching_style ?? '');
    $cancelPolicyValue  = old('cancel_policy',  $profile->cancel_policy ?? '');

    // ✅ بيانات تواصل/سوشيال (جديدة)
    $phoneMobileVal   = old('phone_mobile',    $profile->phone_mobile ?? '');
    $whatsappVal      = old('whatsapp_number', $profile->whatsapp_number ?? '');
    $addressDetails   = old('address_details', $profile->address_details ?? '');

    $websiteUrl       = old('website_url',   $profile->website_url ?? '');
    $facebookUrl      = old('facebook_url',  $profile->facebook_url ?? '');
    $instagramUrl     = old('instagram_url', $profile->instagram_url ?? '');
    $tiktokUrl        = old('tiktok_url',    $profile->tiktok_url ?? '');
    $youtubeUrl       = old('youtube_url',   $profile->youtube_url ?? '');
    $linkedinUrl      = old('linkedin_url',  $profile->linkedin_url ?? '');

    // فاصل الراحة (سيظهر تحت جدول التوفر)
    $breakMinutesValue = old('break_minutes', $profile->break_minutes ?? 0);
    $breakMinutesValue = is_numeric($breakMinutesValue) ? (int)$breakMinutesValue : 0;
    if ($breakMinutesValue < 0) $breakMinutesValue = 0;
    if ($breakMinutesValue > 180) $breakMinutesValue = 180;

    // availability JSON
    $availabilityRaw = old('availability', $profile->availability ?? null);
    if (is_string($availabilityRaw)) {
        $availability = json_decode($availabilityRaw, true);
    } elseif (is_array($availabilityRaw)) {
        $availability = $availabilityRaw;
    } else {
        $availability = json_decode((string)($profile->availability ?? ''), true);
    }
    if (!is_array($availability)) $availability = [];

    $days = [
        'sat' => 'السبت',
        'sun' => 'الأحد',
        'mon' => 'الاثنين',
        'tue' => 'الثلاثاء',
        'wed' => 'الأربعاء',
        'thu' => 'الخميس',
        'fri' => 'الجمعة',
    ];

    $languageOptions = [
        'العربية',
        'English',
        'Urdu',
        'Hindi',
        'French',
    ];

    // ✅ المرحلة: مسودة / تم الإرسال
    $submittedAt = $profile->submitted_at ?? null;
    $hasSubmitted = !empty($submittedAt);
    $stageLabel = $hasSubmitted ? 'تم الإرسال' : 'مسودة';
    $stageClass = $hasSubmitted ? 'success' : 'secondary';

    // ✅✅ زر الإرسال يتعطّل فقط لو "تم الإرسال" و"الحالة pending"
    $isPendingReview = ($hasSubmitted && $status === 'pending');

    // طرق التدريس
    $hasOnline = old('teaches_online', $profile->teaches_online);
    $hasOnsite = old('teaches_onsite', $profile->teaches_onsite);
@endphp

    <div class="alert alert-{{ $badgeClass }} d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <strong>حالة الحساب: </strong>
            <span class="badge bg-{{ $badgeClass }} ms-1">{{ strtoupper($status) }}</span>
            <span class="ms-2">{{ $statusText }}</span>
        </div>

        <div>
            <strong>المرحلة:</strong>
            <span class="badge bg-{{ $stageClass }}">{{ $stageLabel }}</span>
        </div>
    </div>

    {{-- حالة الإرسال --}}
    @if($hasSubmitted)
        <div class="alert alert-info">
            ✅ تم إرسال ملفك للمراجعة
            @if($submittedAt)
                <span class="text-muted">
                    ({{ \Carbon\Carbon::parse($submittedAt)->format('Y-m-d H:i') }})
                </span>
            @endif

            @if($isPendingReview)
                <div class="text-muted small mt-1">
                    ملفك قيد المراجعة الآن — لا يمكنك إعادة الإرسال حتى يتم اتخاذ قرار من الأدمن.
                </div>
            @else
                <div class="text-muted small mt-1">
                    يمكنك تعديل ملفك وإعادة إرساله للمراجعة مرة أخرى عند الحاجة.
                </div>
            @endif
        </div>
    @endif

    <div class="text-muted small mb-3">
        <span class="required-star">*</span> حقول إلزامية (بعضها مطلوب عند الإرسال للمراجعة فقط).
    </div>

    <h2 class="mb-3">تعديل بياناتي</h2>

    {{-- أخطاء --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>برجاء مراجعة الأخطاء التالية:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- نجاح --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST"
          action="{{ route('teacher.profile.update') }}"
          enctype="multipart/form-data"
          id="teacherProfileForm">
        @csrf
        @method('PUT')

        {{-- زر الإرسال للمراجعة --}}
        <input type="hidden" name="submit_for_review" id="submit_for_review" value="0">

        {{-- hidden (languages + availability) --}}
        <input type="hidden" name="languages" id="languages_hidden" value="{{ is_array($languagesRaw) ? implode(', ', $languagesRaw) : $languagesRaw }}">
        <input type="hidden" name="availability" id="availability_hidden" value="{{ is_string($availabilityRaw) ? $availabilityRaw : json_encode($availability) }}">

        <div class="row g-4">

            {{-- العمود الأيسر --}}
            <div class="col-md-8">

                {{-- بيانات الحساب --}}
                <div class="card mb-3">
                    <div class="card-header">بيانات الحساب</div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">
                                الاسم الكامل <span class="required-star">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}"
                                   required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                البريد الإلكتروني <span class="required-star">*</span>
                            </label>
                            <input type="email"
                                   name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}"
                                   required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الجديدة (اختياري)</label>
                            <input type="password"
                                   name="password"
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">اترك الحقل فارغًا إن لم ترغب في تغيير كلمة المرور.</small>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>

                    </div>
                </div>

                {{-- ✅✅ بيانات التواصل (جديدة) --}}
                <div class="card mb-3" id="contact-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>بيانات التواصل (تظهر للأدمن)</span>
                        <span class="badge bg-light text-dark">اختياري عند الحفظ</span>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light border mb-3 small">
                            💡 عند <strong>الإرسال للمراجعة</strong> يجب إدخال <strong>رقم هاتف/موبايل أو رقم واتساب</strong> واحد على الأقل.
                            <div class="text-muted mt-1">يُفضّل كتابة الرقم بصيغة دولية مثل: <strong>+971...</strong></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    رقم الهاتف / الموبايل <span class="required-star">*</span>
                                </label>
                                <input type="text"
                                       name="phone_mobile"
                                       id="phone_mobile"
                                       class="form-control @error('phone_mobile') is-invalid @enderror"
                                       value="{{ $phoneMobileVal }}"
                                       placeholder="+971 50 123 4567"
                                       inputmode="tel"
                                       autocomplete="tel">
                                @error('phone_mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    رقم واتساب <span class="required-star">*</span>
                                </label>
                                <input type="text"
                                       name="whatsapp_number"
                                       id="whatsapp_number"
                                       class="form-control @error('whatsapp_number') is-invalid @enderror"
                                       value="{{ $whatsappVal }}"
                                       placeholder="+971 50 123 4567"
                                       inputmode="tel"
                                       autocomplete="tel">
                                @error('whatsapp_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">العنوان التفصيلي (اختياري)</label>
                            <textarea name="address_details"
                                      id="address_details"
                                      rows="3"
                                      class="form-control @error('address_details') is-invalid @enderror"
                                      placeholder="مثال: الإمارات - دبي - المنطقة - أقرب معلم...">{{ $addressDetails }}</textarea>
                            @error('address_details') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <hr class="my-3">

                        <div class="mb-2 fw-bold">روابط التواصل (اختياري)</div>
                        <div class="text-muted small mb-2">ابدأ الرابط بـ <strong>https://</strong> لضمان قبوله.</div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">الموقع الإلكتروني</label>
                                <input type="url"
                                       name="website_url"
                                       class="form-control @error('website_url') is-invalid @enderror"
                                       value="{{ $websiteUrl }}"
                                       placeholder="https://example.com">
                                @error('website_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">فيسبوك</label>
                                <input type="url"
                                       name="facebook_url"
                                       class="form-control @error('facebook_url') is-invalid @enderror"
                                       value="{{ $facebookUrl }}"
                                       placeholder="https://facebook.com/username">
                                @error('facebook_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">إنستغرام</label>
                                <input type="url"
                                       name="instagram_url"
                                       class="form-control @error('instagram_url') is-invalid @enderror"
                                       value="{{ $instagramUrl }}"
                                       placeholder="https://instagram.com/username">
                                @error('instagram_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">تيك توك</label>
                                <input type="url"
                                       name="tiktok_url"
                                       class="form-control @error('tiktok_url') is-invalid @enderror"
                                       value="{{ $tiktokUrl }}"
                                       placeholder="https://tiktok.com/@username">
                                @error('tiktok_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">يوتيوب</label>
                                <input type="url"
                                       name="youtube_url"
                                       class="form-control @error('youtube_url') is-invalid @enderror"
                                       value="{{ $youtubeUrl }}"
                                       placeholder="https://youtube.com/@channel">
                                @error('youtube_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">لينكدإن</label>
                                <input type="url"
                                       name="linkedin_url"
                                       class="form-control @error('linkedin_url') is-invalid @enderror"
                                       value="{{ $linkedinUrl }}"
                                       placeholder="https://linkedin.com/in/username">
                                @error('linkedin_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                    </div>
                </div>

                {{-- بيانات عامة --}}
                <div class="card mb-3">
                    <div class="card-header">بيانات عامة</div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">عنوان قصير (يظهر للطلاب)</label>
                            <input type="text"
                                   name="headline"
                                   class="form-control @error('headline') is-invalid @enderror"
                                   value="{{ old('headline', $profile->headline) }}">
                            @error('headline') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">نبذة عن المعلّم</label>
                            <textarea name="bio" rows="4"
                                      class="form-control @error('bio') is-invalid @enderror">{{ old('bio', $profile->bio) }}</textarea>
                            @error('bio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">المادة الرئيسية</label>
                                <input type="text"
                                       name="main_subject"
                                       class="form-control @error('main_subject') is-invalid @enderror"
                                       value="{{ old('main_subject', $profile->main_subject) }}">
                                @error('main_subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">سنوات الخبرة</label>
                                <input type="number"
                                       name="experience_years"
                                       class="form-control @error('experience_years') is-invalid @enderror"
                                       value="{{ old('experience_years', $profile->experience_years) }}">
                                @error('experience_years') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- الصفوف والمناهج --}}
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="form-label">أقل صف يدرّسه المعلّم</label>
                                <select name="min_grade" class="form-select @error('min_grade') is-invalid @enderror">
                                    <option value="">لم تُحدّد</option>
                                    @for($i=1;$i<=12;$i++)
                                        <option value="{{ $i }}"
                                            {{ (string)old('min_grade', $profile->min_grade) === (string)$i ? 'selected' : '' }}>
                                            الصف {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                                @error('min_grade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">أعلى صف يدرّسه المعلّم</label>
                                <select name="max_grade" class="form-select @error('max_grade') is-invalid @enderror">
                                    <option value="">لم تُحدّد</option>
                                    @for($i=1;$i<=12;$i++)
                                        <option value="{{ $i }}"
                                            {{ (string)old('max_grade', $profile->max_grade) === (string)$i ? 'selected' : '' }}>
                                            الصف {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                                @error('max_grade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">المناهج التي يدرّسها المعلّم</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($allCurricula as $curr)
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="curricula[]"
                                               id="curr_{{ $curr }}"
                                               value="{{ $curr }}"
                                               {{ in_array($curr, $selectedCurricula) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="curr_{{ $curr }}">{{ $curr }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('curricula') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                    </div>
                </div>

                {{-- معلومات إضافية --}}
                <div class="card mb-3">
                    <div class="card-header">معلومات إضافية (لزيادة ظهورك للطلاب)</div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">مواد إضافية تدرّسها (اكتب كل مادة في سطر)</label>
                            <textarea name="subjects" rows="3"
                                      class="form-control @error('subjects') is-invalid @enderror"
                                      placeholder="مثال: رياضيات&#10;علوم&#10;لغة عربية">{{ $subjectsValue }}</textarea>
                            @error('subjects') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">تظهر هذه المواد للطالب داخل صفحة تفاصيل المعلّم وتساعدك في البحث.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">لغات الشرح</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($languageOptions as $lang)
                                    <div class="form-check">
                                        <input class="form-check-input language-check"
                                               type="checkbox"
                                               id="lang_{{ md5($lang) }}"
                                               value="{{ $lang }}"
                                               {{ in_array($lang, $selectedLanguages) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="lang_{{ md5($lang) }}">{{ $lang }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-2">
                                <label class="form-label small mb-1">لغة أخرى (اختياري)</label>
                                <input type="text"
                                       id="other_language"
                                       name="other_language"
                                       class="form-control"
                                       value="{{ $otherLanguage }}"
                                       placeholder="مثال: Turkish">
                                <small class="text-muted">سيتم إضافتها تلقائيًا لقائمة اللغات عند الحفظ.</small>
                            </div>

                            @error('languages') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">أسلوب التدريس</label>
                            <textarea name="teaching_style" rows="3"
                                      class="form-control @error('teaching_style') is-invalid @enderror"
                                      placeholder="مثال: شرح مبسّط + أمثلة كثيرة + واجبات قصيرة + متابعة أسبوعية">{{ $teachingStyleValue }}</textarea>
                            @error('teaching_style') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">سياسة الإلغاء (مقترح)</label>
                            <textarea name="cancel_policy" rows="3"
                                      class="form-control @error('cancel_policy') is-invalid @enderror"
                                      placeholder="مثال: يمكن الإلغاء قبل موعد الحصة بـ 6 ساعات بدون رسوم.">{{ $cancelPolicyValue }}</textarea>
                            @error('cancel_policy') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">تظهر للطالب قبل الحجز لتقليل الإلغاءات.</small>
                        </div>

                    </div>
                </div>

                {{-- طرق التدريس والأسعار --}}
                <div class="card mb-3">
                    <div class="card-header">
                        طرق التدريس والأسعار <span class="required-star">*</span>
                    </div>
                    <div class="card-body">

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1"
                                           id="teaches_online" name="teaches_online"
                                           {{ $hasOnline ? 'checked' : '' }}>
                                    <label class="form-check-label" for="teaches_online">يقدّم حصص أونلاين</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1"
                                           id="teaches_onsite" name="teaches_onsite"
                                           {{ $hasOnsite ? 'checked' : '' }}>
                                    <label class="form-check-label" for="teaches_onsite">يقدّم حصص حضورية</label>
                                </div>
                            </div>
                        </div>

                        <div id="teaching_method_error"
                            class="text-danger small mb-2 {{ ($errors->has('teaches_online') || $errors->has('teaches_onsite')) ? '' : 'd-none' }}">
                            {{ $errors->first('teaches_online') ?? $errors->first('teaches_onsite') ?? 'يجب اختيار طريقة درس واحدة على الأقل (أونلاين أو حضوري).' }}
                        </div>


                        {{-- ✅ أسعار الأونلاين (مباشرة تحت اختيار أونلاين) --}}
                        <div class="row g-3 online-prices {{ $hasOnline ? '' : 'd-none' }}">
                            <div class="col-md-6">
                                <label class="form-label price-label">
                                    <span>أونلاين / ساعة</span>
                                    <span class="req">*</span>
                                </label>
                                <input type="number" step="0.01"
                                       name="hourly_rate_online"
                                       id="hourly_rate_online"
                                       class="form-control @error('hourly_rate_online') is-invalid @enderror"
                                       value="{{ old('hourly_rate_online', $profile->hourly_rate_online) }}">
                                @error('hourly_rate_online') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label price-label">
                                    <span>أونلاين / نصف ساعة</span>
                                    <span class="req">*</span>
                                </label>
                                <input type="number" step="0.01"
                                       name="half_hour_rate_online"
                                       id="half_hour_rate_online"
                                       class="form-control @error('half_hour_rate_online') is-invalid @enderror"
                                       value="{{ old('half_hour_rate_online', $profile->half_hour_rate_online) }}">
                                @error('half_hour_rate_online') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- ✅ أسعار الحضوري (مباشرة تحت اختيار حضوري) --}}
                        <div class="row g-3 onsite-prices {{ $hasOnsite ? '' : 'd-none' }}">
                            <div class="col-md-6">
                                <label class="form-label price-label">
                                    <span>حضوري / ساعة</span>
                                    <span class="req">*</span>
                                </label>
                                <input type="number" step="0.01"
                                       name="hourly_rate_onsite"
                                       id="hourly_rate_onsite"
                                       class="form-control @error('hourly_rate_onsite') is-invalid @enderror"
                                       value="{{ old('hourly_rate_onsite', $profile->hourly_rate_onsite) }}">
                                @error('hourly_rate_onsite') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label price-label">
                                    <span>حضوري / نصف ساعة</span>
                                    <span class="req">*</span>
                                </label>
                                <input type="number" step="0.01"
                                       name="half_hour_rate_onsite"
                                       id="half_hour_rate_onsite"
                                       class="form-control @error('half_hour_rate_onsite') is-invalid @enderror"
                                       value="{{ old('half_hour_rate_onsite', $profile->half_hour_rate_onsite) }}">
                                @error('half_hour_rate_onsite') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- ✅ مكان الحضور (بعد الأسعار) --}}
                        <div id="onsite-location" class="mt-3 {{ $hasOnsite ? '' : 'd-none' }}">
                            <hr>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">الدولة للحصص الحضورية <span class="text-danger">*</span></label>

                                    <select name="country_id"
                                            id="country_id"
                                            class="form-select @error('country_id') is-invalid @enderror"
                                            data-cities-url-template="{{ route('teacher.locations.cities', ['country' => '__ID__']) }}">
                                        <option value="">{{ $isAr ? 'اختر الدولة' : 'Select country' }}</option>

                                        @foreach($countries as $country)
                                            @php
                                                $nameAr = $country->name_ar ?: $country->name_en;
                                                $nameEn = $country->name_en ?: $country->name_ar;
                                            @endphp
                                            <option value="{{ $country->id }}"
                                                    {{ (string)$selectedCountryId === (string)$country->id ? 'selected' : '' }}>
                                                {{ $isAr ? $nameAr : $nameEn }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('country_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">{{ $isAr ? 'ستظهر المدن بعد اختيار الدولة.' : 'Cities will appear after selecting a country.' }}</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">المدن التي تدرّس فيها حضوريًا <span class="text-danger">*</span></label>

                                    <div class="cities-box" id="cities_box">

                                        <button type="button"
                                                class="btn btn-sm btn-light cities-search-btn"
                                                id="btnCitySearch"
                                                title="{{ $isAr ? 'بحث' : 'Search' }}">
                                            🔍
                                        </button>

                                        <input type="text"
                                               id="city_search"
                                               class="form-control form-control-sm cities-search-input d-none"
                                               placeholder="{{ $isAr ? 'ابحث عن مدينة...' : 'Search city...' }}">

                                        <div id="onsite_cities_container"
                                             data-selected='@json($selectedOnsiteCityIds)'>
                                            @forelse($cities as $city)
                                                @php $checked = in_array($city->id, $selectedOnsiteCityIds); @endphp
                                                <label class="d-flex align-items-center gap-2 py-1 mb-0 city-item"
                                                       data-ar="{{ $city->name_ar ?? '' }}"
                                                       data-en="{{ $city->name_en ?? '' }}">
                                                    <input type="checkbox"
                                                           name="onsite_city_ids[]"
                                                           value="{{ $city->id }}"
                                                           class="form-check-input m-0"
                                                           {{ $checked ? 'checked' : '' }}>
                                                    <span>
                                                        {{ $isAr ? ($city->name_ar ?: $city->name_en) : ($city->name_en ?: $city->name_ar) }}
                                                    </span>
                                                </label>
                                            @empty
                                                <div class="text-muted small">{{ $isAr ? 'اختر دولة لعرض المدن.' : 'Select a country to show cities.' }}</div>
                                            @endforelse
                                        </div>
                                    </div>

                                    <small class="text-muted d-block mt-1">{{ $isAr ? 'يمكنك اختيار أكثر من مدينة.' : 'You can select multiple cities.' }}</small>

                                    @error('onsite_city_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    @error('onsite_city_ids.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- جدول التوفر --}}
                <div class="card mb-3">
                    <div class="card-header">جدول التوفر (اختياري لكنه مهم للطالب)</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 140px;">اليوم</th>
                                        <th style="width: 120px;">متاح؟</th>
                                        <th>من</th>
                                        <th>إلى</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($days as $key => $label)
                                        @php
                                            $dayData = $availability[$key] ?? [];
                                            $enabled = (bool)($dayData['enabled'] ?? false);
                                            $from    = $dayData['from'] ?? '16:00';
                                            $to      = $dayData['to'] ?? '20:00';
                                        @endphp
                                        <tr data-day="{{ $key }}">
                                            <td class="fw-bold">{{ $label }}</td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input avail-enabled" type="checkbox"
                                                           id="avail_{{ $key }}" {{ $enabled ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="avail_{{ $key }}">نعم</label>
                                                </div>
                                            </td>
                                            <td><input type="time" class="form-control avail-from" value="{{ $from }}" {{ $enabled ? '' : 'disabled' }}></td>
                                            <td><input type="time" class="form-control avail-to" value="{{ $to }}" {{ $enabled ? '' : 'disabled' }}></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @error('availability') <div class="text-danger small mt-2">{{ $message }}</div> @enderror

                        <small class="text-muted d-block mt-2">
                            سيتم حفظ جدول التوفر تلقائيًا بصيغة مناسبة للعرض للطالب لاحقًا.
                        </small>

                        {{-- ✅ نقل فاصل الراحة هنا تحت جدول التوفر --}}
                        <hr class="my-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">فاصل الراحة بين الحصص (بالدقائق)</label>
                                <input type="number"
                                       name="break_minutes"
                                       id="break_minutes"
                                       min="0"
                                       max="180"
                                       step="1"
                                       class="form-control @error('break_minutes') is-invalid @enderror"
                                       value="{{ $breakMinutesValue }}"
                                       placeholder="مثال: 15">
                                @error('break_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">يضاف هذا الوقت بعد كل حصة. اتركه 0 لو لا تريد فاصل.</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- العمود الأيمن --}}
            <div class="col-md-4">

                {{-- صورة البروفايل --}}
                <div class="card mb-3">
                    <div class="card-header">صورة المعلّم <span class="text-danger">*</span></div>
                    <div class="card-body">

                        @if($profile->profile_photo_path)
                            <div class="mb-2 text-center">
                                <img src="{{ asset('storage/'.$profile->profile_photo_path) }}"
                                     alt="Teacher photo"
                                     class="img-thumbnail"
                                     style="max-height: 180px;">
                            </div>
                        @endif

                        <div class="mb-2">
                            <label class="form-label">اختيار صورة جديدة</label>
                            <input type="file"
                                   name="profile_photo"
                                   id="profile_photo"
                                   class="form-control @error('profile_photo') is-invalid @enderror"
                                   accept="image/jpeg,image/jpg,image/png,image/webp">
                            @error('profile_photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <small class="text-muted">الصيغ المسموحة: JPG, JPEG, PNG, WEBP – حد أقصى 2MB</small>
                        </div>

                    </div>
                </div>

                {{-- فيديو تعريفي --}}
                <div class="card mb-3">
                    <div class="card-header">فيديو تعريفي</div>
                    <div class="card-body">
                        @if($profile->intro_video_url)
                            <p class="mb-2">
                                <strong>الرابط الحالي:</strong><br>
                                <a href="{{ $profile->intro_video_url }}" target="_blank">{{ $profile->intro_video_url }}</a>
                            </p>
                        @endif

                        <label class="form-label">رابط فيديو (يوتيوب مثلاً)</label>
                        <input type="text"
                               name="intro_video_url"
                               class="form-control @error('intro_video_url') is-invalid @enderror"
                               value="{{ old('intro_video_url', $profile->intro_video_url) }}">
                        @error('intro_video_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- مستندات رسمية --}}
                <div class="card mb-3">
                    <div class="card-header">مستندات رسمية</div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">ملف الهوية (ID) <span class="text-danger">*</span></label>

                            @if($profile->id_document_path)
                                <p class="mb-1">
                                    <a href="{{ asset('storage/'.$profile->id_document_path) }}" target="_blank">عرض الملف الحالي</a>
                                </p>
                            @endif

                            <input type="file"
                                   name="id_document"
                                   id="id_document"
                                   class="form-control @error('id_document') is-invalid @enderror"
                                   accept="image/jpeg,image/jpg,image/png,image/webp,application/pdf">
                            @error('id_document') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <small class="text-muted">الصيغ المسموحة: صورة أو PDF – حد أقصى 4MB</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">تصريح التدريس في الإمارات (إلزامي للحصص الحضورية)</label>

                            @if($profile->teaching_permit_path)
                                <p class="mb-1">
                                    <a href="{{ asset('storage/'.$profile->teaching_permit_path) }}" target="_blank">عرض الملف الحالي</a>
                                </p>
                            @endif

                            <input type="file"
                                   name="teaching_permit"
                                   id="teaching_permit"
                                   class="form-control @error('teaching_permit') is-invalid @enderror"
                                   accept="image/jpeg,image/jpg,image/png,image/webp,application/pdf">
                            @error('teaching_permit') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <small class="text-muted">الصيغ المسموحة: صورة أو PDF – مطلوب فقط إذا كنت تقدّم حصص حضورية.</small>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        {{-- أزرار --}}
        <div class="mt-3 d-flex flex-wrap gap-2 justify-content-end">
            <button type="submit" class="btn btn-primary" id="btnSave">حفظ التعديلات</button>

            <button type="button"
                    class="btn btn-success"
                    id="btnSubmitForReview"
                    {{ $isPendingReview ? 'disabled' : '' }}
                    title="{{ $isPendingReview ? 'ملفك قيد المراجعة بالفعل' : 'إرسال الملف للمراجعة' }}">
                إرسال للمراجعة
            </button>

        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const onlineCheckbox  = document.getElementById('teaches_online');
    const onsiteCheckbox  = document.getElementById('teaches_onsite');

    const onlinePrices    = document.querySelector('.online-prices');
    const onsitePrices    = document.querySelector('.onsite-prices');

    const onsiteLocation  = document.getElementById('onsite-location');
    const countrySelect   = document.getElementById('country_id');

    const citiesContainer = document.getElementById('onsite_cities_container');
    const citySearchInput = document.getElementById('city_search');
    const btnCitySearch   = document.getElementById('btnCitySearch');

    const form            = document.getElementById('teacherProfileForm');
    const methodError     = document.getElementById('teaching_method_error');

    const isAr = @json(app()->getLocale() === 'ar');

    // ✅ تواصل
    const phoneMobileInput = document.getElementById('phone_mobile');
    const whatsappInput    = document.getElementById('whatsapp_number');
    const contactCard      = document.getElementById('contact-card');

    // لغات
    const languagesHidden = document.getElementById('languages_hidden');
    const languageChecks  = document.querySelectorAll('.language-check');
    const otherLanguage   = document.getElementById('other_language');

    // submit flag
    const submitFlag      = document.getElementById('submit_for_review');
    const btnSubmit       = document.getElementById('btnSubmitForReview');

    // Files
    const profilePhotoInput = document.getElementById('profile_photo');
    const idDocInput        = document.getElementById('id_document');
    const permitInput       = document.getElementById('teaching_permit');

    // Existing files flags (server)
    const hasProfilePhoto = @json((bool)($profile->profile_photo_path ?? false));
    const hasIdDoc        = @json((bool)($profile->id_document_path ?? false));
    const hasPermit       = @json((bool)($profile->teaching_permit_path ?? false));

    function rebuildLanguagesCSV() {
        let langs = [];
        languageChecks.forEach(ch => { if (ch.checked) langs.push(ch.value); });

        const other = (otherLanguage?.value || '').trim();
        if (other) langs.push(other);

        langs = [...new Set(langs.map(x => x.trim()).filter(Boolean))];

        if (languagesHidden) languagesHidden.value = langs.join(', ');
    }

    const availabilityHidden = document.getElementById('availability_hidden');

    function rebuildAvailabilityJSON() {
        const rows = document.querySelectorAll('tr[data-day]');
        let data = {};
        rows.forEach(row => {
            const day = row.getAttribute('data-day');
            const enabledEl = row.querySelector('.avail-enabled');
            const fromEl = row.querySelector('.avail-from');
            const toEl   = row.querySelector('.avail-to');

            const enabled = !!enabledEl?.checked;
            const from = fromEl?.value || '';
            const to   = toEl?.value || '';

            data[day] = { enabled, from, to };
        });

        if (availabilityHidden) availabilityHidden.value = JSON.stringify(data);
    }

    document.querySelectorAll('.avail-enabled').forEach(ch => {
        ch.addEventListener('change', function () {
            const row = this.closest('tr');
            const fromEl = row.querySelector('.avail-from');
            const toEl = row.querySelector('.avail-to');
            if (fromEl) fromEl.disabled = !this.checked;
            if (toEl)   toEl.disabled   = !this.checked;
        });
    });

    function toggleSections() {
        if (onlineCheckbox && onlineCheckbox.checked) onlinePrices?.classList.remove('d-none');
        else onlinePrices?.classList.add('d-none');

        if (onsiteCheckbox && onsiteCheckbox.checked) {
            onsitePrices?.classList.remove('d-none');
            onsiteLocation?.classList.remove('d-none');
        } else {
            onsitePrices?.classList.add('d-none');
            onsiteLocation?.classList.add('d-none');
        }

        if (methodError) {
            if ((onlineCheckbox && onlineCheckbox.checked) || (onsiteCheckbox && onsiteCheckbox.checked)) {
                methodError.classList.add('d-none');
            }
        }
    }

    function filterCities() {
        if (!citySearchInput || !citiesContainer) return;
        const q = (citySearchInput.value || '').trim().toLowerCase();

        const items = citiesContainer.querySelectorAll('.city-item');
        items.forEach(item => {
            const ar = (item.getAttribute('data-ar') || '').toLowerCase();
            const en = (item.getAttribute('data-en') || '').toLowerCase();
            item.style.display = (!q || ar.includes(q) || en.includes(q)) ? '' : 'none';
        });
    }

    function renderCitiesCheckboxes(cities, keepSelected = true) {
        if (!citiesContainer) return;

        let selectedIds = [];
        try {
            selectedIds = JSON.parse(citiesContainer.getAttribute('data-selected') || '[]');
            if (!Array.isArray(selectedIds)) selectedIds = [];
        } catch (e) {
            selectedIds = [];
        }

        if (!keepSelected) {
            selectedIds = [];
            citiesContainer.setAttribute('data-selected', JSON.stringify([]));
        }

        if (!Array.isArray(cities) || cities.length === 0) {
            citiesContainer.innerHTML =
                `<div class="text-muted small">${isAr ? 'لا توجد مدن لهذه الدولة.' : 'No cities for this country.'}</div>`;
            return;
        }

        citiesContainer.innerHTML = '';

        cities.forEach(city => {
            const id = parseInt(city.id, 10);
            const nameAr = city.name_ar || '';
            const nameEn = city.name_en || '';
            const displayName = isAr ? (nameAr || nameEn) : (nameEn || nameAr);

            const label = document.createElement('label');
            label.className = 'd-flex align-items-center gap-2 py-1 mb-0 city-item';
            label.setAttribute('data-ar', nameAr);
            label.setAttribute('data-en', nameEn);

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = 'onsite_city_ids[]';
            input.value = id;
            input.className = 'form-check-input m-0';

            if (keepSelected && selectedIds.includes(id)) input.checked = true;

            const span = document.createElement('span');
            span.textContent = displayName;

            label.appendChild(input);
            label.appendChild(span);
            citiesContainer.appendChild(label);
        });

        filterCities();
    }

    async function loadCitiesForCountry(countryId, keepSelected = true) {
        if (!countrySelect || !citiesContainer) return;

        if (!countryId) {
            citiesContainer.innerHTML =
                `<div class="text-muted small">${isAr ? 'اختر دولة أولاً.' : 'Select a country first.'}</div>`;
            return;
        }

        const template = countrySelect.getAttribute('data-cities-url-template');
        const url = template.replace('__ID__', countryId);

        citiesContainer.innerHTML =
            `<div class="text-muted small">${isAr ? 'جارٍ تحميل المدن...' : 'Loading cities...'}</div>`;

        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('Failed');

            const data = await res.json();
            const cities = Array.isArray(data) ? data : (data.cities || []);

            renderCitiesCheckboxes(cities, keepSelected);
        } catch (e) {
            citiesContainer.innerHTML =
                `<div class="text-danger small">${isAr ? 'حدث خطأ أثناء تحميل المدن.' : 'Error loading cities.'}</div>`;
        }
    }

    function validateForSubmitReview() {
        const hasMethod = (onlineCheckbox && onlineCheckbox.checked) || (onsiteCheckbox && onsiteCheckbox.checked);
        if (!hasMethod) {
            if (methodError) {
                methodError.classList.remove('d-none');
                methodError.textContent = 'يجب اختيار طريقة درس واحدة على الأقل (أونلاين أو حضوري).';
                methodError.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
            return false;
        }

        // ✅ شرط: (موبايل أو واتساب) عند الإرسال للمراجعة
        const phoneVal = (phoneMobileInput?.value || '').trim();
        const waVal    = (whatsappInput?.value || '').trim();
        if (!phoneVal && !waVal) {
            alert('لا يمكن الإرسال للمراجعة: برجاء إدخال رقم هاتف/موبايل أو رقم واتساب واحد على الأقل.');
            if (contactCard) contactCard.scrollIntoView({behavior: 'smooth', block: 'start'});
            phoneMobileInput?.focus();
            return false;
        }

        const photoOk = hasProfilePhoto || (profilePhotoInput?.files?.length > 0);
        if (!photoOk) { alert('لا يمكن الإرسال للمراجعة: برجاء رفع صورة المعلّم.'); return false; }

        const idOk = hasIdDoc || (idDocInput?.files?.length > 0);
        if (!idOk) { alert('لا يمكن الإرسال للمراجعة: برجاء رفع ملف الهوية (ID).'); return false; }

        if (onlineCheckbox && onlineCheckbox.checked) {
            const oh = (document.getElementById('hourly_rate_online')?.value || '').trim();
            const ohalf = (document.getElementById('half_hour_rate_online')?.value || '').trim();
            if (!oh || !ohalf) { alert('لا يمكن الإرسال للمراجعة: برجاء إدخال أسعار الأونلاين (ساعة + نصف ساعة).'); return false; }
        }

        if (onsiteCheckbox && onsiteCheckbox.checked) {
            const cid = (countrySelect?.value || '').trim();
            if (!cid) { alert('لا يمكن الإرسال للمراجعة: برجاء اختيار الدولة للحصص الحضورية.'); return false; }

            const checkedCities = citiesContainer?.querySelectorAll('input[type="checkbox"][name="onsite_city_ids[]"]:checked') || [];
            if (!checkedCities.length) { alert('لا يمكن الإرسال للمراجعة: برجاء اختيار مدينة واحدة على الأقل للحصص الحضورية.'); return false; }

            const ih = (document.getElementById('hourly_rate_onsite')?.value || '').trim();
            const ihalf = (document.getElementById('half_hour_rate_onsite')?.value || '').trim();
            if (!ih || !ihalf) { alert('لا يمكن الإرسال للمراجعة: برجاء إدخال أسعار الحضوري (ساعة + نصف ساعة).'); return false; }

            const permitOk = hasPermit || (permitInput?.files?.length > 0);
            if (!permitOk) { alert('لا يمكن الإرسال للمراجعة: تصريح التدريس مطلوب للحصص الحضورية (ارفعه أولاً).'); return false; }
        }

        return true;
    }

    // ✅ داخل الصندوق: زر البحث
    if (btnCitySearch && citySearchInput) {
        btnCitySearch.addEventListener('click', function () {
            citySearchInput.classList.toggle('d-none');
            if (!citySearchInput.classList.contains('d-none')) {
                citySearchInput.focus();
            } else {
                citySearchInput.value = '';
                filterCities();
            }
        });

        citySearchInput.addEventListener('input', filterCities);

        citySearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                citySearchInput.classList.add('d-none');
                citySearchInput.value = '';
                filterCities();
            }
        });
    }

    // init
    toggleSections();
    rebuildLanguagesCSV();
    rebuildAvailabilityJSON();

    if (onsiteCheckbox?.checked && countrySelect?.value) loadCitiesForCountry(countrySelect.value, true);

    onlineCheckbox?.addEventListener('change', toggleSections);

    onsiteCheckbox?.addEventListener('change', function () {
        toggleSections();
        if (onsiteCheckbox.checked && countrySelect?.value) loadCitiesForCountry(countrySelect.value, true);
    });

    countrySelect?.addEventListener('change', function () {
        loadCitiesForCountry(this.value, false);
    });

    languageChecks.forEach(ch => ch.addEventListener('change', rebuildLanguagesCSV));
    otherLanguage?.addEventListener('input', rebuildLanguagesCSV);

    if (btnSubmit) {
        btnSubmit.addEventListener('click', function () {
            const ok = confirm('تأكيد إرسال ملفك للمراجعة؟ لن يظهر للطلاب حتى يوافق الأدمن.');
            if (!ok) return;

            if (!validateForSubmitReview()) return;

            rebuildLanguagesCSV();
            rebuildAvailabilityJSON();

            if (submitFlag) submitFlag.value = '1';
            form.submit();
        });
    }

    form?.addEventListener('submit', function (e) {
        if (onlineCheckbox && onsiteCheckbox && !onlineCheckbox.checked && !onsiteCheckbox.checked) {
            e.preventDefault();
            if (methodError) {
                methodError.classList.remove('d-none');
                methodError.textContent = 'يجب اختيار طريقة درس واحدة على الأقل (أونلاين أو حضوري).';
                methodError.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
            return;
        }

        rebuildLanguagesCSV();
        rebuildAvailabilityJSON();
    });
});
</script>
@endsection
