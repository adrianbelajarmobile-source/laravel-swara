<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tps extends Model
{
    protected $table = 'tps';

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'accepted_waste_types',
        'open_time',
        'close_time',
        'contact_phone',
        'contact_social_media',
    ];

    protected $casts = [
        'accepted_waste_types' => 'array',
    ];

    public function getOpenTimeAttribute($value)
    {
        return $this->formatTimeToHourMinute($value);
    }

    public function getCloseTimeAttribute($value)
    {
        return $this->formatTimeToHourMinute($value);
    }

    private function formatTimeToHourMinute($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 5);
    }
}
