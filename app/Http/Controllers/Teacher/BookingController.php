<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingStatusUpdatedForStudent;
use App\Services\MeetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BookingController extends Controller
{
    /**
     * قائمة الحجوزات الخاصة بالمعلّم الحالي
     */
    public function index(Request $request)
    {
        $teacherId = Auth::id();

        // فلتر الحالة من الـ URL (pending / confirmed / cancelled)
        $status = $request->query('status', '');

        // ✅ نجيب حجوزات المعلّم عن طريق teacher_profile.user_id
        $query = Booking::with(['teacherProfile', 'student'])
            ->whereHas('teacherProfile', function ($q) use ($teacherId) {
                $q->where('user_id', $teacherId);
            })
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(10)->withQueryString();

        return view('teacher.bookings.index', compact('bookings', 'status'));
    }

    /**
     * عرض تفاصيل حجز واحد للمعلّم
     */
    public function show(Booking $booking)
    {
        $this->authorizeBooking($booking);

        // نحمل العلاقات اللي نحتاجها
        $booking->load(['teacherProfile', 'student']);

        // ✅ مين آخر شخص غيّر الحالة (لو موجود)
        $statusUpdater = null;
        if (!empty($booking->status_updated_by)) {
            $statusUpdater = User::find($booking->status_updated_by);
        }

        // ✅ قراءة المرفقات من جدول booking_attachments
        $attachments = DB::table('booking_attachments')
            ->where('booking_id', $booking->id)
            ->get();

        return view('teacher.bookings.show', compact('booking', 'attachments', 'statusUpdater'));
    }

    /**
     * تحديث حالة الحجز (تأكيد / إلغاء)
     * ✅ Batch 1: عند التأكيد ننشئ Meeting، وعند الإلغاء نقفل Meeting إن وجد
     */
    public function updateStatus(Request $request, Booking $booking, MeetingService $meetingService)
    {
        $this->authorizeBooking($booking);

        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        $oldStatus = $booking->status;
        $newStatus = $data['status'];

        if ($oldStatus === $newStatus) {
            return back()->with('success', 'الحالة بالفعل: ' . $newStatus);
        }

        // ✅ تحديث الحالة + سجل مين/إمتى/المصدر
        $booking->status = $newStatus;

        if ($this->hasBookingColumn('status_updated_by')) {
            $booking->status_updated_by = Auth::id();
        }
        if ($this->hasBookingColumn('status_updated_at')) {
            $booking->status_updated_at = now();
        }
        if ($this->hasBookingColumn('status_updated_source')) {
            $booking->status_updated_source = 'teacher';
        }

        $booking->save();

        // ✅ Batch 1: تجهيز Meeting عند التأكيد
        if ($newStatus === 'confirmed') {
            try {
                // Batch 1: Meeting داخلي + حساب التوقيت
                $meetingService->ensureMeetingForBooking($booking);
            } catch (\Throwable $e) {
                // لا نكسر العملية بسبب الاجتماع
            }

            // ✅ Batch 2: تجهيز Zoom meeting (مرة واحدة)
            try {
                app(\App\Services\Zoom\ZoomMeetingProvisioner::class)
                    ->ensureZoomMeetingForBooking($booking);
            } catch (\Throwable $e) {
                // لا نكسر تأكيد الحجز بسبب Zoom
            }
        }


        // ✅ Batch 1: قفل Meeting عند الإلغاء
        if ($newStatus === 'cancelled') {
            try {
                // لو الـ relation meeting موجودة بعد ما نضيفها في Booking model (خطوة لاحقة)
                if (method_exists($booking, 'meeting')) {
                    $booking->loadMissing(['meeting']);
                    if ($booking->meeting) {
                        $booking->meeting->status = 'cancelled';
                        $booking->meeting->actual_ended_at = now();
                        // نقفل النافذة فورًا
                        $booking->meeting->allow_join_until = now();
                        $booking->meeting->save();
                    }
                }
            } catch (\Throwable $e) {
                // لا نكسر العملية بسبب الاجتماع
            }
        }

        // ✅ إشعار للطالب عند تغيير الحالة
        $this->notifyStudentStatusChanged($booking, $newStatus);

        return back()->with('success', 'تم تحديث حالة الحجز بنجاح.');
    }

    /**
     * ✅ قبول طلب الإلغاء من الطالب
     * - يشترط أن الحجز confirmed
     * - cancel_request_status = pending (لو العمود موجود)
     * - ثم يلغي الحجز فعليًا + يسجل قرار الطلب
     * ✅ Batch 1: قفل Meeting إن وجد
     */
    public function approveCancelRequest(Request $request, Booking $booking, MeetingService $meetingService)
    {
        $this->authorizeBooking($booking);

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'لا يمكن قبول طلب الإلغاء إلا إذا كان الحجز مؤكد.');
        }

        // لو الأعمدة موجودة لازم الطلب يكون pending
        if ($this->hasBookingColumn('cancel_request_status')) {
            if (($booking->cancel_request_status ?? null) !== 'pending') {
                return back()->with('error', 'لا يوجد طلب إلغاء قيد المراجعة لهذا الحجز.');
            }
        }

        // ✅ note اختياري (هنخزّنه فقط لو العمود موجود)
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $updates = [];

        // ✅ الحالة النهائية: cancelled
        $updates['status'] = 'cancelled';

        // سجلات عامة للحالة
        if ($this->hasBookingColumn('status_updated_by')) {
            $updates['status_updated_by'] = Auth::id();
        }
        if ($this->hasBookingColumn('status_updated_at')) {
            $updates['status_updated_at'] = now();
        }
        if ($this->hasBookingColumn('status_updated_source')) {
            $updates['status_updated_source'] = 'teacher_cancel_request_approved';
        }

        // سجل قرار طلب الإلغاء (لو الأعمدة موجودة)
        if ($this->hasBookingColumn('cancel_request_status')) {
            $updates['cancel_request_status'] = 'approved';
        }

        // دعم أسماء أعمدة مختلفة حسب اللي عندك
        if ($this->hasBookingColumn('cancel_decided_at')) {
            $updates['cancel_decided_at'] = now();
        }
        if ($this->hasBookingColumn('cancel_decided_by')) {
            $updates['cancel_decided_by'] = Auth::id();
        }

        if ($this->hasBookingColumn('cancel_handled_at')) {
            $updates['cancel_handled_at'] = now();
        }
        if ($this->hasBookingColumn('cancel_handled_by')) {
            $updates['cancel_handled_by'] = Auth::id();
        }

        if ($this->hasBookingColumn('cancel_handle_note')) {
            $updates['cancel_handle_note'] = $data['note'] ?? null;
        }

        $booking->fill($updates);
        $booking->save();

        // ✅ Batch 1: قفل Meeting إن وجد
        try {
            if (method_exists($booking, 'meeting')) {
                $booking->loadMissing(['meeting']);
                if ($booking->meeting) {
                    $booking->meeting->status = 'cancelled';
                    $booking->meeting->actual_ended_at = now();
                    $booking->meeting->allow_join_until = now();
                    $booking->meeting->save();
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // ✅ إشعار الطالب (status changed)
        $this->notifyStudentStatusChanged($booking, 'cancelled');

        return back()->with('success', 'تم قبول طلب الإلغاء وإلغاء الحجز بنجاح.');
    }

    /**
     * ✅ رفض طلب الإلغاء من الطالب
     * - يشترط confirmed
     * - cancel_request_status = pending (لو العمود موجود)
     * - لا يغيّر status (يبقى confirmed)
     */
    public function rejectCancelRequest(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'لا يمكن رفض طلب الإلغاء إلا إذا كان الحجز مؤكد.');
        }

        if ($this->hasBookingColumn('cancel_request_status')) {
            if (($booking->cancel_request_status ?? null) !== 'pending') {
                return back()->with('error', 'لا يوجد طلب إلغاء قيد المراجعة لهذا الحجز.');
            }
        }

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $updates = [];

        // قرار الطلب فقط (بدون تغيير status)
        if ($this->hasBookingColumn('cancel_request_status')) {
            $updates['cancel_request_status'] = 'rejected';
        }

        // دعم أسماء أعمدة مختلفة حسب اللي عندك
        if ($this->hasBookingColumn('cancel_decided_at')) {
            $updates['cancel_decided_at'] = now();
        }
        if ($this->hasBookingColumn('cancel_decided_by')) {
            $updates['cancel_decided_by'] = Auth::id();
        }

        if ($this->hasBookingColumn('cancel_handled_at')) {
            $updates['cancel_handled_at'] = now();
        }
        if ($this->hasBookingColumn('cancel_handled_by')) {
            $updates['cancel_handled_by'] = Auth::id();
        }

        if ($this->hasBookingColumn('cancel_handle_note')) {
            $updates['cancel_handle_note'] = $data['note'] ?? null;
        }

        // نسجل مصدر التحديث (بدون تغيير status)
        if ($this->hasBookingColumn('status_updated_by')) {
            $updates['status_updated_by'] = Auth::id();
        }
        if ($this->hasBookingColumn('status_updated_at')) {
            $updates['status_updated_at'] = now();
        }
        if ($this->hasBookingColumn('status_updated_source')) {
            $updates['status_updated_source'] = 'teacher_cancel_request_rejected';
        }

        $booking->fill($updates);
        $booking->save();

        // لا نرسل Notification تغيير حالة لأن status لم يتغير فعليًا
        return back()->with('success', 'تم رفض طلب الإلغاء بنجاح (الحجز ما زال مؤكد).');
    }

    /**
     * التأكد أن الحجز يخص هذا المعلّم فعلًا
     */
    protected function authorizeBooking(Booking $booking)
    {
        $teacherId = Auth::id();

        // ✅ مهم جدًا: نضمن تحميل teacherProfile قبل الفحص
        $booking->loadMissing(['teacherProfile']);

        $bookingTeacherUserId = optional($booking->teacherProfile)->user_id;

        if ($bookingTeacherUserId !== $teacherId) {
            abort(403, 'غير مسموح لك بعرض هذا الحجز.');
        }
    }

    /**
     * ✅ Helper: إرسال إشعار للطالب عند تغيير status
     */
    protected function notifyStudentStatusChanged(Booking $booking, string $newStatus): void
    {
        try {
            $booking->loadMissing(['student']);

            $studentUser = $booking->student;

            // fallback لو student relation مش راجعة لأي سبب
            if (!$studentUser && !empty($booking->user_id)) {
                $studentUser = User::find($booking->user_id);
            }

            if ($studentUser) {
                $studentUser->notify(new BookingStatusUpdatedForStudent($booking, $newStatus));
            }
        } catch (\Throwable $e) {
            // ما نكسرش العملية بسبب الإشعارات
        }
    }

    /**
     * ✅ Helper: هل عمود موجود في جدول bookings؟
     * (علشان ما يحصلش Error لو الأعمدة لسه مش معمولها Migration)
     */
    protected function hasBookingColumn(string $column): bool
    {
        try {
            return Schema::hasColumn('bookings', $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
