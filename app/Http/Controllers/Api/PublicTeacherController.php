<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class PublicTeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = TeacherProfile::query()
            ->with(['user', 'subjects', 'cities', 'deliveryModes'])
            ->where('onboarding_status', 'approved');

        if ($request->has('subject_id')) {
            $query->whereHas('subjects', function (Builder $q) use ($request) {
                $q->where('subjects.id', $request->subject_id);
            });
        }

        if ($request->has('city_id')) {
            $query->whereHas('cities', function (Builder $q) use ($request) {
                $q->where('cities.id', $request->city_id);
            });
        }

        if ($request->has('delivery_mode_id')) {
            $query->whereHas('deliveryModes', function (Builder $q) use ($request) {
                $q->where('lesson_delivery_modes.id', $request->delivery_mode_id);

                if ($request->has('max_price')) {
                    $q->where('teacher_delivery_modes.price_per_hour', '<=', $request->max_price);
                }
            });
        }

        return response()->json(
            $query->paginate(20)
        );
    }

    public function show($id)
    {
        $teacher = TeacherProfile::with(['user', 'subjects', 'cities', 'deliveryModes'])
            ->where('onboarding_status', 'approved')
            ->findOrFail($id);

        return response()->json($teacher);
    }
}
