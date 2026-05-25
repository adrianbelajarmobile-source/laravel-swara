<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;


class EventController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'location_name' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'event_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'quota' => 'nullable|numeric',
            'tps_id' => 'nullable|numeric',
            'point_reward' => 'nullable|numeric',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Validate end_time > start_time if both provided
        if (!empty($validated['start_time']) && !empty($validated['end_time'])) {
            $startTime = \DateTime::createFromFormat('H:i', $validated['start_time']);
            $endTime = \DateTime::createFromFormat('H:i', $validated['end_time']);
            
            if ($endTime <= $startTime) {
                return response()->json([
                    'success' => false,
                    'message' => 'end_time harus lebih besar dari start_time',
                    'errors' => ['end_time' => ['end_time harus lebih besar dari start_time']]
                ], 422);
            }
        }

        $validated['quota'] = isset($validated['quota']) ? (int) $validated['quota'] : null;
        $validated['tps_id'] = isset($validated['tps_id']) ? (int) $validated['tps_id'] : null;
        $validated['point_reward'] = isset($validated['point_reward']) ? (int) $validated['point_reward'] : 10;


        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')
                ->store('events', 'public');
        }

        $validated['created_by'] = $request->user()->id;
        $validated['qr_token'] = Str::uuid();
        $validated['status'] = $validated['status'] ?? 'pending';

        $event = Event::create($validated);

        if ($event->status === 'approved') {
            $this->notificationService->notifyPegiatForNewEvent($event);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatEventResponse($event),
            'qr_token' => $event->qr_token
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $previousStatus = $event->status;

        // Hanya pembuat yang bisa edit
        if ($event->created_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'id' => 'sometimes|required|integer',
            'title' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'location_name' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'event_date' => 'sometimes|required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'quota' => 'nullable|integer',
            'tps_id' => 'nullable|integer',
            'point_reward' => 'nullable|integer',
            'status' => 'sometimes|in:pending,approved,rejected,finished',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Validate end_time > start_time if both provided
        if (!empty($validated['start_time']) && !empty($validated['end_time'])) {
            $startTime = \DateTime::createFromFormat('H:i', $validated['start_time']);
            $endTime = \DateTime::createFromFormat('H:i', $validated['end_time']);
            
            if ($endTime <= $startTime) {
                return response()->json([
                    'success' => false,
                    'message' => 'end_time harus lebih besar dari start_time',
                    'errors' => ['end_time' => ['end_time harus lebih besar dari start_time']]
                ], 422);
            }
        }

        if ($request->hasFile('photo')) {

            // hapus foto lama
            if ($event->photo_path && Storage::disk('public')->exists($event->photo_path)) {
                Storage::disk('public')->delete($event->photo_path);
            }

            $validated['photo_path'] = $request->file('photo')
                ->store('events', 'public');
        }

        if (isset($validated['status']) && $validated['status'] === 'approved') {
            $validated['approved_by'] = $request->user()->id;
        }

        $event->update($validated);

        $updatedEvent = $event->fresh();
        if ($previousStatus !== 'approved' && $updatedEvent->status === 'approved') {
            $this->notificationService->notifyPegiatForNewEvent($updatedEvent);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $this->formatEventResponse($updatedEvent)
        ]);
    }

    public function index(Request $request)
    {
        $query = Event::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        $events = $query->latest()->paginate(10);

        // Transform response
        $data = $events->map(function ($event) {
            return $this->formatEventResponse($event);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ]
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if ($event->created_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully'
        ]);
    }


    ///Admin
    public function show(Request $request, $id)
    {
        $event = Event::with([
            'creator:id,email',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatEventResponse($event)
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,finished'
        ]);

        $event = Event::findOrFail($id);
        $previousStatus = $event->status;

        // Optional: aturan logika status
        if ($validated['status'] === 'finished' && $event->status !== 'approved') {
            return response()->json([
                'message' => 'Event hanya bisa di-finish jika sudah approved'
            ], 400);
        }

        $updateData = [
            'status' => $validated['status']
        ];

        if ($validated['status'] === 'approved') {
            $updateData['approved_by'] = $user->id;
        }

        $event->update($updateData);

        $updatedEvent = $event->fresh();
        if ($previousStatus !== 'approved' && $updatedEvent->status === 'approved') {
            $this->notificationService->notifyPegiatForNewEvent($updatedEvent);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status event berhasil diperbarui',
            'data' => $this->formatEventResponse($updatedEvent)
        ]);
    }

    public function myCreatedEvents(Request $request)
    {
        $events = Event::where('created_by', $request->user()->id)
            ->latest()
            ->paginate(10);

        $data = $events->map(function ($event) {
            return $this->formatEventResponse($event);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ]
        ]);
    }

    /**
     * Format event response dengan field time dan location yang konsisten
     */
    private function formatEventResponse($event)
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'location_name' => $event->location_name,
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
            'event_date' => $event->event_date?->toIso8601String(),
            'start_time' => $event->start_time, // Time format H:i:s from cast
            'end_time' => $event->end_time,     // Time format H:i:s from cast
            'quota' => $event->quota,
            'photo_path' => $event->photo_path,
            'tps_id' => $event->tps_id,
            'point_reward' => $event->point_reward,
            'created_by' => $event->created_by,
            'approved_by' => $event->approved_by,
            'status' => $event->status,
            'qr_token' => $event->qr_token,
            'created_at' => $event->created_at?->toIso8601String(),
            'updated_at' => $event->updated_at?->toIso8601String(),
        ];
    }
}
