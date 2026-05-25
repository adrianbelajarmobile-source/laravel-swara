<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventImpactReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'reported_by',
        'activity_report',
        'suggestions'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Event relationship
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Reporter (User) relationship
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by')->with('profile:user_id,name,photo_profile');
    }

    /**
     * Scopes
     */
    public function scopeByEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeByReporter($query, $userId)
    {
        return $query->where('reported_by', $userId);
    }
}
