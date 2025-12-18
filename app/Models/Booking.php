<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'teacher_id',
        'teacher_profile_id',
        'lesson_delivery_mode_id',
        'city_id',

        'subject',
        'grade',
        'curriculum',
        'mode',
        'duration_minutes',
        'lessons_count',
        'first_lesson_at',
        'location',
        'notes',

        'price_per_lesson',
        'total_price',
        'total_amount',
        'currency',
        'payment_status',
        'booking_type',
        'status',

        'booking_date',
        'address',

        // Batch 1 – meeting helpers (لو الميجريشن اتعمل)
        'meeting_early_join_minutes',
        'meeting_grace_after_minutes',
        'meeting_duration_minutes',
        'meeting_notes',
    ];

    protected $casts = [
        // DateTimes
        'booking_date'    => 'datetime',
        'first_lesson_at' => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    /* =========================================================
     | Relations
     |========================================================= */

    // ✅ الطالب (صاحب الحجز)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ✅ Alias باسم student
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ✅ بروفايل المعلّم
    public function teacherProfile()
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    // ✅ Alias باسم teacher (يرجع بروفايل المعلّم)
    public function teacher()
    {
        return $this->teacherProfile();
    }

    // ✅ المدينة
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    // ✅ نوع طريقة الدرس (أونلاين / حضوري)
    public function deliveryMode()
    {
        return $this->belongsTo(LessonDeliveryMode::class, 'lesson_delivery_mode_id');
    }

    // ✅ تاريخ تغيّر الحالة
    public function statusHistories()
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    // =========================
    // ✅ Batch 1: Meeting
    // =========================

    /**
     * الاجتماع المرتبط بالحجز
     */
    public function meeting()
    {
        return $this->hasOne(Meeting::class);
    }

    /* =========================================================
     | Helpers (Server-side time logic)
     |========================================================= */

    /**
     * وقت بداية الحصة (مصدر واحد واضح)
     */
    public function getLessonStartAt(): ?Carbon
    {
        if ($this->first_lesson_at instanceof Carbon) {
            return $this->first_lesson_at;
        }

        if ($this->booking_date instanceof Carbon) {
            return $this->booking_date;
        }

        return null;
    }

    /**
     * مدة الحصة بالدقائق
     * (تستخدم في حساب نافذة الاجتماع)
     */
    public function getLessonDurationMinutes(): int
    {
        if (!empty($this->meeting_duration_minutes)) {
            return (int) $this->meeting_duration_minutes;
        }

        if (!empty($this->duration_minutes)) {
            return (int) $this->duration_minutes;
        }

        return 60; // fallback آمن
    }

    /**
     * وقت نهاية الحصة (بدون تمديد)
     */
    public function getLessonEndAt(): ?Carbon
    {
        $start = $this->getLessonStartAt();
        if (!$start) {
            return null;
        }

        return $start->copy()->addMinutes($this->getLessonDurationMinutes());
    }
}
