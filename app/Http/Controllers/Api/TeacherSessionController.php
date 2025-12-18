<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use Illuminate\Http\Request;

class TeacherSessionController extends Controller
{
    public function index(Request $request)
    {
        $teacherId = $request->user()->teacherProfile->id;

        $sessions = LessonSession::where('teacher_profile_id', $teacherId)
            ->with('user')
            ->orderBy('scheduled_start_at')
            ->paginate(15);

        return response()->json($sessions);
    }

    public function show(Request $request, $id)
    {
        $teacherId = $request->user()->teacherProfile->id;

        $session = LessonSession::where('id', $id)
            ->where('teacher_profile_id', $teacherId)
            ->with(['booking', 'user'])
            ->firstOrFail();

        return response()->json($session);
    }

    public function start(Request $request, $id)
    {
        $teacherId = $request->user()->teacherProfile->id;

        $session = LessonSession::where('id', $id)
            ->where('teacher_profile_id', $teacherId)
            ->firstOrFail();

        if ($session->status !== 'scheduled') {
            return response()->json(['error' => 'Session can only start if scheduled.'], 400);
        }

        $session->update([
            'status' => 'in_progress',
            'actual_start_at' => now(),
        ]);

        return response()->json(['message' => 'Session started', 'session' => $session]);
    }

    public function complete(Request $request, $id)
    {
        $request->validate([
            'teacher_notes' => 'nullable|string',
        ]);

        $teacherId = $request->user()->teacherProfile->id;

        $session = LessonSession::where('id', $id)
            ->where('teacher_profile_id', $teacherId)
            ->firstOrFail();

        if (in_array($session->status, ['completed', 'cancelled'])) {
            return response()->json(['error' => 'Session is already finished.'], 400);
        }

        $session->update([
            'status' => 'completed',
            'actual_end_at' => now(),
            'teacher_notes' => $request->teacher_notes,
        ]);

        return response()->json(['message' => 'Session completed', 'session' => $session]);
    }
}
