@extends('layouts.app')
@section('page_title', 'تعديل ملف المعلّم')

@section('content')
<div class="container py-4">

    <h1 class="mb-3">ملف المعلّم: {{ $teacher->name }}</h1>

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

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST"
          action="{{ route('admin.teachers.profile.update', $teacher->id) }}"
          enctype="multipart/form-data">

        @csrf
        @method('PUT')

        <div class="row g-4">

            {{-- بيانات عامة --}}
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        بيانات عامة
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">عنوان قصير (يظهر للطلاب)</label>
                            <input type="text" name="headline" class="form-control"
                                   value="{{ old('headline', $profile->headline) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">نبذة عن المعلّم</label>
                            <textarea name="bio" rows="4" class="form-control">{{ old('bio', $profile->bio) }}</textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">الدولة</label>
                                <input type="text" name="country" class="form-control"
                                       value="{{ old('country', $profile->country) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">المدينة</label>
                                <input type="text" name="city" class="form-control"
                                       value="{{ old('city', $profile->city) }}">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">المادة الرئيسية</label>
                                <input type="text" name="main_subject" class="form-control"
                                       value="{{ old('main_subject', $profile->main_subject) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">سنوات الخبرة</label>
                                <input type="number" name="experience_years" class="form-control"
                                       value="{{ old('experience_years', $profile->experience_years) }}">
                            </div>
                        </div>

                    </div>
                </div>

                {{-- طرق التدريس والأسعار --}}
                @php
                    $hasOnline = old('teaches_online', $profile->teaches_online);
                    $hasOnsite = old('teaches_onsite', $profile->teaches_onsite);
                @endphp

                <div class="card mb-3">
                    <div class="card-header">
                        طرق التدريس والأسعار
                    </div>
                    <div class="card-body">

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1"
                                           id="teaches_online" name="teaches_online"
                                           {{ $hasOnline ? 'checked' : '' }}>
                                    <label class="form-check-label" for="teaches_online">
                                        يقدّم حصص أونلاين
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1"
                                           id="teaches_onsite" name="teaches_onsite"
                                           {{ $hasOnsite ? 'checked' : '' }}>
                                    <label class="form-check-label" for="teaches_onsite">
                                        يقدّم حصص حضورية
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- رسالة خطأ لاختيار طريقة التدريس --}}
                        <div id="teaching_method_error"
                             class="text-danger small mb-2 {{ $errors->has('teaches_online') ? '' : 'd-none' }}">
                            {{ $errors->first('teaches_online') ?? 'يجب اختيار طريقة درس واحدة على الأقل (أونلاين أو حضوري).' }}
                        </div>

                        {{-- أسعار الأونلاين --}}
                        <div class="row g-3 online-prices {{ $hasOnline ? '' : 'd-none' }}">
                            <div class="col-md-3">
                                <label class="form-label">أونلاين / ساعة</label>
                                <input type="number" step="0.01" name="hourly_rate_online" class="form-control"
                                       value="{{ old('hourly_rate_online', $profile->hourly_rate_online) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">أونلاين / نصف ساعة</label>
                                <input type="number" step="0.01" name="half_hour_rate_online" class="form-control"
                                       value="{{ old('half_hour_rate_online', $profile->half_hour_rate_online) }}">
                            </div>
                        </div>

                        {{-- أسعار الحضوري --}}
                        <div class="row g-3 mt-3 onsite-prices {{ $hasOnsite ? '' : 'd-none' }}">
                            <div class="col-md-3">
                                <label class="form-label">حضوري / ساعة</label>
                                <input type="number" step="0.01" name="hourly_rate_onsite" class="form-control"
                                       value="{{ old('hourly_rate_onsite', $profile->hourly_rate_onsite) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">حضوري / نصف ساعة</label>
                                <input type="number" step="0.01" name="half_hour_rate_onsite" class="form-control"
                                       value="{{ old('half_hour_rate_onsite', $profile->half_hour_rate_onsite) }}">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ميديا وملفات --}}
            <div class="col-md-4">

                {{-- صورة البروفايل --}}
                <div class="card mb-3">
                    <div class="card-header">
                        صورة المعلّم
                    </div>
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
                            <input type="file" name="profile_photo" class="form-control" accept="image/*">
                            <small class="text-muted">الصيغ المسموحة: JPG, JPEG, PNG, WEBP – حد أقصى 2MB</small>
                        </div>
                    </div>
                </div>

                {{-- فيديو تعريفي --}}
                <div class="card mb-3">
                    <div class="card-header">
                        فيديو تعريفي
                    </div>
                    <div class="card-body">
                        @if($profile->intro_video_url)
                            <p class="mb-2">
                                <strong>الرابط الحالي:</strong><br>
                                <a href="{{ $profile->intro_video_url }}" target="_blank">
                                    {{ $profile->intro_video_url }}
                                </a>
                            </p>
                        @endif

                        <label class="form-label">رابط فيديو (يوتيوب مثلاً)</label>
                        <input type="text" name="intro_video_url" class="form-control"
                               value="{{ old('intro_video_url', $profile->intro_video_url) }}">
                    </div>
                </div>

                {{-- الهوية + تصريح التدريس --}}
                <div class="card mb-3">
                    <div class="card-header">
                        مستندات رسمية
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">ملف الهوية (ID)</label>
                            @if($profile->id_document_path)
                                <p class="mb-1">
                                    <a href="{{ asset('storage/'.$profile->id_document_path) }}" target="_blank">
                                        عرض الملف الحالي
                                    </a>
                                </p>
                            @endif
                            <input type="file" name="id_document" class="form-control" accept="image/*,.pdf">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">تصريح التدريس في الإمارات</label>
                            @if($profile->teaching_permit_path)
                                <p class="mb-1">
                                    <a href="{{ asset('storage/'.$profile->teaching_permit_path) }}" target="_blank">
                                        عرض الملف الحالي
                                    </a>
                                </p>
                            @endif
                           <input type="file" name="teaching_permit" class="form-control" accept="image/*,.pdf">
                           <small class="text-muted">
                               الصيغ المسموحة: صورة أو PDF – مطلوب فقط إذا كان المعلّم يقدّم حصص حضورية
                           </small>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <div class="mt-3 text-end">
            <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary">
                رجوع للقائمة
            </a>
            <button type="submit" class="btn btn-primary">
                حفظ التعديلات
            </button>
        </div>

    </form>
</div>

{{-- سكربت للتحكم في إظهار حقول الأسعار + التحقق من اختيار طريقة التدريس --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const onlineCheckbox  = document.getElementById('teaches_online');
        const onsiteCheckbox  = document.getElementById('teaches_onsite');

        const onlinePrices    = document.querySelector('.online-prices');
        const onsitePrices    = document.querySelector('.onsite-prices');

        const form            = document.querySelector('form');
        const methodError     = document.getElementById('teaching_method_error');

        function toggleSections() {
            if (onlineCheckbox.checked) {
                onlinePrices.classList.remove('d-none');
            } else {
                onlinePrices.classList.add('d-none');
            }

            if (onsiteCheckbox.checked) {
                onsitePrices.classList.remove('d-none');
            } else {
                onsitePrices.classList.add('d-none');
            }

            // إخفاء رسالة الخطأ عند وجود اختيار
            if (onlineCheckbox.checked || onsiteCheckbox.checked) {
                methodError.classList.add('d-none');
            }
        }

        // أول تحميل للصفحة
        toggleSections();

        // عند تغيير الاختيارات
        onlineCheckbox.addEventListener('change', toggleSections);
        onsiteCheckbox.addEventListener('change', toggleSections);

        // تحقق قبل إرسال الفورم
        form.addEventListener('submit', function (e) {
            if (!onlineCheckbox.checked && !onsiteCheckbox.checked) {
                e.preventDefault();
                methodError.classList.remove('d-none');
                methodError.textContent = 'يجب اختيار طريقة درس واحدة على الأقل (أونلاين أو حضوري).';
                methodError.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
        });
    });
</script>
@endsection
