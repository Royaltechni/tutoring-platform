<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeacherProfileController extends Controller
{
    /**
     * قائمة المعلّمين في لوحة الأدمن
     * stage:
     * - submitted => تم الإرسال فقط (teacher_profiles.submitted_at NOT NULL)
     * - draft     => مسودات فقط (teacher_profiles.submitted_at IS NULL أو لا يوجد بروفايل)
     * - null      => الكل
     */
    public function index(Request $request)
    {
        $stage = $request->query('stage'); // submitted | draft | null

        $teachersQuery = User::where('role', 'teacher')
            ->with('teacherProfile')
            ->orderBy('name');

        // ✅ فلتر المرحلة
        if ($stage === 'submitted') {
            // لازم يكون عنده بروفايل + submitted_at موجود
            $teachersQuery->whereHas('teacherProfile', function ($q) {
                $q->whereNotNull('submitted_at');
            });
        } elseif ($stage === 'draft') {
            // مسودة = (لا يوجد بروفايل) OR (submitted_at null)
            $teachersQuery->where(function ($q) {
                $q->whereDoesntHave('teacherProfile')
                  ->orWhereHas('teacherProfile', function ($qq) {
                      $qq->whereNull('submitted_at');
                  });
            });
        }

        $teachers = $teachersQuery->paginate(15)->withQueryString();

        return view('admin.teachers.index', compact('teachers', 'stage'));
    }

    /**
     * شاشة تعديل ملف المعلّم
     */
    public function editProfile($teacherId)
    {
        $teacher = User::where('role', 'teacher')
            ->with('teacherProfile')
            ->findOrFail($teacherId);

        // لو ما عندوش بروفايل لسه ننشئ واحد في الذاكرة
        $profile = $teacher->teacherProfile ?? new TeacherProfile([
            'user_id' => $teacher->id,
        ]);

        // ملاحظة: اسم الملف هو edit-profile.blade.php
        return view('admin.teachers.edit-profile', compact('teacher', 'profile'));
    }

    /**
     * حفظ بيانات/ملف المعلّم
     */
    public function updateProfile(Request $request, $teacherId)
    {
        $teacher = User::where('role', 'teacher')
            ->with('teacherProfile')
            ->findOrFail($teacherId);

        $profile = $teacher->teacherProfile ?? new TeacherProfile([
            'user_id' => $teacher->id,
        ]);

        // ✅ التحقق من البيانات
        $data = $request->validate([
            'bio'                => ['required', 'string', 'min:20'],
            'headline'           => ['nullable', 'string', 'max:255'],
            'country'            => ['required', 'string', 'max:100'],
            'city'               => ['required', 'string', 'max:100'],
            'main_subject'       => ['required', 'string', 'max:150'],
            'experience_years'   => ['required', 'integer', 'between:0,50'],

            'teaches_online'     => ['nullable', 'boolean'],
            'teaches_onsite'     => ['nullable', 'boolean'],

            'hourly_rate_online'       => ['nullable', 'numeric', 'min:0'],
            'half_hour_rate_online'    => ['nullable', 'numeric', 'min:0'],
            'hourly_rate_onsite'       => ['nullable', 'numeric', 'min:0'],
            'half_hour_rate_onsite'    => ['nullable', 'numeric', 'min:0'],

            'intro_video_url'    => ['nullable', 'url', 'max:255'],

            // ✅ الصورة: صورة فقط
            'profile_photo'      => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],

            // ✅ الهوية: صورة أو PDF
            'id_document'        => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],

            // ✅ تصريح التدريس: صورة أو PDF (هنعمل الشرط الإجباري يدوي تحت)
            'teaching_permit'    => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ], [
            'bio.required'              => 'برجاء كتابة نبذة تعريفية عن المعلّم.',
            'country.required'          => 'برجاء اختيار الدولة.',
            'city.required'             => 'برجاء إدخال المدينة.',
            'main_subject.required'     => 'برجاء تحديد المادة الأساسية.',
            'experience_years.required' => 'برجاء إدخال عدد سنوات الخبرة.',

            'profile_photo.image'       => 'صورة المعلّم يجب أن تكون ملف صورة.',
            'profile_photo.mimes'       => 'صورة المعلّم يجب أن تكون من النوع: JPG أو PNG أو WEBP.',
            'id_document.mimes'         => 'ملف الهوية يجب أن يكون صورة أو ملف PDF.',
            'teaching_permit.mimes'     => 'تصريح التدريس يجب أن يكون صورة أو ملف PDF.',
        ]);

        // نحول الـ checkbox إلى true/false
        $teachesOnline = $request->boolean('teaches_online');
        $teachesOnsite = $request->boolean('teaches_onsite');

        // ✅ لازم يختار طريقة واحدة على الأقل (أونلاين أو حضوري)
        if (!$teachesOnline && !$teachesOnsite) {
            return back()
                ->withErrors([
                    'teaches_online' => 'يجب اختيار طريقة درس واحدة على الأقل (أونلاين أو حضوري).',
                ])
                ->withInput();
        }

        // ✅ لو حصص حضورية ولا يوجد تصريح تدريس (قديم أو جديد) → خطأ
        $hasExistingPermit = !empty($profile->teaching_permit_path);
        $hasNewPermit      = $request->hasFile('teaching_permit');

        if ($teachesOnsite && !$hasExistingPermit && !$hasNewPermit) {
            return back()
                ->withErrors([
                    'teaching_permit' => 'لأن المعلّم يقدّم حصص حضورية، يجب رفع تصريح التدريس (صورة أو ملف PDF).',
                ])
                ->withInput();
        }

        // ✅ رفع / استبدال صورة المعلّم
        if ($request->hasFile('profile_photo')) {
            if ($profile->profile_photo_path) {
                Storage::disk('public')->delete($profile->profile_photo_path);
            }

            $path = $request->file('profile_photo')
                ->store('teacher_photos', 'public');

            $profile->profile_photo_path = $path;
        }

        // ✅ رفع / استبدال ملف الهوية
        if ($request->hasFile('id_document')) {
            if ($profile->id_document_path) {
                Storage::disk('public')->delete($profile->id_document_path);
            }

            $path = $request->file('id_document')
                ->store('teacher_ids', 'public');

            $profile->id_document_path = $path;
        }

        // ✅ رفع / استبدال تصريح التدريس
        if ($request->hasFile('teaching_permit')) {
            if ($profile->teaching_permit_path) {
                Storage::disk('public')->delete($profile->teaching_permit_path);
            }

            $path = $request->file('teaching_permit')
                ->store('teacher_permits', 'public');

            $profile->teaching_permit_path = $path;
        }

        // ✅ حفظ باقي البيانات النصية والرقمية
        $profile->bio               = $data['bio'];
        $profile->headline          = $data['headline']        ?? $profile->headline;
        $profile->country           = $data['country'];
        $profile->city              = $data['city'];
        $profile->main_subject      = $data['main_subject'];
        $profile->experience_years  = $data['experience_years'];

        $profile->intro_video_url       = $data['intro_video_url']        ?? null;

        $profile->teaches_online        = $teachesOnline;
        $profile->teaches_onsite        = $teachesOnsite;

        $profile->hourly_rate_online    = $data['hourly_rate_online']     ?? null;
        $profile->half_hour_rate_online = $data['half_hour_rate_online']  ?? null;
        $profile->hourly_rate_onsite    = $data['hourly_rate_onsite']     ?? null;
        $profile->half_hour_rate_onsite = $data['half_hour_rate_onsite']  ?? null;

        $profile->save();

        return redirect()
            ->route('admin.teachers.profile.edit', $teacher->id)
            ->with('success', 'تم حفظ ملف المعلّم بنجاح.');
    }
}
