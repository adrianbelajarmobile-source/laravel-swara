<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Reward;

class RedeemHistory extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'reward_id',
        'quantity',
        'points_used',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'points_used' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }
}
