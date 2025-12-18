<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Str;

class MeetingService
{
    public function ensureMeetingForBooking(Booking $booking): Meeting
    {
        $meeting = $booking->meeting()->first();

        if (!$meeting) {
            $meeting = new Meeting();
            $meeting->booking_id = $booking->id;
            $meeting->uuid = (string) Str::uuid();
            $meeting->status = 'scheduled';
            $meeting->room_token = Str::random(48);
            $meeting->recording_required = true;
            $meeting->recording_admin_enabled = false;
            $meeting->recording_status = 'disabled';
        }

        $start = $booking->getLessonStartAt();
        $duration = $booking->getLessonDurationMinutes();
        $end = $start ? $start->copy()->addMinutes($duration) : null;

        $early = (int)($booking->meeting_early_join_minutes ?? 10);
        $grace = (int)($booking->meeting_grace_after_minutes ?? 10);

        $meeting->scheduled_start_at = $start;
        $meeting->scheduled_end_at = $end;

        if ($start && $end) {
            $meeting->allow_join_from  = $start->copy()->subMinutes($early);
            $meeting->allow_join_until = $end->copy()->addMinutes($grace);
        }

        $meeting->save();

        return $meeting;
    }

    public function canAccessBookingMeeting(User $user, Booking $booking): bool
    {
        if ($user->role === 'admin') return true;

        if ($user->role === 'student' && (int)$booking->user_id === (int)$user->id) return true;

        if ($user->role === 'teacher') {
            $booking->loadMissing(['teacherProfile']);
            return (int)optional($booking->teacherProfile)->user_id === (int)$user->id;
        }

        return false;
    }

    /**
     * Server-side gating: الوقت + الحالة + forced end + (NEW) الطالب لا يدخل إلا بعد دخول الـ Host
     */
    public function joinState(User $user, Booking $booking): array
    {
        if (!$this->canAccessBookingMeeting($user, $booking)) {
            return ['allowed' => false, 'reason' => 'unauthorized'];
        }

        if (in_array($booking->status, ['cancelled', 'canceled'], true)) {
            return ['allowed' => false, 'reason' => 'booking_cancelled'];
        }

        if ($booking->status !== 'confirmed') {
            return ['allowed' => false, 'reason' => 'booking_not_confirmed'];
        }

        $meeting = $this->ensureMeetingForBooking($booking);

        if ($meeting->status === 'cancelled') {
            return ['allowed' => false, 'reason' => 'meeting_cancelled'];
        }

        if (!empty($meeting->forced_ended_at)) {
            return ['allowed' => false, 'reason' => 'forced_ended'];
        }

        $now = now();

        if ($meeting->allow_join_from && $now->lt($meeting->allow_join_from)) {
            return [
                'allowed' => false,
                'reason' => 'too_early',
                'opens_at' => $meeting->allow_join_from,
                'closes_at' => $meeting->allow_join_until,
                'server_now' => $now,
            ];
        }

        if ($meeting->allow_join_until && $now->gt($meeting->allow_join_until)) {
            return [
                'allowed' => false,
                'reason' => 'too_late',
                'opens_at' => $meeting->allow_join_from,
                'closes_at' => $meeting->allow_join_until,
                'server_now' => $now,
            ];
        }

        // ✅ NEW: الطالب لا يدخل إلا بعد دخول الـ Host (teacher/admin)
        $isHostUser = in_array($user->role, ['admin', 'teacher'], true);
        $isStudentUser = ($user->role === 'student');

        if ($isStudentUser && empty($meeting->host_joined_at)) {
            return [
                'allowed' => false,
                'reason' => 'wait_for_host',
                'opens_at' => $meeting->allow_join_from,
                'closes_at' => $meeting->allow_join_until,
                'server_now' => $now,
            ];
        }

        // ✅ لما الـ Host يدخل لأول مرة: نسجل أنه بدأ الاجتماع
        if ($isHostUser && empty($meeting->host_joined_at)) {
            $meeting->host_joined_at = $now;
            $meeting->host_joined_by_user_id = $user->id;
        }

        // live marker
        if ($meeting->status === 'scheduled') {
            $meeting->status = 'live';
            $meeting->actual_started_at = $meeting->actual_started_at ?: $now;
        }

        $meeting->save();

        return [
            'allowed' => true,
            'reason' => 'ok',
            'meeting' => $meeting,
            'server_now' => $now,
        ];
    }

    public function adminToggleRecording(Meeting $meeting, int $adminId, bool $enabled): void
    {
        $meeting->recording_admin_enabled = $enabled;
        $meeting->recording_enabled_by_admin_id = $enabled ? $adminId : null;
        $meeting->recording_enabled_at = $enabled ? now() : null;
        $meeting->recording_status = $enabled ? 'ready' : 'disabled';
        $meeting->save();
    }

    public function adminExtend(Meeting $meeting, int $minutes): void
    {
        $minutes = max(1, min(180, $minutes));
        if ($meeting->allow_join_until) {
            $meeting->allow_join_until = $meeting->allow_join_until->copy()->addMinutes($minutes);
        }
        if ($meeting->scheduled_end_at) {
            $meeting->scheduled_end_at = $meeting->scheduled_end_at->copy()->addMinutes($minutes);
        }
        $meeting->save();
    }

    public function adminForceEnd(Meeting $meeting, int $adminId, ?string $reason = null): void
    {
        $meeting->forced_ended_by_admin_id = $adminId;
        $meeting->forced_ended_at = now();
        $meeting->forced_end_reason = $reason;
        $meeting->status = 'ended';
        $meeting->actual_ended_at = $meeting->actual_ended_at ?: now();
        $meeting->allow_join_until = now();
        // ✅ reset host flag
        $meeting->host_joined_at = null;
        $meeting->host_joined_by_user_id = null;

        $meeting->save();
    }
}
