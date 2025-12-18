<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonDeliveryMode extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function teacherDeliveryModes()
    {
        return $this->hasMany(TeacherDeliveryMode::class);
    }
}
