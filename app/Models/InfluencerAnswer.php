<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class InfluencerAnswer extends Model
{
    protected $fillable = [
        'application_id',
        'question_id',
        'answer'
    ];

    public function application()
    {
        return $this->belongsTo(RoleUpgradeRequests::class);
    }

    public function question()
    {
        return $this->belongsTo(InfluencerQuestion::class);
    }
}
