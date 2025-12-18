<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAuditLog extends Model
{
    protected $fillable = [
        'teacher_id',
        'admin_id',
        'action',
        'from_status',
        'to_status',
        'rejection_reason',
        'admin_note',
        'ip',
        'user_agent',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
