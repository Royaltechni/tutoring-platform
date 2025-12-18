<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'preferences'       => 'array',
        'password'          => 'hashed',
    ];
    protected $fillable = [
    'name',
    'email',
    'password',
    'role',   // تأكد إنها موجودة
    ];


    // ✅ العلاقات

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function teacherProfile()
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function lessonSessions()
    {
        return $this->hasMany(LessonSession::class);
    }

    public function lessonRatings()
    {
        return $this->hasMany(LessonRating::class);
    }


    public function teacherAuditLogs()
    {
        return $this->hasMany(\App\Models\TeacherAuditLog::class, 'teacher_id');
    }

}
