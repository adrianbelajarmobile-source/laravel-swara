<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RiverReport;
use App\Models\River;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class RiverReportController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'river_id' => 'required|exists:rivers,id',
            'description' => 'required|string',
            'photo' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
            'video' => 'sometimes|mimetypes:video/mp4,video/quicktime,video/x-msvideo|max:10240',
            'monitoring_date' => 'sometimes|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'urgency' => 'nullable|string|in:normal,warning,urgent',
            'reported_by_type' => 'nullable|string|in:community,influencer',
        ]);

        $user = $request->user();

        DB::beginTransaction();

        try {
            $photoPath = null;
            $videoPath = null;

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')
                    ->store('river_reports', 'public');
            }

            if ($request->hasFile('video')) {
                $videoPath = $request->file('video')
                    ->store('river_reports/videos', 'public');
            }

            $report = RiverReport::create([
                'user_id' => $user->id,
                'river_id' => $validated['river_id'],
                'description' => $validated['description'],
                'photo_path' => $photoPath,
                'video_path' => $videoPath,
                'monitoring_date' => $validated['monitoring_date'] ?? now()->date(),
                'reported_by_type' => $validated['reported_by_type'] ?? 'community',
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'urgency' => $validated['urgency'] ?? 'normal',
                'status' => 'pending'
            ]);

            // Trigger update river condition dari reports
            $this->updateRiverCondition($validated['river_id']);

            $report->loadMissing('river', 'user.role');
            $this->notificationService->notifyAdminsForRiverAlert($report, $user);
            $this->notificationService->notifyNearbyUsersForRiverAlert($report);

            DB::commit();

            return response()->json([
                'message' => 'Report berhasil dikirim',
                'data' => $report
            ], 201);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function updateRiverCondition($riverId): void
    {
        $river = River::with('reports')->find($riverId);
        
        if (!$river) {
            return;
        }

        $reports = $river->reports;
        $urgencyValues = $reports
            ->map(fn ($report) => $this->resolveUrgencyValue($report->urgency))
            ->filter(fn ($value) => $value !== null)
            ->values();

        $averageUrgency = $urgencyValues->isNotEmpty()
            ? round($urgencyValues->avg(), 2)
            : null;

        $condition = $this->resolveCondition($averageUrgency);
        $river->update(['condition' => $condition]);
    }

    private function resolveUrgencyValue($urgency): ?float
    {
        if (is_numeric($urgency)) {
            return (float) $urgency;
        }

        $normalized = strtolower(trim((string) $urgency));

        return match ($normalized) {
            'normal', 'rendah' => 1.0,
            'warning', 'medium', 'sedang' => 2.0,
            'urgent', 'high', 'tinggi' => 3.0,
            default => null,
        };
    }

    private function resolveCondition(?float $averageUrgency): int
    {
        if ($averageUrgency === null) {
            return 1; // CONDITION_NORMAL
        }

        if ($averageUrgency < 1.5) {
            return 1; // CONDITION_NORMAL
        }

        if ($averageUrgency < 2.5) {
            return 2; // CONDITION_WARNING
        }

        return 3; // CONDITION_URGENT
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $query = RiverReport::with([
            'river:id,name,condition',
            'user:id,email,role_id',               
            'user.profile:id,user_id,name,photo_profile', 
            'user.role:id,name'
        ]);

        // 🔎 Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 🔎 Filter by river
        if ($request->filled('river_id')) {
            $query->where('river_id', $request->river_id);
        }

        // 🔎 Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $reports = $query->latest()->paginate($perPage);

        // format data agar lebih rapi
        $data = $reports->map(function ($report) {
            return [
                'id' => $report->id,
                'description' => $report->description,
                'photo_path' => $report->photo_path,
                'latitude' => $report->latitude,
                'longitude' => $report->longitude,
                'urgency' => $report->urgency,
                'status' => $report->status,
                'river' => $report->river,
                'profile' => [
                    'id' => $report->user->id,
                    'email' => $report->user->email,
                    'name' => $report->user->profile->name ?? null,
                    'role' => $report->user->role->name ?? null,
                    'photo_profile' => $report->user->profile->photo_profile ?? null
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'has_more' => $reports->hasMorePages(),
            ]
        ]);
    }
}
