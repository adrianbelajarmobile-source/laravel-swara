<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleUpgradeRequests extends Model
{
    protected $table = 'influencer_applications';
    protected $fillable = [
        'user_id',
        'nik',
        'screenshot_path',
        'status',
        'admin_note',
        'reviewed_at',
        'reviewed_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(InfluencerAnswer::class, 'application_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
