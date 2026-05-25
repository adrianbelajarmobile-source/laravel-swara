<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfluencerQuestion extends Model
{
    protected $fillable = ['question', 'is_active'];

    public function answers()
    {
        return $this->hasMany(InfluencerAnswer::class, 'question_id');
    }
}
