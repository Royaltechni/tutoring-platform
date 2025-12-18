<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherProfile extends Model
{
    use HasFactory;

    // ✅ الحالات المتاحة لحساب المعلّم
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $table = 'teacher_profiles';

    protected $fillable = [
        'user_id',
        'headline',
        'bio',
        'intro_video_url',

        // legacy (اختياري)
        'country',
        'city',

        // onsite الجديدة
        'country_id',
        'onsite_city_ids',

        'main_subject',
        'experience_years',
        'teaching_style',
        'teaches_online',
        'teaches_onsite',
        'hourly_rate_online',
        'half_hour_rate_online',
        'hourly_rate_onsite',
        'half_hour_rate_onsite',

        // ✅ الأسماء الصح حسب DB
        'min_grade',
        'max_grade',
        'curricula',

        // ✅ فاصل الراحة بين الحصص بالدقائق
        'break_minutes',

        // تفاصيل إضافية
        'subjects',
        'languages',
        'cancel_policy',
        'availability',

        'profile_photo_path',
        'id_document_path',
        'teaching_permit_path',

        'account_status',

        // ✅ وقت الإرسال للمراجعة
        'submitted_at',

        // ✅ meta (JSON) — ملاحظات الأدمن + سبب الرفض
        'meta',
    ];

    protected $casts = [
        'teaches_online'   => 'boolean',
        'teaches_onsite'   => 'boolean',
        'experience_years' => 'integer',

        'min_grade'        => 'integer',
        'max_grade'        => 'integer',

        'break_minutes'    => 'integer',

        // JSON → Array
        'curricula'        => 'array',
        'onsite_city_ids'  => 'array',
        'availability'     => 'array',

        // meta JSON
        'meta'             => 'array',

        // ✅ تاريخ الإرسال
        'submitted_at'     => 'datetime',
    ];

    /* =========================================================
     | Mutators / Accessors
     |=========================================================*/

    /**
     * تنظيف onsite_city_ids قبل الحفظ
     */
    public function setOnsiteCityIdsAttribute($value): void
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }

        $arr = is_array($value) ? $value : [];
        $arr = array_map(fn ($v) => (int) $v, $arr);
        $arr = array_filter($arr, fn ($v) => $v > 0);
        $arr = array_unique($arr);

        $this->attributes['onsite_city_ids'] = json_encode(array_values($arr));
    }

    /**
     * تنظيف curricula قبل الحفظ
     */
    public function setCurriculaAttribute($value): void
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_map('trim', explode(',', $value));
            }
        }

        $arr = is_array($value) ? $value : [];
        $arr = array_filter(array_map('trim', $arr));
        $arr = array_unique($arr);

        $this->attributes['curricula'] = json_encode(array_values($arr));
    }

    /**
     * تنظيف availability قبل الحفظ
     */
    public function setAvailabilityAttribute($value): void
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }

        $arr = is_array($value) ? $value : [];
        $this->attributes['availability'] = json_encode($arr);
    }

    /* =========================================================
     | Meta helpers (Admin note / Rejection reason)
     |=========================================================*/

    private function metaArray(): array
    {
        return is_array($this->meta) ? $this->meta : [];
    }

    public function getAdminNoteAttribute(): ?string
    {
        $m = $this->metaArray();
        return !empty($m['admin_note']) ? trim($m['admin_note']) : null;
    }

    public function setAdminNoteAttribute($value): void
    {
        $m = $this->metaArray();
        $v = is_string($value) ? trim($value) : null;

        if ($v) {
            $m['admin_note'] = $v;
        } else {
            unset($m['admin_note']);
        }

        $this->attributes['meta'] = json_encode($m);
    }

    public function getRejectionReasonAttribute(): ?string
    {
        $m = $this->metaArray();
        return !empty($m['rejection_reason']) ? trim($m['rejection_reason']) : null;
    }

    public function setRejectionReasonAttribute($value): void
    {
        $m = $this->metaArray();
        $v = is_string($value) ? trim($value) : null;

        if ($v) {
            $m['rejection_reason'] = $v;
        } else {
            unset($m['rejection_reason']);
        }

        $this->attributes['meta'] = json_encode($m);
    }

    /* =========================================================
     | Helpers
     |=========================================================*/

    /**
     * مرحلة الملف: مسودة / تم الإرسال
     */
    public function getStageAttribute(): string
    {
        return $this->submitted_at ? 'submitted' : 'draft';
    }

    public function isSubmitted(): bool
    {
        return !is_null($this->submitted_at);
    }

    public function isApproved(): bool
    {
        return $this->account_status === self::STATUS_APPROVED;
    }

    public function getBreakMinutesValue(): int
    {
        $v = (int) ($this->break_minutes ?? 0);
        return max(0, min(180, $v));
    }

    /* =========================================================
     | Relationships
     |=========================================================*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function onsiteCountry()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function onsiteCities()
    {
        return $this->belongsToMany(
            City::class,
            'teacher_profile_onsite_cities',
            'teacher_profile_id',
            'city_id'
        )->withTimestamps();
    }
}
