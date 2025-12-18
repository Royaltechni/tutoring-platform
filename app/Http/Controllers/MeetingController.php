<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\MeetingService;
use Illuminate\Support\Facades\Auth;

class MeetingController extends Controller
{
    public function room(Booking $booking, MeetingService $meetingService)
    {
        $user = Auth::user();

        $state = $meetingService->joinState($user, $booking);

        if (!$state['allowed']) {
            return view('meetings.blocked', [
                'booking' => $booking,
                'state' => $state,
            ]);
        }

        $meeting = $state['meeting'];

        // ✅ Role for Zoom Meeting SDK:
        // 1 = Host, 0 = Attendee
        $role = 0;

        if ($user?->role === 'admin') {
            $role = 1; // الأدمن نخليه Host للتجارب والإدارة
        } elseif ($user?->role === 'teacher') {
            // المدرّس هو الـ Host لو هو نفس teacher_id الخاص بالحجز
            if ((int)$booking->teacher_id === (int)$user->id) {
                $role = 1;
            }
        }

        return view('meetings.room', [
            'booking'   => $booking,
            'meeting'   => $meeting,
            'serverNow' => $state['server_now'],
            'role'      => $role,
        ]);
    }

    public function heartbeat(Booking $booking, MeetingService $meetingService)
    {
        $user = Auth::user();
        $state = $meetingService->joinState($user, $booking);

        return response()->json([
            'allowed' => (bool)($state['allowed'] ?? false),
            'reason' => $state['reason'] ?? 'unknown',
            'server_now' => now()->toDateTimeString(),
            'opens_at' => isset($state['opens_at']) ? optional($state['opens_at'])->toDateTimeString() : null,
            'closes_at' => isset($state['closes_at']) ? optional($state['closes_at'])->toDateTimeString() : null,
        ]);
    }
}
