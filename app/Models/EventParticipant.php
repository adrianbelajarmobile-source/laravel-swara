<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    public const CERTIFICATE_NOT_GENERATED = 'not_generated';
    public const CERTIFICATE_PROCESSING = 'processing';
    public const CERTIFICATE_READY = 'ready';
    public const CERTIFICATE_FAILED = 'failed';

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'points_earned',
        'checked_in_at',
        'checked_out_at',
        'certificate_status',
        'certificate_path',
        'certificate_generated_at',
    ];

    protected $casts = [
        'points_earned' => 'integer',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'certificate_generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(EventMedia::class, 'participant_id');
    }

    /**
     * Scopes
     */
    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'checked_in');
    }

    public function scopeCheckedOut($query)
    {
        return $query->where('status', 'checked_out');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Helpers
     */
    public function isCheckedIn()
    {
        return $this->status === 'checked_in' && $this->checked_in_at !== null;
    }

    public function isCheckedOut()
    {
        return $this->status === 'checked_out' && $this->checked_out_at !== null;
    }

    public function getCheckInDurationAttribute()
    {
        if ($this->checked_in_at && $this->checked_out_at) {
            return $this->checked_out_at->diffInMinutes($this->checked_in_at);
        }
        return null;
    }
}

