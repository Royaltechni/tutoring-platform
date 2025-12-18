<?php

namespace App\Services\Zoom;

use App\Models\Booking;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ZoomMeetingProvisioner
{
    public function __construct(private ZoomHttp $zoom) {}

    public function ensureZoomMeetingForBooking(Booking $booking): void
    {
        // لازم meeting موجود من Batch 1
        if (!method_exists($booking, 'meeting')) return;

        $booking->loadMissing(['meeting', 'teacherProfile', 'student']);

        $meeting = $booking->meeting;
        if (!$meeting) return;

        // لو اتعمل قبل كده، خلاص
        if (($meeting->provider ?? null) === 'zoom' && !empty($meeting->provider_meeting_id) && !empty($meeting->provider_meeting_number)) {
            return;
        }

        // Create Zoom meeting (مرة واحدة) داخل Transaction لتفادي race
        DB::transaction(function () use ($booking, $meeting) {
            $meeting->refresh();

            if (($meeting->provider ?? null) === 'zoom' && !empty($meeting->provider_meeting_id) && !empty($meeting->provider_meeting_number)) {
                return;
            }

            $hostEmail = config('services.zoom.default_host_email');
            if (!$hostEmail) {
                throw new \RuntimeException('ZOOM_DEFAULT_HOST_EMAIL is missing');
            }

            // نجيب Zoom user id للـ host عبر email
            $u = $this->zoom->get('https://api.zoom.us/v2/users/' . urlencode($hostEmail));
            if (!$u->ok()) {
                throw new \RuntimeException('Zoom get user failed: ' . $u->body());
            }
            $zoomUserId = $u->json('id');

            $topic = "Lesson Booking #{$booking->id}";

            // وقت الحصة من meeting (Batch1) أو من booking
            $startAt = $meeting->scheduled_start_at ?? now()->addMinutes(5);
            $duration = (int)($booking->meeting_duration_minutes ?? $booking->duration_minutes ?? 60);

            // إعدادات مهمة:
            // type=2 scheduled
            // waiting_room=true
            // join_before_host=false
            // approval_type=2 no registration
            // auto_recording=cloud (لكن الأفضل قفلها من حساب Zoom كمان)
            $payload = [
                'topic' => $topic,
                'type' => 2,
                'start_time' => $startAt->format('Y-m-d\TH:i:s'),
                'timezone' => 'Asia/Dubai',
                'duration' => $duration,
                'settings' => [
                    'waiting_room' => true,
                    'join_before_host' => false,
                    'approval_type' => 2,
                    'mute_upon_entry' => false,
                    'host_video' => true,
                    'participant_video' => true,
                    'registrants_confirmation_email' => false,
                    'auto_recording' => 'cloud',
                ],
            ];

            $res = $this->zoom->post("https://api.zoom.us/v2/users/{$zoomUserId}/meetings", $payload);

            if (!in_array($res->status(), [200, 201])) {
                throw new \RuntimeException('Zoom create meeting failed: ' . $res->body());
            }

            $zoomMeetingId = (string)$res->json('id');
            $zoomUUID      = (string)($res->json('uuid') ?? '');
            $zoomNumber    = (string)($res->json('id') ?? ''); // غالبًا نفس id رقم
            $passcode      = (string)($res->json('password') ?? '');

            // خزّن بأقل معلومات (بدون join_url)
            $meeting->provider = 'zoom';
            $meeting->provider_meeting_id = $zoomMeetingId;
            $meeting->provider_meeting_uuid = $zoomUUID ?: null;
            $meeting->provider_meeting_number = $zoomNumber ?: null;
            $meeting->provider_passcode = $passcode ? Crypt::encryptString($passcode) : null;
            $meeting->provider_host_user_id = $zoomUserId;
            $meeting->provider_payload = [
                'created_at' => now()->toDateTimeString(),
                'topic' => $topic,
            ];
            $meeting->save();

            // Log (لو تحب نضيف Model لاحقًا، حاليًا DB مباشرة)
            DB::table('meeting_logs')->insert([
                'meeting_id' => $meeting->id,
                'booking_id' => $booking->id,
                'user_id' => auth()->id(),
                'actor_role' => 'teacher',
                'event' => 'zoom_created',
                'meta' => json_encode(['zoom_meeting_id' => $zoomMeetingId]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
