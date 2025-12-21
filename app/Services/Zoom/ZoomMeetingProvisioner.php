<?php

namespace App\Services\Zoom;

use App\Models\Booking;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZoomMeetingProvisioner
{
    public function __construct(private ZoomHttp $zoom) {}

    public function ensureZoomMeetingForBooking(Booking $booking): void
    {
        // 1. التأكد أن علاقة الميتنج موجودة
        if (!method_exists($booking, 'meeting')) return;

        $booking->loadMissing(['meeting', 'teacherProfile', 'student']);

        $meeting = $booking->meeting;
        
        // 2. إذا لم يكن هناك سجل ميتنج داخلي، لا يمكننا إكمال زووم
        if (!$meeting) {
            Log::error("Zoom Provisioner: No internal meeting found for Booking #{$booking->id}");
            return;
        }

        // 3. لو تم إنشاء اجتماع زووم لهذا الحجز سابقاً، لا نكرر العملية
        if (!empty($meeting->provider_meeting_id)) {
            return;
        }

        // 4. بدء العملية داخل Transaction لضمان سلامة البيانات
        DB::transaction(function () use ($booking, $meeting) {
            $meeting->refresh();

            // تأكيد إضافي لمنع التكرار
            if (!empty($meeting->provider_meeting_id)) return;

            // 5. جلب الإيميل الأساسي لحساب زووم من الإعدادات
            $hostEmail = config('services.zoom.default_host_email');
            if (!$hostEmail) {
                Log::error("Zoom Provisioner: ZOOM_DEFAULT_HOST_EMAIL is missing in config/services.php");
                throw new \RuntimeException('ZOOM_DEFAULT_HOST_EMAIL is missing');
            }

            // 6. الحصول على معرف المستخدم (User ID) من زووم باستخدام الإيميل
            $u = $this->zoom->get('https://api.zoom.us/v2/users/' . urlencode($hostEmail));
            if (!$u->ok()) {
                Log::error("Zoom Provisioner: Failed to get user from Zoom. Response: " . $u->body());
                throw new \RuntimeException('Zoom get user failed: ' . $u->body());
            }
            $zoomUserId = $u->json('id');

            // 7. تجهيز بيانات الاجتماع
            $topic = "Lesson Booking #{$booking->id} - " . ($booking->student->name ?? 'Student');
            $startAt = $meeting->scheduled_start_at ?? now()->addMinutes(5);
            $duration = (int)($booking->meeting_duration_minutes ?? $booking->duration_minutes ?? 60);

            $payload = [
                'topic' => $topic,
                'type' => 2, // Scheduled Meeting
                'start_time' => $startAt->format('Y-m-d\TH:i:s'),
                'timezone' => 'Asia/Dubai',
                'duration' => $duration,
                'settings' => [
                    'waiting_room' => true,
                    'join_before_host' => true, // نتركها true للسماح بالدخول السهل
                    'approval_type' => 2,
                    'mute_upon_entry' => false,
                    'host_video' => true,
                    'participant_video' => true,
                    'auto_recording' => 'none', 
                ],
            ];

            // 8. طلب إنشاء الاجتماع من API زووم
            $res = $this->zoom->post("https://api.zoom.us/v2/users/{$zoomUserId}/meetings", $payload);

            if (!in_array($res->status(), [200, 201])) {
                Log::error("Zoom Provisioner: Failed to create meeting. Body: " . $res->body());
                throw new \RuntimeException('Zoom create meeting failed: ' . $res->body());
            }

            $zoomData = $res->json();

            // 9. تخزين البيانات المستلمة في قاعدة البيانات (أهم جزء)
            $meeting->provider = 'zoom';
            $meeting->provider_meeting_id = (string) $zoomData['id'];
            $meeting->provider_meeting_number = (string) $zoomData['id'];
            $meeting->provider_meeting_uuid = (string) ($zoomData['uuid'] ?? '');
            
            // روابط الدخول ( start_url للمعلم و join_url للطالب )
            $meeting->start_url = $zoomData['start_url'] ?? null;
            $meeting->join_url  = $zoomData['join_url'] ?? null;
            
            // تشفير كلمة السر للأمان
            $passcode = (string) ($zoomData['password'] ?? '');
            $meeting->provider_passcode = $passcode ? Crypt::encryptString($passcode) : null;
            
            $meeting->provider_host_user_id = $zoomUserId;
            $meeting->provider_payload = [
                'created_at' => now()->toDateTimeString(),
                'topic' => $topic,
                'zoom_response' => $zoomData // حفظ الرد كامل احتياطاً
            ];

            // حفظ التعديلات في جدول meetings
            $meeting->save();

            // 10. تسجيل اللوج (اختياري)
            DB::table('meeting_logs')->insert([
                'meeting_id' => $meeting->id,
                'booking_id' => $booking->id,
                'user_id' => auth()->id(),
                'actor_role' => 'teacher',
                'event' => 'zoom_created',
                'meta' => json_encode(['zoom_meeting_id' => $zoomData['id']]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}