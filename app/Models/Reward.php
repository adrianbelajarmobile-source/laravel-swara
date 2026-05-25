<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RedeemHistory;

class Reward extends Model
{
    public const CATEGORY_VOUCHER = 'voucher';
    public const CATEGORY_PRODUCT = 'product';

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OUT_OF_STOCK = 'out_of_stock';

    protected $fillable = [
        'name',
        'category',
        'description',
        'points_required',
        'quantity',
        'code',
        'pin',
        'image',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'quantity' => 'integer',
        'code' => 'encrypted',
        'pin' => 'encrypted',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function redeemHistories()
    {
        return $this->hasMany(RedeemHistory::class);
    }

    public function isVoucher(): bool
    {
        return $this->category === self::CATEGORY_VOUCHER;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
