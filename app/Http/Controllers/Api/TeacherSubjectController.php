<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TeacherSubjectController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->teacherProfile->subjects
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $profile = $request->user()->teacherProfile;

        $profile->subjects()->syncWithoutDetaching($request->subject_ids);

        return response()->json([
            'message' => 'Subjects updated',
            'subjects' => $profile->subjects,
        ]);
    }

    public function destroy(Request $request, string $subjectId)
    {
        $request->user()->teacherProfile->subjects()->detach($subjectId);

        return response()->json(['message' => 'Subject removed']);
    }
}
