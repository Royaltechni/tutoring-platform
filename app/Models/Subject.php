<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function teachers()
    {
        return $this->belongsToMany(TeacherProfile::class, 'teacher_subjects');
    }
}
