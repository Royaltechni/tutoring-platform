<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use App\Models\LessonRating;
use Illuminate\Http\Request;

class SessionRatingController extends Controller
{
    public function store(Request $request, $sessionId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $session = LessonSession::where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($session->status !== 'completed') {
            return response()->json(['error' => 'You can only rate completed sessions.'], 400);
        }

        $rating = LessonRating::updateOrCreate(
            ['lesson_session_id' => $session->id, 'user_id' => $request->user()->id],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]
        );

        return response()->json([
            'message' => 'Rating submitted successfully.',
            'rating' => $rating,
        ]);
    }
}
