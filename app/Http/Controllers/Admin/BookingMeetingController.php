<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\MeetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingMeetingController extends Controller
{
    public function toggleRecording(Booking $booking, Request $request, MeetingService $meetingService)
    {
        $this->authorizeAdmin();
        $booking->loadMissing(['meeting']);

        if (!$booking->meeting) {
            $meetingService->ensureMeetingForBooking($booking);
            $booking->loadMissing(['meeting']);
        }

        $enabled = (bool)($request->input('enabled') ? true : false);
        $meetingService->adminToggleRecording($booking->meeting, Auth::id(), $enabled);

        return back()->with('success', $enabled ? 'تم تفعيل التسجيل (سماح) من الأدمن.' : 'تم إيقاف التسجيل (منع) من الأدمن.');
    }

    public function extend(Booking $booking, Request $request, MeetingService $meetingService)
    {
        $this->authorizeAdmin();
        $data = $request->validate(['minutes' => 'required|integer|min:1|max:180']);

        $booking->loadMissing(['meeting']);
        if (!$booking->meeting) {
            $meetingService->ensureMeetingForBooking($booking);
            $booking->loadMissing(['meeting']);
        }

        $meetingService->adminExtend($booking->meeting, (int)$data['minutes']);

        return back()->with('success', 'تم تمديد نافذة الاجتماع بنجاح.');
    }

    public function forceEnd(Request $request, \App\Models\Booking $booking, \App\Services\MeetingService $meetingService)
    {
    $this->authorizeAdmin();

    $data = $request->validate([
        'reason' => ['nullable', 'string', 'max:255'],
    ]);

    // ✅ تأكد إن الميتينج موجود
    $meeting = $meetingService->ensureMeetingForBooking($booking);

    // ✅ لو منتهي/ملغي بالفعل
    if (in_array($meeting->status, ['ended', 'cancelled'], true)) {
        return back()->with('info', 'الاجتماع منتهي/ملغي بالفعل.');
    }

    // ✅ (1) أنهِ الاجتماع فعليًا في Zoom لو هو Zoom meeting
    try {
        if (($meeting->provider ?? null) === 'zoom' && !empty($meeting->provider_meeting_id)) {
            /** @var \App\Services\Zoom\ZoomHttp $zoom */
            $zoom = app(\App\Services\Zoom\ZoomHttp::class);

            $res = $zoom->put("https://api.zoom.us/v2/meetings/{$meeting->provider_meeting_id}/status", [
                'action' => 'end',
            ]);

            // Zoom ممكن يرجع 204 No Content
            if (!in_array($res->status(), [200, 204], true)) {
                // لو فشل End في Zoom، نكمل DB لكن نبلغ في الرسالة
                $zoomError = $res->body();
            }
        }
    } catch (\Throwable $e) {
        $zoomError = $e->getMessage();
    }

    // ✅ (2) أنهِ الاجتماع في DB (قفل الدخول فورًا)
    $meetingService->adminForceEnd($meeting, auth()->id(), $data['reason'] ?? null);

    // ✅ رسالة نهائية
    if (!empty($zoomError)) {
        return back()->with('warning', 'تم إنهاء الاجتماع من المنصة، لكن حدث خطأ أثناء إنهائه في Zoom: ' . $zoomError);
    }

    return back()->with('success', 'تم إنهاء الاجتماع فورًا وإغلاق الدخول (وإنهاؤه في Zoom).');
    }



    public function updateSettings(Booking $booking, Request $request, MeetingService $meetingService)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'meeting_early_join_minutes' => 'nullable|integer|min:0|max:60',
            'meeting_grace_after_minutes' => 'nullable|integer|min:0|max:60',
            'meeting_duration_minutes' => 'nullable|integer|min:15|max:240',
            'meeting_notes' => 'nullable|string|max:5000',
        ]);

        $booking->fill($data);
        $booking->save();

        // إعادة حساب نافذة الاجتماع
        $meetingService->ensureMeetingForBooking($booking);

        return back()->with('success', 'تم حفظ إعدادات الاجتماع وإعادة حساب نافذة الدخول.');
    }

    protected function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            abort(403);
        }
    }
}
