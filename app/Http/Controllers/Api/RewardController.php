<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RedeemHistory;
use App\Models\Reward;
use App\Services\RewardRedemptionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RewardController extends Controller
{
    public function index(Request $request)
    {
        $query = Reward::query()
            ->where('status', Reward::STATUS_AVAILABLE)
            ->where('quantity', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $rewards = $query->latest()->paginate((int) $request->get('per_page', 10));

        $data = collect($rewards->items())->map(function (Reward $reward) {
            return [
                'id' => $reward->id,
                'name' => $reward->name,
                'category' => $reward->category,
                'description' => $reward->description,
                'points_required' => $reward->points_required,
                'quantity' => $reward->quantity,
                'image' => $reward->image,
                'status' => $reward->status,
                'expires_at' => $reward->expires_at?->toIso8601String(),
                'is_expired' => $reward->isExpired(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $rewards->currentPage(),
                'per_page' => $rewards->perPage(),
                'total' => $rewards->total(),
                'last_page' => $rewards->lastPage(),
            ]
        ]);
    }

    public function redeem($rewardId, Request $request, RewardRedemptionService $service)
    {
        $validated = $request->validate([
            'quantity' => 'nullable|integer|min:1'
        ]);

        $quantity = $validated['quantity'] ?? 1;

        try {
            $result = $service->redeem($request->user()->id, (int) $rewardId, (int) $quantity);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Redeem gagal',
                'errors' => $e->errors(),
            ], 422);
        }

        /** @var RedeemHistory $history */
        $history = $result['history'];

        return response()->json([
            'success' => true,
            'message' => 'Redeem berhasil',
            'data' => [
                'redeem_id' => $history->id,
                'reward' => [
                    'id' => $history->reward->id,
                    'name' => $history->reward->name,
                    'category' => $history->reward->category,
                    'image' => $history->reward->image,
                    'expires_at' => $history->reward->expires_at?->toIso8601String(),
                    'is_expired' => $history->reward->isExpired(),
                ],
                'quantity' => $history->quantity,
                'points_used' => $history->points_used,
                'status' => $history->status,
                'redeemed_at' => $history->created_at?->toIso8601String(),
                'redemption_code' => $result['code'],
                'pin' => $result['pin'],
            ]
        ], 201);
    }

    public function redeemHistories(Request $request)
    {
        $histories = RedeemHistory::query()
            ->with('reward')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate((int) $request->get('per_page', 10));

        $data = collect($histories->items())->map(function (RedeemHistory $history) {
            return [
                'id' => $history->id,
                'reward' => [
                    'id' => $history->reward?->id,
                    'name' => $history->reward?->name,
                    'category' => $history->reward?->category,
                    'image' => $history->reward?->image,
                    'expires_at' => $history->reward?->expires_at?->toIso8601String(),
                    'is_expired' => $history->reward?->isExpired(),
                ],
                'quantity' => $history->quantity,
                'points_used' => $history->points_used,
                'status' => $history->status,
                'created_at' => $history->created_at?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $histories->currentPage(),
                'per_page' => $histories->perPage(),
                'total' => $histories->total(),
                'last_page' => $histories->lastPage(),
            ]
        ]);
    }
}
