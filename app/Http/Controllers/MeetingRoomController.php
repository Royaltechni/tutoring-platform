<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class MeetingRoomController extends Controller
{
    public function show(Booking $booking)
    {
        $user = Auth::user();

        $booking->loadMissing(['teacherProfile', 'student', 'meeting']);

        $isAdmin = ($user->role ?? null) === 'admin';
        $isTeacher = ($user->role ?? null) === 'teacher'
            && optional($booking->teacherProfile)->user_id === $user->id;

        $isStudent = ($user->role ?? null) === 'student'
            && ($booking->user_id ?? null) === $user->id;

        abort_unless($isAdmin || $isTeacher || $isStudent, 403);

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'لا يمكن دخول الاجتماع إلا بعد تأكيد الحجز.');
        }

        if (!$booking->meeting) {
            return back()->with('error', 'لم يتم تجهيز الاجتماع بعد.');
        }

        return view('meetings.room', [
            'booking' => $booking,
            'meeting' => $booking->meeting,
            'serverNow' => now(),
        ]);
    }
}
