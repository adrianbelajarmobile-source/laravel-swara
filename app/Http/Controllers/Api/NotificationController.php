<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 20);

        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($perPage);

        $data = $notifications->getCollection()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->data['title'] ?? null,
                'body' => $notification->data['body'] ?? null,
                'category' => $notification->data['category'] ?? 'general',
                'data' => $notification->data['data'] ?? [],
                'read_at' => optional($notification->read_at)?->toIso8601String(),
                'created_at' => optional($notification->created_at)?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sudah dibaca',
        ]);
    }

    public function sendCustom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:1000',
            'category' => 'nullable|string|max:100',
            'target_type' => 'required|in:all,admins,role,users',
            'role' => 'nullable|string|in:user,pegiat,influencer,admin',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'data' => 'nullable|array',
        ]);

        if ($validated['target_type'] === 'role' && empty($validated['role'])) {
            return response()->json([
                'success' => false,
                'message' => 'Field role wajib diisi jika target_type=role',
            ], 422);
        }

        if ($validated['target_type'] === 'users' && empty($validated['user_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Field user_ids wajib diisi jika target_type=users',
            ], 422);
        }

        $totalSent = $this->notificationService->sendCustomNotification($validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom push notification berhasil dikirim',
            'total_sent' => $totalSent,
        ]);
    }
}
