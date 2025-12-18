<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';

    protected $fillable = [
        'code',     // AE, SA, EG ...
        'name_ar',  // الاسم بالعربي
        'name_en',  // الاسم بالإنجليزي
    ];

    public function cities()
    {
        return $this->hasMany(City::class, 'country_id');
    }
}
