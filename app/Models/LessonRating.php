<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonRating extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function session()
    {
        return $this->belongsTo(LessonSession::class, 'lesson_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
