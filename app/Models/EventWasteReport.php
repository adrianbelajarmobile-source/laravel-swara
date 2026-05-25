<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Event;
use App\Models\User;
class EventWasteReport extends Model
{
    protected $fillable = [
        'event_id',
        'total_waste_kg',
        'waste_type',
        'photo_path',
        'reported_by',
        'status'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
