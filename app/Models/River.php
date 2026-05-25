<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RiverConclusion;

class River extends Model
{
    protected $fillable = [
        'name',
        'description',
        'latitude',
        'longitude',
        'condition', // Auto-calculated dari river reports
    ];

    // Kondisi constants
    const CONDITION_NORMAL = 1;
    const CONDITION_WARNING = 2;
    const CONDITION_URGENT = 3;

    protected $casts = [
        'condition' => 'integer',
    ];

    public function reports()
    {
        return $this->hasMany(RiverReport::class);
    }

    public function conclusion()
    {
        return $this->hasOne(RiverConclusion::class);
    }
}
