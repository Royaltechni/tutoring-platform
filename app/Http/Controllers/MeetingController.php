<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request; // السطر ده هو اللي كان ناقص وبيعمل المشكلة
use Illuminate\Support\Facades\Auth;

// محتاجينه عشان التوقيع

class MeetingController extends Controller
{
    /**
     * عرض غرفة الاجتماع (الصفحة السوداء)
     */
    public function room($bookingId)
    {
        // 1. نجيب الحجز والاجتماع
        $booking = Booking::with('meeting')->findOrFail($bookingId);
        $user    = auth()->user();

        // 2. نتأكد إن الحجز مؤكد وفيه ميتنج اتعمل فعلاً من زووم
        if ($booking->status !== 'confirmed' || ! $booking->meeting || ! $booking->meeting->provider_meeting_id) {
            return back()->with('error', 'الاجتماع غير متاح حالياً. تأكد من قبول الحجز أولاً.');
        }

        $meeting = $booking->meeting;

        // 3. فتح صفحة الـ Blade (الغرفة السوداء)
        return view('meetings.room', compact('meeting', 'booking'));
    }

    /**
     * توليد التوقيع الأمني لزووم (Signature)
     */
  public function generateSignature(Request $request)
{
    try {
        $meetingNumber = str_replace([' ', '-'], '', $request->meetingNumber);
        $role = (int)$request->role; 

        $sdkKey = config('services.zoom.sdk_key');
        $sdkSecret = config('services.zoom.sdk_secret');

        // أهم تعديل: تقليل الوقت لضمان المزامنة مع سيرفرات زووم
        $iat = time() - 120; // دقيقتين للوراء
        $exp = $iat + (60 * 60 * 2);

        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = [
            'sdkKey' => $sdkKey,
            'mn' => (string)$meetingNumber,
            'role' => $role,
            'iat' => $iat,
            'exp' => $exp,
            'tokenExp' => $exp
        ];

        // التشفير اليدوي الأدق
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $sdkSecret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        $token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        return response()->json(['signature' => $token]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    private function generateJwt($header, $payload, $secret)
    {
        // تشفير الـ Header والـ Payload بنظام Base64Url
        $base64UrlHeader  = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        // إنشاء التوقيع الرقمي باستخدام الـ Secret
        $signature          = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
}
