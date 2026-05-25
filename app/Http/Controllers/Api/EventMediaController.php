<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventMedia;
use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventMediaController extends Controller
{
    /**
     * Upload photo/video untuk event
     * POST /events/{id}/media
     */
    public function store(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);
        $user = $request->user();

        $validated = $request->validate([
            'media_type' => 'required|in:photo,video',
            'file' => 'required|file|mimes:jpg,jpeg,png,mp4,mov,avi,mkv|max:104857600', // 100MB
            'participant_id' => 'nullable|exists:event_participants,id',
            'description' => 'nullable|string|max:500'
        ]);

        // Verify participant belongs to this event if provided
        if ($validated['participant_id']) {
            $participant = EventParticipant::where([
                'id' => $validated['participant_id'],
                'event_id' => $eventId
            ])->firstOrFail();
        }

        // Store file
        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->store("events/{$eventId}/media", 'public');

        // Create media record
        $media = EventMedia::create([
            'event_id' => $eventId,
            'participant_id' => $validated['participant_id'] ?? null,
            'uploaded_by' => $user->id,
            'media_type' => $validated['media_type'],
            'file_path' => $filePath,
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'description' => $validated['description'] ?? null
        ]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($validated['media_type']) . ' uploaded successfully',
            'data' => $this->formatMediaResponse($media)
        ], 201);
    }

    /**
     * Get media list untuk event
     * GET /events/{id}/media?media_type=photo&participant_id=X
     */
    public function index(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);

        $query = EventMedia::where('event_id', $eventId)
            ->with(['uploader:id,email', 'uploader.profile:user_id,name,photo_profile']);

        // Filter by media type
        if ($request->has('media_type')) {
            $query->where('media_type', $request->media_type);
        }

        // Filter by participant
        if ($request->has('participant_id')) {
            $query->where('participant_id', $request->participant_id);
        }

        $media = $query->latest()->paginate(20);

        $data = $media->map(function ($m) {
            return $this->formatMediaResponse($m);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $media->currentPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
                'last_page' => $media->lastPage(),
            ]
        ]);
    }

    /**
     * Delete media
     * DELETE /events/{id}/media/{media_id}
     */
    public function destroy($eventId, $mediaId)
    {
        $media = EventMedia::where([
            'id' => $mediaId,
            'event_id' => $eventId
        ])->firstOrFail();

        // Delete file from storage
        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Media deleted successfully'
        ]);
    }

    /**
     * Format media response
     */
    private function formatMediaResponse($media)
    {
        return [
            'id' => $media->id,
            'event_id' => $media->event_id,
            'participant_id' => $media->participant_id,
            'media_type' => $media->media_type,
            'file_path' => $media->file_path,
            'file_url' => $media->file_path ? asset('storage/' . $media->file_path) : null,
            'file_size_kb' => $media->file_size_kb,
            'file_size_mb' => $media->file_size_mb,
            'original_name' => $media->original_name,
            'description' => $media->description,
            'uploader' => [
                'id' => $media->uploader->id,
                'email' => $media->uploader->email,
                'name' => $media->uploader?->profile?->name,
                'photo_profile' => $media->uploader?->profile?->photo_profile
            ],
            'uploaded_at' => $media->created_at->toIso8601String()
        ];
    }
}
