<?php

namespace App\Http\Controllers\Api;

use App\Events\CommunityMessageSent;
use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Send a message to a community.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request, Community $community): JsonResponse
    {
        // Get authenticated user
        $user = Auth::user();

        // Check if user is a member of the community
        if (!$community->isMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this community',
            ], 403);
        }

        // Validate request
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        // Create the message
        $message = $community->messages()->create([
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        // Load the user relationship with profile
        $message->load('user.profile');

        // Broadcast the message event to other users
        broadcast(new CommunityMessageSent($message))->toOthers();

        // Return the created message
        return response()->json([
            'success' => true,
            'data' => $this->formatMessage($message),
        ], 201);
    }

    /**
     * Get messages from a community with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Community  $community
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessagesByCommunity(Request $request, Community $community): JsonResponse
    {
        // Get authenticated user
        $user = Auth::user();

        // Check if user is a member of the community
        if (!$community->isMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this community',
            ], 403);
        }

        // Validate pagination parameter
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        // Get messages with eager loading, latest first but ordered ascending for display
        $messages = $community->messages()
            ->with('user.profile')
            ->orderBy('created_at', 'asc')
            ->paginate(20, ['*'], 'page', $validated['page'] ?? 1);

        // Format messages
        $formattedMessages = $messages->map(fn($message) => $this->formatMessage($message));

        // Return paginated messages
        return response()->json([
            'success' => true,
            'data' => $formattedMessages,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'last_page' => $messages->lastPage(),
                'from' => $messages->firstItem(),
                'to' => $messages->lastItem(),
            ],
        ]);
    }

    /**
     * Format message response data.
     *
     * @param  \App\Models\Message  $message
     * @return array
     */
    private function formatMessage(Message $message): array
    {
        return [
            'id' => $message->id,
            'community_id' => $message->community_id,
            'message' => $message->message,
            'user' => [
                'id' => $message->user->id,
                'email' => $message->user->email,
                'profile' => $message->user->profile,
            ],
            'created_at' => $message->created_at->toIso8601String(),
            'updated_at' => $message->updated_at->toIso8601String(),
        ];
    }
}
