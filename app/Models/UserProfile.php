<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'nik',
        'date_of_birth',
        'gender',
        'latitude',
        'longitude',
        'photo_profile',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
