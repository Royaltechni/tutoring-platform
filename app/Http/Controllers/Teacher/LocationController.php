<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function cities($countryId)
    {
        $cities = City::query()
            ->where('country_id', $countryId)
            ->orderBy('name_en')
            ->get(['id', 'name_ar', 'name_en']);

        return response()->json([
            'cities' => $cities
        ]);
    }
}
