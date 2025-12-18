<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewBookingForTeacher extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking)
    {
    }

    public function via($notifiable)
    {
        // ✅ إشعار داخل الموقع (Database)
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $studentName = $this->booking->student_name ?? null;

        // لو عندك علاقة student في Booking (اختياري)
        if (!$studentName && method_exists($this->booking, 'student') && $this->booking->relationLoaded('student')) {
            $studentName = optional($this->booking->student)->name;
        }

        return [
            'type'        => 'new_booking',
            'booking_id'  => $this->booking->id,
            'student_id'  => $this->booking->user_id ?? null,
            'student_name'=> $studentName ?? 'طالب',
            'subject'     => $this->booking->subject ?? null,
            'status'      => $this->booking->status ?? null,
            'booking_date'=> $this->booking->booking_date ?? $this->booking->first_lesson_at ?? null,
            'title'       => 'لديك حجز جديد',
            'message'     => 'تم إنشاء حجز جديد ويحتاج مراجعة/تأكيد.',
        ];
    }
}
