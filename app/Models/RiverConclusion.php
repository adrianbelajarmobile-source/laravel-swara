<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiverConclusion extends Model
{
    protected $fillable = [
        'river_id',
        'river_path',
        'status',
        'pollution_type',
        'average_urgency',
        'reporter_user_ids',
        'reporters',
        'reporter_count',
    ];

    protected $casts = [
        'average_urgency' => 'float',
        'reporter_user_ids' => 'array',
        'reporters' => 'array',
        'reporter_count' => 'integer',
    ];

    public function river()
    {
        return $this->belongsTo(River::class);
    }
}