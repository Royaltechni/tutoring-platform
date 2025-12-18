<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// =======================
// Admin Controllers
// =======================
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\TeacherProfileController as AdminTeacherProfileController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;

// =======================
// Teacher Controllers
// =======================
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\BookingController as TeacherBookingController;
use App\Http\Controllers\Teacher\ProfileController as TeacherProfileController;
use App\Http\Controllers\Teacher\LocationController as TeacherLocationController;

// =======================
// Student Controllers
// =======================
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\BookingController as StudentBookingController;
use App\Http\Controllers\Student\TeacherController as StudentTeacherController;
use App\Http\Controllers\Student\TeacherAvailabilityController;

// =======================
// Auth Controllers
// =======================
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;

use App\Http\Controllers\NotificationController;

// =======================
// Meeting (Batch 1 + 2)
// =======================
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\Admin\BookingMeetingController;
use App\Http\Controllers\MeetingRoomController;
use App\Http\Controllers\Zoom\SignatureController;

/*
|--------------------------------------------------------------------------
| Home / Redirect
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'teacher') {
            return redirect()->route('teacher.dashboard');
        }

        if ($user->role === 'student') {
            return redirect()->route('student.dashboard');
        }
    }

    return redirect()->route('login');
})->name('home');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {

    Route::get('/login', [LoginController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'login'])
        ->name('login.post');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])
        ->name('register');

    Route::post('/register', [RegisterController::class, 'register'])
        ->name('register.post');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout');

// API Logout
Route::middleware('auth:sanctum')->post('/api/logout', [LogoutController::class, 'logout'])
    ->name('api.logout');

/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.readAll');
});

/*
|--------------------------------------------------------------------------
| Meetings (Batch 1) + Zoom SDK endpoint (Batch 2)
|--------------------------------------------------------------------------
| - meetings.room + heartbeat: للجميع (auth) مع server-side gating
| - zoom.sdk.signature: للجميع (auth) لأن الطالب/المعلم لازم يطلبوا Signature
*/
Route::middleware('auth')->group(function () {

    Route::get('/meetings/{booking}/room', [MeetingController::class, 'room'])
        ->name('meetings.room');

    Route::get('/meetings/{booking}/heartbeat', [MeetingController::class, 'heartbeat'])
        ->middleware('auth');

    
});


Route::middleware(['auth'])->group(function () {
    Route::post('/zoom/signature', [SignatureController::class, 'generate'])
        ->name('zoom.signature');
});


/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        // Bookings
        Route::get('/bookings', [AdminBookingController::class, 'index'])
            ->name('bookings.index');

        Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])
            ->name('bookings.show');

        Route::get('/bookings/{booking}/edit', [AdminBookingController::class, 'edit'])
            ->name('bookings.edit');

        Route::put('/bookings/{booking}', [AdminBookingController::class, 'update'])
            ->name('bookings.update');

        Route::put('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])
            ->name('bookings.updateStatus');

        // Meeting controls (Admin)
        Route::get('/bookings/{booking}/room', [MeetingRoomController::class, 'show'])
            ->name('meetings.room');

        Route::put('/bookings/{booking}/meeting/toggle-recording', [BookingMeetingController::class, 'toggleRecording'])
            ->name('bookings.meeting.toggleRecording');

        Route::put('/bookings/{booking}/meeting/extend', [BookingMeetingController::class, 'extend'])
            ->name('bookings.meeting.extend');

        Route::put('/bookings/{booking}/meeting/force-end', [BookingMeetingController::class, 'forceEnd'])
            ->name('bookings.meeting.forceEnd');

        Route::put('/bookings/{booking}/meeting/settings', [BookingMeetingController::class, 'updateSettings'])
            ->name('bookings.meeting.settings');

        // Students
        Route::get('/students', [AdminStudentController::class, 'index'])
            ->name('students.index');

        // Teachers
        Route::get('/teachers', [AdminTeacherController::class, 'index'])
            ->name('teachers.index');

        Route::get('/teachers/{teacher}', [AdminTeacherController::class, 'show'])
            ->name('teachers.show');

        Route::post('/teachers/{teacher}/status', [AdminTeacherController::class, 'updateStatus'])
            ->name('teachers.updateStatus');

        Route::patch('/teachers/{teacher}/approve', [\App\Http\Controllers\Admin\TeacherController::class, 'approve'])
            ->name('teachers.approve');

        Route::patch('/teachers/{teacher}/reject', [\App\Http\Controllers\Admin\TeacherController::class, 'reject'])
            ->name('teachers.reject');

        Route::get('/teachers/{teacher}/profile/edit', [AdminTeacherProfileController::class, 'editProfile'])
            ->name('teachers.profile.edit');

        Route::put('/teachers/{teacher}/profile', [AdminTeacherProfileController::class, 'updateProfile'])
            ->name('teachers.profile.update');
    });

/*
|--------------------------------------------------------------------------
| Teacher Panel Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {

        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/bookings', [TeacherBookingController::class, 'index'])
            ->name('bookings.index');

        Route::get('/bookings/{booking}', [TeacherBookingController::class, 'show'])
            ->name('bookings.show');

        Route::put('/bookings/{booking}/status', [TeacherBookingController::class, 'updateStatus'])
            ->name('bookings.updateStatus');

        Route::put('/bookings/{booking}/cancel-request/approve', [TeacherBookingController::class, 'approveCancelRequest'])
            ->name('bookings.cancelRequest.approve');

        Route::put('/bookings/{booking}/cancel-request/reject', [TeacherBookingController::class, 'rejectCancelRequest'])
            ->name('bookings.cancelRequest.reject');

        Route::get('/profile/edit', [TeacherProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::put('/profile', [TeacherProfileController::class, 'update'])
            ->name('profile.update');

        Route::get('/locations/cities/{country}', [TeacherLocationController::class, 'cities'])
            ->name('locations.cities');
    });

/*
|--------------------------------------------------------------------------
| Student Panel Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {

        Route::get('/dashboard', [StudentDashboardController::class, 'index'])
            ->name('dashboard');

        // Bookings
        Route::get('/bookings', [StudentBookingController::class, 'index'])
            ->name('bookings.index');

        Route::get('/bookings/create', [StudentBookingController::class, 'create'])
            ->name('bookings.create');

        Route::post('/bookings', [StudentBookingController::class, 'store'])
            ->name('bookings.store');

        Route::get('/bookings/{booking}', [StudentBookingController::class, 'show'])
            ->name('bookings.show');

        Route::post('/bookings/{booking}/cancel', [StudentBookingController::class, 'cancel'])
            ->name('bookings.cancel');

        Route::post('/bookings/{booking}/request-cancel', [StudentBookingController::class, 'requestCancel'])
            ->name('bookings.requestCancel');

        // Teachers
        Route::get('/teachers', [StudentTeacherController::class, 'index'])
            ->name('teachers.index');

        Route::get('/teachers/{teacher}', [StudentTeacherController::class, 'show'])
            ->name('teachers.show');

        // AJAX: Available Slots
        Route::get('/teachers/{teacher}/available-slots', [TeacherAvailabilityController::class, 'availableSlots'])
            ->name('teachers.availableSlots');
    });
