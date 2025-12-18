<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
    'booking_id',
    'uuid',
    'status',
    'scheduled_start_at',
    'scheduled_end_at',
    'allow_join_from',
    'allow_join_until',
    'actual_started_at',
    'actual_ended_at',
    'room_token',
    'provider',
    'provider_meeting_id',

    // ✅ Batch 2 (Zoom fields)
    'provider_meeting_uuid',
    'provider_meeting_number',
    'provider_passcode',
    'provider_host_user_id',
    'provider_payload',

    'recording_required',
    'recording_admin_enabled',
    'recording_status',
    'recording_path',
    'recording_enabled_by_admin_id',
    'recording_enabled_at',
    'forced_ended_by_admin_id',
    'forced_ended_at',
    'forced_end_reason',
    ];

    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'allow_join_from' => 'datetime',
        'allow_join_until' => 'datetime',
        'actual_started_at' => 'datetime',
        'actual_ended_at' => 'datetime',
        'recording_required' => 'boolean',
        'recording_admin_enabled' => 'boolean',
        'recording_enabled_at' => 'datetime',
        'forced_ended_at' => 'datetime',

        // ✅ Batch 2
        'provider_payload' => 'array',
    ];


    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
