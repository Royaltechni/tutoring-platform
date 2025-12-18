<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ✅ Auth
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ZoomSdkController;

// ✅ General Bookings (Testing)
use App\Http\Controllers\Api\BookingController;

// ✅ Teacher
use App\Http\Controllers\Api\Teacher\BookingController as TeacherBookingController;
use App\Http\Controllers\Api\Teacher\DashboardController as TeacherDashboardController;

// ✅ Student
use App\Http\Controllers\Api\Student\BookingController as StudentBookingController;
use App\Http\Controllers\Api\Student\DashboardController as StudentDashboardController;

// ✅ Admin
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\BookingController as AdminBookingController;


/*
|--------------------------------------------------------------------------
| ✅ Public Routes (بدون تسجيل دخول)
|--------------------------------------------------------------------------
*/

// ✅ اختبار أن الـ API شغالة
Route::get('/ping', function () {
    return response()->json(['message' => 'API is working ✅']);
});

// ✅ تسجيل الدخول
Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| 🔐 Protected Routes (تحتاج Token Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // ✅ Logout (لأي نوع حساب)
    Route::post('/logout', [LogoutController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | ✅ General Bookings (Testing)
    |--------------------------------------------------------------------------
    */
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);

        Route::get('/test', function () {
            return response()->json([
                'message' => 'Bookings index route exists ✅'
            ]);
        });
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/zoom/sdk/signature', [ZoomSdkController::class, 'signature'])
            ->name('zoom.sdk.signature');
    });

    /*
    |--------------------------------------------------------------------------
    | ✅ Teacher Routes
    |--------------------------------------------------------------------------
    */
    Route::get('teacher/bookings', [TeacherBookingController::class, 'index']);
    Route::get('teacher/bookings/{uuid}', [TeacherBookingController::class, 'show']);
    Route::patch('teacher/bookings/{uuid}/status', [TeacherBookingController::class, 'updateStatus']);
    Route::get('teacher/dashboard/summary', [TeacherDashboardController::class, 'summary']);

    /*
    |--------------------------------------------------------------------------
    | ✅ Student Routes
    |--------------------------------------------------------------------------
    */
    Route::get('student/bookings', [StudentBookingController::class, 'index']);
    Route::get('student/bookings/{uuid}', [StudentBookingController::class, 'show']);
    Route::post('student/bookings', [StudentBookingController::class, 'store']);
    Route::patch('student/bookings/{uuid}/status', [StudentBookingController::class, 'updateStatus']);
    Route::get('student/dashboard/summary', [StudentDashboardController::class, 'summary']);


    /*
    |--------------------------------------------------------------------------
    | ✅ Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::get('admin/dashboard/summary', [AdminDashboardController::class, 'summary']);

    Route::get('admin/bookings', [AdminBookingController::class, 'index']);
    Route::get('admin/bookings/{uuid}', [AdminBookingController::class, 'show']);
    Route::patch('admin/bookings/{uuid}/status', [AdminBookingController::class, 'updateStatus']);
});
