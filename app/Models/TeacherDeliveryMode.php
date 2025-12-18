<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherDeliveryMode extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function teacherProfile()
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    public function deliveryMode()
    {
        return $this->belongsTo(LessonDeliveryMode::class, 'lesson_delivery_mode_id');
    }
}
