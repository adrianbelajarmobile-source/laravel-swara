<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventMedia extends Model
{
    protected $table = 'event_media';
    
    protected $fillable = [
        'event_id',
        'participant_id',
        'uploaded_by',
        'media_type',
        'file_path',
        'original_name',
        'file_size',
        'description'
    ];

    protected $casts = [
        'file_size' => 'integer',
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

    public function participant()
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scopes
     */
    public function scopePhoto($query)
    {
        return $query->where('media_type', 'photo');
    }

    public function scopeVideo($query)
    {
        return $query->where('media_type', 'video');
    }

    public function scopeByEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeByParticipant($query, $participantId)
    {
        return $query->where('participant_id', $participantId);
    }

    /**
     * Accessors
     */
    public function getFileSizeKbAttribute()
    {
        return $this->file_size ? round($this->file_size / 1024, 2) : null;
    }

    public function getFileSizeMbAttribute()
    {
        return $this->file_size ? round($this->file_size / (1024 * 1024), 2) : null;
    }
}
