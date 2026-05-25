<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiverReport extends Model
{
    protected $fillable = [
        'user_id',
        'river_id',
        'description',
        'photo_path',
        'video_path',
        'monitoring_date',
        'reported_by_type',
        'latitude',
        'longitude',
        'urgency',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'monitoring_date' => 'date',
    ];

    public function river()
    {
        return $this->belongsTo(River::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
