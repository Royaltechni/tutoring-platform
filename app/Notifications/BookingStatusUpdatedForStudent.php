<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingStatusUpdatedForStudent extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking, public string $newStatus)
    {
    }

    public function via($notifiable)
    {
        // ✅ إشعار داخل الموقع (Database)
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $statusAr = match ($this->newStatus) {
            'confirmed' => 'مؤكَّد',
            'cancelled' => 'ملغي',
            default     => $this->newStatus,
        };

        $title = 'تم تحديث حالة الحجز';
        $message = "تم تحديث حالة حجزك رقم #{$this->booking->id} إلى: {$statusAr}.";

        return [
            'type'        => 'booking_status_updated',
            'booking_id'  => $this->booking->id,
            'teacher_id'  => $this->booking->teacher_id ?? null,
            'status'      => $this->newStatus,
            'title'       => $title,
            'message'     => $message,
            'booking_date'=> $this->booking->booking_date ?? $this->booking->first_lesson_at ?? null,
        ];
    }
}
