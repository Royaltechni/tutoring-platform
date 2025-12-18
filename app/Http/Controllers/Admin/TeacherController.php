<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use App\Models\TeacherAuditLog;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    /**
     * عرض قائمة جميع المعلّمين
     * + حالة الحساب
     * + بحث
     * + فلتر مستندات ناقصة
     * + فلتر المرحلة (submitted / draft)
     */
    public function index(Request $request)
    {
        $status  = $request->get('status');                 // pending|approved|rejected
        $q       = trim((string) $request->get('q'));       // search
        $missing = (int) $request->get('missing', 0);       // 1 => ناقص فقط
        $stage   = $request->get('stage', 'all');           // all|submitted|draft

        $query = User::query()
            ->where('role', 'teacher')
            ->with('teacherProfile');

        // ✅ فلترة الحالة من users.teacher_status (المصدر الأساسي)
        if (!empty($status)) {
            $query->where('teacher_status', $status);
        }

        // ✅ فلترة المرحلة (توحيد draft بدل drafts)
        if ($stage === 'submitted') {
            $query->whereHas('teacherProfile', function ($p) {
                $p->whereNotNull('submitted_at');
            });
        } elseif ($stage === 'draft') {
            // draft: لا يوجد profile أو submitted_at = null
            $query->where(function ($qq) {
                $qq->whereDoesntHave('teacherProfile')
                   ->orWhereHas('teacherProfile', function ($p) {
                       $p->whereNull('submitted_at');
                   });
            });
        }

        // ✅ بحث
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }

        // ✅ مستندات ناقصة فقط (Server-side)
        if ($missing === 1) {
            $query->where(function ($qq) {
                $qq->whereDoesntHave('teacherProfile')
                   ->orWhereHas('teacherProfile', function ($p) {
                       $p->where(function ($w) {
                           $w->whereNull('profile_photo_path')->orWhere('profile_photo_path', '');
                       })->orWhere(function ($w) {
                           $w->whereNull('id_document_path')->orWhere('id_document_path', '');
                       })->orWhere(function ($w) {
                           $w->whereNull('teaching_permit_path')->orWhere('teaching_permit_path', '');
                       });
                   });
            });
        }

        $teachers = $query
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.teachers.index', [
            'teachers' => $teachers,
            'status'   => $status,
        ]);
    }

    /**
     * عرض صفحة تفاصيل المعلّم للأدمن
     * ✅ المرحلة 6: تحميل سجل القرارات (آخر 50) + بيانات الأدمن
     */
    public function show(User $teacher)
    {
        if ($teacher->role !== 'teacher') {
            abort(404);
        }

        $teacher->load('teacherProfile');

        // ✅ اجلب سجل القرارات (مرن مع اختلاف أسماء الأعمدة)
        $auditLogs = TeacherAuditLog::query()
            ->where(function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)
                ->orWhere('teacher_user_id', $teacher->id)
                ->orWhere('user_id', $teacher->id);
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.teachers.show', compact('teacher', 'auditLogs'));
    }


    /**
     * ✅ قبول/تفعيل المعلّم
     * مسموح فقط إذا:
     * - profile موجود
     * - submitted_at موجود (تم الإرسال)
     * - الحالة الحالية pending
     */
    public function approve(Request $request, User $teacher)
    {
        if ($teacher->role !== 'teacher') {
            abort(404);
        }

        $teacher->load('teacherProfile');

        if (!$this->canReview($teacher)) {
            return back()->with('error', $this->reviewBlockMessage($teacher));
        }

        $request->validate([
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $this->applyStatus($teacher, TeacherProfile::STATUS_APPROVED, [
            'admin_note'       => $request->input('admin_note'),
            'rejection_reason' => null,
        ]);

        return back()->with('success', '✅ تم قبول/تفعيل المعلّم بنجاح.');
    }

    /**
     * ✅ رفض المعلّم
     * مسموح فقط إذا:
     * - profile موجود
     * - submitted_at موجود (تم الإرسال)
     * - الحالة الحالية pending
     */
    public function reject(Request $request, User $teacher)
    {
        if ($teacher->role !== 'teacher') {
            abort(404);
        }

        $teacher->load('teacherProfile');

        if (!$this->canReview($teacher)) {
            return back()->with('error', $this->reviewBlockMessage($teacher));
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:2000',
            'admin_note'       => 'nullable|string|max:2000',
        ]);

        $this->applyStatus($teacher, TeacherProfile::STATUS_REJECTED, [
            'rejection_reason' => $request->input('rejection_reason'),
            'admin_note'       => $request->input('admin_note'),
        ]);

        return back()->with('success', '⛔ تم رفض المعلّم بنجاح.');
    }

    /**
     * تحديث حالة حساب المعلّم (pending / approved / rejected)
     * ✅ في مشروعك: ما دام قرار قبول/رفض مرتبط بمرحلة "تم الإرسال"
     * نحمي المسار ده بنفس القاعدة:
     * - لا يمكن تغيير الحالة إلا لو submitted + pending
     */
    public function updateStatus(Request $request, User $teacher)
    {
        if ($teacher->role !== 'teacher') {
            abort(404);
        }

        $teacher->load('teacherProfile');

        if (!$this->canReview($teacher)) {
            return back()->with('error', $this->reviewBlockMessage($teacher));
        }

        $request->validate([
            'account_status'   => 'required|in:pending,approved,rejected',
            'rejection_reason' => 'nullable|string|max:2000',
            'admin_note'       => 'nullable|string|max:2000',
        ]);

        $newStatus = $request->input('account_status');

        $payload = [
            'rejection_reason' => $request->input('rejection_reason'),
            'admin_note'       => $request->input('admin_note'),
        ];

        if ($newStatus === TeacherProfile::STATUS_APPROVED) {
            $payload['rejection_reason'] = null;
        }

        $this->applyStatus($teacher, $newStatus, $payload);

        return back()->with('success', 'تم تحديث حالة حساب المعلّم بنجاح.');
    }

    /**
     * ✅ تطبيق الحالة على users + teacher_profiles
     * ✅ المرحلة 6: إنشاء Audit Log
     */
    private function applyStatus(User $teacher, string $status, array $extra = []): void
    {
        // الحالة القديمة (قبل التغيير)
        $fromStatus = $teacher->teacher_status ?? null;

        // 1) users (المصدر الأساسي عندك)
        $teacher->teacher_status = $status;
        $teacher->save();

        // 2) profile
        $profile = $teacher->teacherProfile ?? new TeacherProfile([
            'user_id' => $teacher->id,
        ]);

        $profile->account_status = $status;

        if (array_key_exists('rejection_reason', $extra)) {
            $profile->rejection_reason = $extra['rejection_reason'];
        }

        if (array_key_exists('admin_note', $extra)) {
            $profile->admin_note = $extra['admin_note'];
        }

        $profile->save();

        // 3) ✅ Audit Log
        $action = 'status_changed';
        if ($status === TeacherProfile::STATUS_APPROVED) {
            $action = 'approved';
        } elseif ($status === TeacherProfile::STATUS_REJECTED) {
            $action = 'rejected';
        }

        TeacherAuditLog::create([
            'teacher_id'       => $teacher->id,
            'admin_id'         => Auth::id(),
            'action'           => $action,
            'from_status'      => $fromStatus,
            'to_status'        => $status,
            'rejection_reason' => $extra['rejection_reason'] ?? null,
            'admin_note'       => $extra['admin_note'] ?? null,
            'ip'               => request()->ip(),
            'user_agent'       => substr((string) request()->userAgent(), 0, 1000),
        ]);
    }

    /**
     * ✅ الشرط الحقيقي للمراجعة (Server-side)
     * - لازم يكون فيه teacherProfile
     * - submitted_at موجود
     * - الحالة pending
     */
    private function canReview(User $teacher): bool
    {
        $profile = $teacher->teacherProfile;

        if (!$profile) {
            return false;
        }

        $status = $teacher->teacher_status ?? $profile->account_status ?? 'pending';

        return !empty($profile->submitted_at) && $status === TeacherProfile::STATUS_PENDING;
    }

    /**
     * رسالة توضيحية لو المراجعة غير مسموحة
     */
    private function reviewBlockMessage(User $teacher): string
    {
        $profile = $teacher->teacherProfile;

        if (!$profile) {
            return 'لا يمكن تنفيذ الإجراء: لا يوجد ملف تعريف للمعلّم بعد.';
        }

        $status = $teacher->teacher_status ?? $profile->account_status ?? 'pending';

        if (empty($profile->submitted_at)) {
            return 'لا يمكن تنفيذ الإجراء: الملف ما زال "مسودة" ولم يتم إرساله للمراجعة.';
        }

        if ($status !== TeacherProfile::STATUS_PENDING) {
            return 'لا يمكن تنفيذ الإجراء: تم اتخاذ قرار بالفعل على هذا الحساب (مقبول/مرفوض).';
        }

        return 'لا يمكن تنفيذ الإجراء حاليًا.';
    }
}
