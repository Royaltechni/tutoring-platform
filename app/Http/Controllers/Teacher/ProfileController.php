<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\Country;
use App\Models\City;

class ProfileController extends Controller
{
    /**
     * عرض صفحة تعديل الملف
     */
    public function edit()
    {
        $user    = auth()->user();
        $profile = $user->teacherProfile ?? new TeacherProfile(['user_id' => $user->id]);

        // ✅ UAE first
        $uaeId = Country::where('code', 'AE')->value('id');
        if (!$uaeId) {
            $uaeId = Country::where('name_en', 'United Arab Emirates')->value('id');
        }

        $countries = Country::query()
            ->when($uaeId, function ($q) use ($uaeId) {
                $q->orderByRaw("CASE WHEN id = ? THEN 0 ELSE 1 END", [$uaeId]);
            })
            ->orderBy('name_en')
            ->get(['id', 'name_ar', 'name_en', 'code']);

        $selectedCountryId = old('country_id', $profile->country_id ?? $uaeId);

        // ✅ المدن المختارة سابقًا (Pivot أولاً، ثم fallback JSON)
        $selectedOnsiteCityIds = [];

        if ($profile->exists) {
            // 1) Pivot relation (لو موجودة)
            try {
                if (method_exists($profile, 'onsiteCities')) {
                    $selectedOnsiteCityIds = $profile->onsiteCities()->pluck('cities.id')->toArray();
                }
            } catch (\Throwable $e) {
                $selectedOnsiteCityIds = [];
            }

            // 2) Fallback: JSON/Array column onsite_city_ids
            if (empty($selectedOnsiteCityIds) && !empty($profile->onsite_city_ids)) {
                $raw = $profile->onsite_city_ids;

                if (is_array($raw)) {
                    $selectedOnsiteCityIds = $raw;
                } elseif (is_string($raw) && trim($raw) !== '') {
                    $decoded = json_decode($raw, true);
                    $selectedOnsiteCityIds = is_array($decoded) ? $decoded : [];
                } else {
                    $selectedOnsiteCityIds = [];
                }
            }
        }

        // ✅ تحميل مدن الدولة المختارة
        $cities = collect([]);
        if ($selectedCountryId) {
            $cities = City::where('country_id', $selectedCountryId)
                ->orderBy('name_en')
                ->get(['id', 'country_id', 'name_ar', 'name_en']);
        }

        return view('teacher.profile.edit', [
            'user'                  => $user,
            'profile'               => $profile,
            'selectedOnsiteCityIds' => $selectedOnsiteCityIds,
            'countries'             => $countries,
            'uaeDefaultCountryId'   => $uaeId,
            'cities'                => $cities,
            'selectedCountryId'     => $selectedCountryId,
        ]);
    }

    /**
     * API: cities by country (لو بتستخدمه)
     */
    public function cities($countryId)
    {
        $cities = City::where('country_id', $countryId)
            ->orderBy('name_en')
            ->get(['id', 'country_id', 'name_ar', 'name_en']);

        return response()->json($cities);
    }

    /**
     * حفظ تعديل الملف
     */
    public function update(Request $request)
    {
        $user    = $request->user();
        $profile = $user->teacherProfile ?? new TeacherProfile(['user_id' => $user->id]);

        // ✅ هل هذه "إرسال للمراجعة"؟
        $isSubmitForReview = $request->boolean('submit_for_review');

        // ✅ امنع إعادة الإرسال لو بالفعل submitted وهو pending
        if (
            $isSubmitForReview &&
            !is_null($profile->submitted_at) &&
            ($user->teacher_status === \App\Models\TeacherProfile::STATUS_PENDING)
        ) {
            return redirect()
                ->route('teacher.profile.edit')
                ->with('success', 'ℹ️ ملفك تم إرساله للمراجعة بالفعل وهو قيد المراجعة.');
        }

        // ✅ ثبّت قيم الـ checkboxes (لأن unchecked لا تُرسل)
        $request->merge([
            'teaches_online' => $request->has('teaches_online') ? 1 : 0,
            'teaches_onsite' => $request->has('teaches_onsite') ? 1 : 0,
        ]);

        // ✅ دعم الاسم القديم والجديد معًا (grade_min/grade_max القديم)
        $min = $request->input('min_grade', $request->input('grade_min'));
        $max = $request->input('max_grade', $request->input('grade_max'));

        $request->merge([
            'min_grade' => $min,
            'max_grade' => $max,
        ]);

        // ✅ تنظيف break_minutes
        $break = $request->input('break_minutes', 0);
        if ($break === '' || $break === null) $break = 0;
        if (!is_numeric($break)) $break = 0;
        $break = (int) $break;
        if ($break < 0) $break = 0;
        if ($break > 180) $break = 180;

        $request->merge(['break_minutes' => $break]);

        $phoneRegex = '/^[0-9\+\-\s\(\)]{6,50}$/';

        $rules = [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password'              => ['nullable', 'confirmed', 'min:8'],

            'headline'              => ['nullable', 'string', 'max:255'],
            'bio'                   => ['nullable', 'string'],

            'intro_video_url'       => ['nullable', 'string', 'max:2048', 'url'],

            // legacy
            'country'               => ['nullable', 'string', 'max:255'],
            'city'                  => ['nullable', 'string', 'max:255'],

            'main_subject'          => ['nullable', 'string', 'max:255'],
            'experience_years'      => ['nullable', 'integer', 'min:0'],

            'min_grade'             => ['nullable', 'integer', 'min:1', 'max:12'],
            'max_grade'             => ['nullable', 'integer', 'min:1', 'max:12'],

            'curricula'             => ['nullable', 'array'],
            'curricula.*'           => ['string', 'max:50'],

            'teaches_online'        => ['required', 'boolean'],
            'teaches_onsite'        => ['required', 'boolean'],

            'hourly_rate_online'    => ['nullable', 'numeric', 'min:0'],
            'half_hour_rate_online' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate_onsite'    => ['nullable', 'numeric', 'min:0'],
            'half_hour_rate_onsite' => ['nullable', 'numeric', 'min:0'],

            'country_id'            => ['nullable', 'integer'],
            'onsite_city_ids'       => ['nullable', 'array'],
            'onsite_city_ids.*'     => ['integer'],

            // ✅ الملفات (تظل nullable هنا، والإلزام يتم في after عند submit_for_review)
            'profile_photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'id_document'           => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'teaching_permit'       => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],

            'subjects'              => ['nullable', 'string', 'max:5000'],
            'languages'             => ['nullable', 'string', 'max:1000'],
            'teaching_style'        => ['nullable', 'string', 'max:5000'],
            'cancel_policy'         => ['nullable', 'string', 'max:5000'],
            'availability'          => ['nullable', 'string', 'max:10000'],

            'break_minutes'         => ['nullable', 'integer', 'min:0', 'max:180'],

            // ✅✅ الحقول الجديدة (بيانات تواصل + سوشيال)
            'phone_mobile'          => ['nullable', 'string', 'max:50', "regex:$phoneRegex"],
            'whatsapp_number'       => ['nullable', 'string', 'max:50', "regex:$phoneRegex"],
            'address_details'       => ['nullable', 'string', 'max:5000'],

            'website_url'           => ['nullable', 'url', 'max:2048'],
            'facebook_url'          => ['nullable', 'url', 'max:2048'],
            'instagram_url'         => ['nullable', 'url', 'max:2048'],
            'tiktok_url'            => ['nullable', 'url', 'max:2048'],
            'youtube_url'           => ['nullable', 'url', 'max:2048'],
            'linkedin_url'          => ['nullable', 'url', 'max:2048'],

            'submit_for_review'     => ['nullable', 'boolean'],
        ];

        $messages = [
            'name.required' => 'الاسم الكامل مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صحيح.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',

            'intro_video_url.url' => 'رابط الفيديو غير صحيح.',
            'website_url.url' => 'رابط الموقع الإلكتروني غير صحيح.',
            'facebook_url.url' => 'رابط فيسبوك غير صحيح.',
            'instagram_url.url' => 'رابط إنستغرام غير صحيح.',
            'tiktok_url.url' => 'رابط تيك توك غير صحيح.',
            'youtube_url.url' => 'رابط يوتيوب غير صحيح.',
            'linkedin_url.url' => 'رابط لينكدإن غير صحيح.',

            'phone_mobile.regex' => 'رقم الهاتف/الموبايل غير صحيح. استخدم أرقامًا ويمكن إضافة + أو مسافات.',
            'whatsapp_number.regex' => 'رقم الواتساب غير صحيح. استخدم أرقامًا ويمكن إضافة + أو مسافات.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $validator->after(function ($validator) use ($request, $profile, $isSubmitForReview) {
            $online = $request->boolean('teaches_online');
            $onsite = $request->boolean('teaches_onsite');

            $min = $request->input('min_grade');
            $max = $request->input('max_grade');
            if ($min && $max && (int)$min > (int)$max) {
                $validator->errors()->add('min_grade', 'أقل صف يجب أن يكون أصغر من أو يساوي أكبر صف.');
            }

            if ($isSubmitForReview) {

                if (!$online && !$onsite) {
                    $validator->errors()->add('teaches_online', 'يجب اختيار طريقة درس واحدة على الأقل (أونلاين أو حضوري).');
                }

                // ✅ شرط: موبايل أو واتساب واحد على الأقل
                $phone    = trim((string)$request->input('phone_mobile', ''));
                $whatsapp = trim((string)$request->input('whatsapp_number', ''));
                if ($phone === '' && $whatsapp === '') {
                    $validator->errors()->add('phone_mobile', 'يجب إدخال رقم هاتف أو رقم واتساب واحد على الأقل قبل الإرسال للمراجعة.');
                }

                if ($online) {
                    if ((float)$request->input('hourly_rate_online') <= 0) {
                        $validator->errors()->add('hourly_rate_online', 'يجب إدخال سعر للساعة الأونلاين أكبر من صفر.');
                    }
                    if ((float)$request->input('half_hour_rate_online') <= 0) {
                        $validator->errors()->add('half_hour_rate_online', 'يجب إدخال سعر لنصف الساعة الأونلاين أكبر من صفر.');
                    }
                }

                if ($onsite) {
                    if ((float)$request->input('hourly_rate_onsite') <= 0) {
                        $validator->errors()->add('hourly_rate_onsite', 'يجب إدخال سعر للساعة الحضورية أكبر من صفر.');
                    }
                    if ((float)$request->input('half_hour_rate_onsite') <= 0) {
                        $validator->errors()->add('half_hour_rate_onsite', 'يجب إدخال سعر لنصف الساعة الحضورية أكبر من صفر.');
                    }

                    $countryId = $request->input('country_id');
                    if (empty($countryId) || !is_numeric($countryId)) {
                        $validator->errors()->add('country_id', 'من فضلك اختر الدولة للحصص الحضورية.');
                    }

                    $cityIds = $request->input('onsite_city_ids', []);
                    if (!is_array($cityIds) || count($cityIds) < 1) {
                        $validator->errors()->add('onsite_city_ids', 'من فضلك اختر مدينة واحدة على الأقل للحصص الحضورية.');
                    }
                }

                // ✅ مستندات إلزامية عند الإرسال للمراجعة فقط
                $hasOldPhoto = !empty($profile->profile_photo_path);
                $hasNewPhoto = $request->hasFile('profile_photo');
                if (!$hasOldPhoto && !$hasNewPhoto) {
                    $validator->errors()->add('profile_photo', 'يجب رفع صورة شخصية للمعلّم قبل الإرسال للمراجعة.');
                }

                $hasOldId = !empty($profile->id_document_path);
                $hasNewId = $request->hasFile('id_document');
                if (!$hasOldId && !$hasNewId) {
                    $validator->errors()->add('id_document', 'يجب رفع ملف الهوية (ID) للمعلّم قبل الإرسال للمراجعة.');
                }

                if ($onsite) {
                    $hasOldPermit = !empty($profile->teaching_permit_path);
                    $hasNewPermit = $request->hasFile('teaching_permit');
                    if (!$hasOldPermit && !$hasNewPermit) {
                        $validator->errors()->add('teaching_permit', 'يجب رفع تصريح التدريس إذا كنت تقدّم حصص حضورية قبل الإرسال للمراجعة.');
                    }
                }
            }

            $availability = $request->input('availability');
            if (!empty($availability)) {
                $decoded = json_decode($availability, true);
                if (!is_array($decoded)) {
                    $validator->errors()->add('availability', 'صيغة جدول التوفر غير صحيحة.');
                }
            }
        });

        $validated = $validator->validate();

        // ✅ تحديث user
        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        /**
         * ✅✅ التعديل الأهم لحل مشكلتك:
         * عند الإرسال للمراجعة (حتى لو كان Approved قبل كده)
         * نرجّع الحالة Pending عشان الأدمن يشوفه تاني للمراجعة.
         */
        if ($isSubmitForReview) {
            $user->teacher_status = TeacherProfile::STATUS_PENDING;
        }

        $user->save();

        // ✅ تحديث profile
        $profile->user_id          = $user->id;

        $profile->headline         = $request->input('headline');
        $profile->bio              = $request->input('bio');

        $introVideo = trim((string)$request->input('intro_video_url', ''));
        $profile->intro_video_url  = $introVideo !== '' ? $introVideo : null;

        // legacy
        $profile->country          = $request->input('country');
        $profile->city             = $request->input('city');

        $profile->main_subject     = $request->input('main_subject');
        $profile->experience_years = $request->input('experience_years');

        $profile->teaches_online   = $request->boolean('teaches_online');
        $profile->teaches_onsite   = $request->boolean('teaches_onsite');

        $profile->hourly_rate_online    = $request->input('hourly_rate_online');
        $profile->half_hour_rate_online = $request->input('half_hour_rate_online');
        $profile->hourly_rate_onsite    = $request->input('hourly_rate_onsite');
        $profile->half_hour_rate_onsite = $request->input('half_hour_rate_onsite');

        $profile->break_minutes = (int) $request->input('break_minutes', 0);

        $profile->min_grade = $request->input('min_grade') !== null ? (int)$request->input('min_grade') : null;
        $profile->max_grade = $request->input('max_grade') !== null ? (int)$request->input('max_grade') : null;

        $curr = $request->input('curricula', []);
        $curr = is_array($curr) ? array_values(array_filter(array_map('trim', $curr))) : [];
        $profile->curricula = $curr;

        // ✅ لو مش حضوري، نظّف دولة/مدن الحضوري
        if ($profile->teaches_onsite) {
            $profile->country_id = $request->input('country_id');

            $cityIds = $request->input('onsite_city_ids', []);
            $profile->onsite_city_ids = is_array($cityIds) ? array_values($cityIds) : [];
        } else {
            $profile->country_id = null;
            $profile->onsite_city_ids = [];
        }

        // ✅ الحقول النصية
        $profile->subjects = $request->input('subjects');

        $langs = trim((string)$request->input('languages', ''));
        $langs = preg_replace('/\s+/', ' ', $langs);
        $profile->languages = $langs !== '' ? $langs : null;

        $style = trim((string)$request->input('teaching_style', ''));
        $profile->teaching_style = $style !== '' ? $style : null;

        $policy = trim((string)$request->input('cancel_policy', ''));
        $profile->cancel_policy = $policy !== '' ? $policy : null;

        // availability: نخزنها Array
        $availability = $request->input('availability');
        if (!empty($availability)) {
            $decoded = json_decode($availability, true);
            $profile->availability = is_array($decoded) ? $decoded : null;
        } else {
            $profile->availability = null;
        }

        // ✅✅✅ الحقول الجديدة: التواصل + السوشيال
        $profile->phone_mobile    = trim((string)$request->input('phone_mobile', '')) ?: null;
        $profile->whatsapp_number = trim((string)$request->input('whatsapp_number', '')) ?: null;

        $addr = trim((string)$request->input('address_details', ''));
        $profile->address_details = $addr !== '' ? $addr : null;

        $profile->website_url   = $request->input('website_url') ?: null;
        $profile->facebook_url  = $request->input('facebook_url') ?: null;
        $profile->instagram_url = $request->input('instagram_url') ?: null;
        $profile->tiktok_url    = $request->input('tiktok_url') ?: null;
        $profile->youtube_url   = $request->input('youtube_url') ?: null;
        $profile->linkedin_url  = $request->input('linkedin_url') ?: null;

        // ✅ الملفات
        if ($request->hasFile('profile_photo')) {
            $profile->profile_photo_path = $request->file('profile_photo')->store('teacher_photos', 'public');
        }

        if ($request->hasFile('id_document')) {
            $profile->id_document_path = $request->file('id_document')->store('teacher_id_docs', 'public');
        }

        if ($request->hasFile('teaching_permit')) {
            $profile->teaching_permit_path = $request->file('teaching_permit')->store('teacher_permits', 'public');
        }

        /**
         * ✅✅✅ التعديل الأهم الثاني:
         * لازم كل مرة Submit نحدّث submitted_at = now()
         */
        if ($isSubmitForReview) {
            $profile->submitted_at   = now(); // ✅ كل مرة (مراجعة جديدة)

            $profile->account_status = TeacherProfile::STATUS_PENDING;

            // امسح سبب الرفض + ملاحظة الأدمن القديمة (عشان مراجعة جديدة)
            $profile->rejection_reason = null;
            $profile->admin_note       = null;
        }

        // ✅ احفظ بعد ما نخلص كل التعديلات
        $profile->save();

        // ✅ Pivot sync
        try {
            if (method_exists($profile, 'onsiteCities')) {
                if ($profile->teaches_onsite) {
                    $ids = $request->input('onsite_city_ids', []);
                    $profile->onsiteCities()->sync(is_array($ids) ? $ids : []);
                } else {
                    $profile->onsiteCities()->sync([]);
                }
            }
        } catch (\Throwable $e) {}

        // ✅ رسالة حسب السيناريو
        if ($isSubmitForReview) {
            return redirect()
                ->route('teacher.profile.edit')
                ->with('success', '✅ تم إرسال ملفك للمراجعة بنجاح. سيتم تفعيل حسابك بعد مراجعة إدارة المنصّة.');
        }

        return redirect()
            ->route('teacher.profile.edit')
            ->with('success', '✅ تم حفظ بيانات ملفك بنجاح.');
    }
}
