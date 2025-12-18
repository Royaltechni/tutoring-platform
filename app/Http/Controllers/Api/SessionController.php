<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = LessonSession::where('user_id', $request->user()->id)
            ->with(['teacherProfile.user'])
            ->orderBy('scheduled_start_at', 'desc')
            ->paginate(10);

        return response()->json($sessions);
    }

    public function show(Request $request, $id)
    {
        $session = LessonSession::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['teacherProfile.user', 'rating'])
            ->firstOrFail();

        return response()->json($session);
    }
}
