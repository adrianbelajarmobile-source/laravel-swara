<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RewardController extends Controller
{
    public function index(Request $request)
    {
        $query = Reward::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $rewards = $query->latest()->paginate((int) $request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $rewards->items(),
            'pagination' => [
                'current_page' => $rewards->currentPage(),
                'per_page' => $rewards->perPage(),
                'total' => $rewards->total(),
                'last_page' => $rewards->lastPage(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:voucher,product',
            'description' => 'required|string',
            'points_required' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:0',
            'code' => 'required|string|max:255',
            'pin' => 'nullable|string|max:255',
            'status' => 'nullable|in:available,out_of_stock',
            'expires_at' => 'nullable|date',
        ]);

        $validated = array_merge($validated, $this->resolveImageInput($request));

        $validated['status'] = $this->resolveStatus(
            $validated['quantity'],
            $validated['status'] ?? null
        );

        $reward = Reward::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reward berhasil dibuat',
            'data' => $reward,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $reward = Reward::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $reward,
        ]);
    }

    public function update(Request $request, $id)
    {
        $reward = Reward::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|in:voucher,product',
            'description' => 'sometimes|required|string',
            'points_required' => 'sometimes|required|integer|min:1',
            'quantity' => 'sometimes|required|integer|min:0',
            'code' => 'sometimes|required|string|max:255',
            'pin' => 'nullable|string|max:255',
            'status' => 'nullable|in:available,out_of_stock',
            'expires_at' => 'nullable|date',
        ]);

        $validated = array_merge($validated, $this->resolveImageInput($request, $reward));

        if (array_key_exists('quantity', $validated)) {
            $validated['status'] = $this->resolveStatus(
                $validated['quantity'],
                $validated['status'] ?? ($reward->status ?? null)
            );
        }

        $reward->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reward berhasil diperbarui',
            'data' => $reward->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $reward = Reward::findOrFail($id);
        $reward->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reward berhasil dihapus',
        ]);
    }

    private function resolveStatus(int $quantity, ?string $requestedStatus): string
    {
        if ($quantity <= 0) {
            return Reward::STATUS_OUT_OF_STOCK;
        }

        if ($requestedStatus === Reward::STATUS_OUT_OF_STOCK) {
            return Reward::STATUS_OUT_OF_STOCK;
        }

        return Reward::STATUS_AVAILABLE;
    }

    private function resolveImageInput(Request $request, ?Reward $existingReward = null): array
    {
        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'file|image|max:5120',
            ]);

            if ($existingReward?->image !== null) {
                $oldPath = $this->extractPublicStoragePath($existingReward->image);
                if ($oldPath !== null && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $path = $request->file('image')->store('rewards', 'public');

            return [
                'image' => $this->normalizeImagePath($path),
            ];
        }

        if ($request->has('image')) {
            $request->validate([
                'image' => 'nullable|string|max:2048',
            ]);

            return [
                'image' => $this->normalizeImagePath($request->input('image')),
            ];
        }

        return [];
    }

    private function extractPublicStoragePath(string $imageUrl): ?string
    {
        $storageMarker = '/storage/';
        $position = strpos($imageUrl, $storageMarker);
        if ($position === false) {
            return null;
        }

        return substr($imageUrl, $position + strlen($storageMarker));
    }

    private function normalizeImagePath(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $path = parse_url($value, PHP_URL_PATH) ?: $value;
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return '/storage/' . ltrim($path, '/');
    }
}
