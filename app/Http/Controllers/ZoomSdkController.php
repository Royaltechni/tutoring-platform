<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\Zoom\ZoomSdkSignatureService;
use App\Services\Zoom\ZoomS2STokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class ZoomSdkController extends Controller
{
    public function signature(Request $request, ZoomSdkSignatureService $signer, ZoomS2STokenService $tokens)
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer'],
        ]);

        $booking = Booking::with(['teacherProfile', 'meeting'])->findOrFail($data['booking_id']);

        // =========================
        // ✅ صلاحيات
        // =========================
        $user = Auth::user();
        $isAdmin   = ($user->role ?? null) === 'admin';
        $isTeacher = ($user->role ?? null) === 'teacher' && optional($booking->teacherProfile)->user_id === $user->id;
        $isStudent = ($user->role ?? null) === 'student' && ($booking->user_id ?? null) === $user->id;

        if (!($isAdmin || $isTeacher || $isStudent)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (($booking->status ?? null) !== 'confirmed') {
            return response()->json(['message' => 'Booking not confirmed'], 403);
        }

        // =========================
        // ✅ Provision Zoom meeting مرة واحدة
        // =========================
        $meeting = $booking->meeting;

        if (!$meeting || ($meeting->provider ?? null) !== 'zoom') {
            try {
                app(\App\Services\Zoom\ZoomMeetingProvisioner::class)
                    ->ensureZoomMeetingForBooking($booking);

                $booking->load('meeting');
                $meeting = $booking->meeting;
            } catch (\Throwable $e) {
                return response()->json([
                    'message' => 'Failed to provision Zoom meeting',
                    'error'   => $e->getMessage(),
                ], 422);
            }
        }

        if (!$meeting || ($meeting->provider ?? null) !== 'zoom') {
            return response()->json(['message' => 'Zoom meeting not provisioned'], 422);
        }

        // =========================
        // ✅ status + time window + forced end
        // =========================
        if (!empty($meeting->forced_ended_at)) {
            return response()->json(['message' => 'Meeting forced ended'], 403);
        }

        $now = now();

        if ($meeting->allow_join_from && $now->lt($meeting->allow_join_from)) {
            return response()->json(['message' => 'Too early'], 403);
        }

        if ($meeting->allow_join_until && $now->gt($meeting->allow_join_until)) {
            return response()->json(['message' => 'Too late'], 403);
        }

        // =========================
        // ✅ Meeting Number + Passcode
        // =========================
        $mn = (string) (
            $meeting->provider_meeting_number
            ?? $meeting->provider_meeting_id
            ?? data_get($meeting->provider_payload, 'meetingNumber')
            ?? data_get($meeting->provider_payload, 'id')
            ?? ''
        );

        if (!$mn) {
            return response()->json(['message' => 'Meeting number missing'], 422);
        }

        $encryptedPass = $meeting->provider_passcode
            ?? data_get($meeting->provider_payload, 'passcode')
            ?? null;

        $pass = '';
        if (!empty($encryptedPass)) {
            try {
                $pass = Crypt::decryptString($encryptedPass);
            } catch (\Throwable $e) {
                $pass = (string) $encryptedPass;
            }
        }

        // =========================
        // ✅ Role (احترافي)
        // الطالب role=0
        // المدرس/الأدمن role=1 (Host داخل المنصّة)
        // =========================
        $role = ($isAdmin || $isTeacher) ? 1 : 0;

        // =========================
        // ✅ SDK Credentials
        // =========================
        $sdkKey    = (string) config('services.zoom_meeting_sdk.key');
        $sdkSecret = (string) config('services.zoom_meeting_sdk.secret');

        if ($request->boolean('debug')) {
            return response()->json([
                'sdkKey_len'    => strlen($sdkKey),
                'sdkSecret_len' => strlen($sdkSecret),
                'sdkKey_head'   => substr($sdkKey, 0, 6),
                'appEnv'        => app()->environment(),
                'role'          => $role,
            ], 200);
        }

        if (!$sdkKey || !$sdkSecret) {
            return response()->json(['message' => 'Zoom Meeting SDK Key missing in config'], 422);
        }

        $signature = $signer->makeSignature($mn, $role);

        // =========================
        // ✅ لو Host: رجّع ZAK لحساب المنصّة
        // =========================
        $zakToken = null;
        if ($role === 1) {
            $hostEmail = (string) config('services.zoom.default_host_email');
            if (!$hostEmail) {
                return response()->json(['message' => 'ZOOM_DEFAULT_HOST_EMAIL is missing'], 422);
            }

            try {
                $zakToken = $tokens->getZakTokenForHostEmail($hostEmail);
            } catch (\Throwable $e) {
                return response()->json([
                    'message' => 'Failed to get ZAK token',
                    'error'   => $e->getMessage(),
                ], 422);
            }
        }

        return response()->json([
            'appKey'        => $sdkKey,
            'sdkKey'        => $sdkKey,
            'signature'     => $signature,
            'meetingNumber' => $mn,
            'password'      => $pass,
            'passcode'      => $pass,
            'userName'      => $user->name ?? 'User',
            'leaveUrl'      => url('/'),
            'role'          => $role,
            'zakToken'      => $zakToken,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');

    }
}
