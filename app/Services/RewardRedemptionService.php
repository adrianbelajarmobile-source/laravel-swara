<?php

namespace App\Services;

use App\Models\RedeemHistory;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardRedemptionService
{
    /**
     * Redeem reward points in a single transaction to avoid double-redeem race conditions.
     */
    public function redeem(int $userId, int $rewardId, int $quantity = 1): array
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity redeem minimal 1.'
            ]);
        }

        return DB::transaction(function () use ($userId, $rewardId, $quantity) {
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($userId);

            $reward = Reward::query()
                ->lockForUpdate()
                ->findOrFail($rewardId);

            if ($reward->isExpired()) {
                throw ValidationException::withMessages([
                    'reward' => 'Reward sudah expired.'
                ]);
            }

            if ($reward->status !== Reward::STATUS_AVAILABLE || $reward->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'reward' => 'Reward tidak tersedia atau stok tidak mencukupi.'
                ]);
            }

            $pointsNeeded = $reward->points_required * $quantity;
            if ($user->total_points < $pointsNeeded) {
                throw ValidationException::withMessages([
                    'points' => 'Poin user tidak cukup untuk redeem reward ini.'
                ]);
            }

            $reward->quantity -= $quantity;
            if ($reward->quantity <= 0) {
                $reward->quantity = 0;
                $reward->status = Reward::STATUS_OUT_OF_STOCK;
            }
            $reward->save();

            $user->decrement('total_points', $pointsNeeded);

            $history = RedeemHistory::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'quantity' => $quantity,
                'points_used' => $pointsNeeded,
                'status' => RedeemHistory::STATUS_COMPLETED,
            ]);

            return [
                'history' => $history->fresh(['user', 'reward']),
                'code' => $reward->code,
                'pin' => $reward->pin,
            ];
        }, 3);
    }
}
