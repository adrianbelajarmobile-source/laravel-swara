<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\EventParticipant;

class Event extends Model
{
    protected $fillable = [
        'id',
        'title',
        'description',
        'location_name',
        'latitude',
        'longitude',
        'event_date',
        'start_time',
        'end_time',
        'quota',
        'photo_path',
        'certificate_template_image_path',
        'certificate_template_layout',
        'tps_id',
        'point_reward',
        'created_by',
        'approved_by',
        'status',
        'qr_token'
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'certificate_template_layout' => 'array',
    ];
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
    public function participants()
    {
        return $this->hasMany(EventParticipant::class);
    }
    public function tps()
    {
        return $this->belongsTo(Tps::class, 'tps_id', 'id');
    }

    public function media()
    {
        return $this->hasMany(\App\Models\EventMedia::class);
    }

    public function impactReports()
    {
        return $this->hasMany(\App\Models\EventImpactReport::class);
    }

    /**
     * Scope a query to only approved events.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
