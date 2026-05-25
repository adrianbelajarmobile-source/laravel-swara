<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\River;
use App\Models\RiverConclusion;

class RiverConclusionController extends Controller
{
    public function index()
    {
        $this->syncConclusions();

        $conclusions = RiverConclusion::with('river:id,name,latitude,longitude,condition')
            ->latest('updated_at')
            ->get()
            ->map(function (RiverConclusion $conclusion) {
                return $this->formatConclusion($conclusion);
            });

        return response()->json([
            'success' => true,
            'data' => $conclusions,
        ]);
    }

    public function show($id)
    {
        $this->syncConclusions();

        $conclusion = RiverConclusion::with([
            'river:id,name,description,latitude,longitude,condition',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatConclusion($conclusion, true),
        ]);
    }

    private function syncConclusions(): void
    {
        $rivers = River::with(['reports.user.profile'])->get();
        $riverIds = $rivers->pluck('id')->all();

        if ($riverIds === []) {
            RiverConclusion::query()->delete();

            return;
        }

        foreach ($rivers as $river) {
            $reports = $river->reports;
            $urgencyValues = $reports
                ->map(fn ($report) => $this->resolveUrgencyValue($report->urgency))
                ->filter(fn ($value) => $value !== null)
                ->values();

            $averageUrgency = $urgencyValues->isNotEmpty()
                ? round($urgencyValues->avg(), 2)
                : null;

            $uniqueReporters = $reports
                ->unique('user_id')
                ->values();

            $latestReport = $reports->sortByDesc('created_at')->first();

            // Calculate condition dari average urgency
            $condition = $this->resolveCondition($averageUrgency);

            // Update river's condition
            $river->update(['condition' => $condition]);

            RiverConclusion::updateOrCreate(
                ['river_id' => $river->id],
                [
                    'river_path' => $latestReport?->photo_path,
                    'status' => $this->resolveStatus($averageUrgency),
                    'pollution_type' => $this->resolvePollutionType($averageUrgency),
                    'average_urgency' => $averageUrgency,
                    'reporter_user_ids' => $uniqueReporters->pluck('user_id')->values()->all(),
                    'reporters' => $uniqueReporters->map(function ($report) {
                        return [
                            'user_id' => $report->user_id,
                            'photo_profile' => $report->user?->profile?->photo_profile,
                        ];
                    })->values()->all(),
                    'reporter_count' => $uniqueReporters->count(),
                ]
            );
        }

        RiverConclusion::whereNotIn('river_id', $riverIds)->delete();
    }

    private function resolveStatus(?float $averageUrgency): string
    {
        if ($averageUrgency === null) {
            return 'normal';
        }

        if ($averageUrgency < 1.5) {
            return 'normal';
        }

        if ($averageUrgency < 2.5) {
            return 'warning';
        }

        return 'urgent';
    }

    private function resolvePollutionType(?float $averageUrgency): string
    {
        if ($averageUrgency === null) {
            return 'unknown';
        }

        if ($averageUrgency < 1.5) {
            return 'low';
        }

        if ($averageUrgency < 2.5) {
            return 'moderate';
        }

        return 'high';
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
            return River::CONDITION_NORMAL; // 1
        }

        if ($averageUrgency < 1.5) {
            return River::CONDITION_NORMAL; // 1
        }

        if ($averageUrgency < 2.5) {
            return River::CONDITION_WARNING; // 2
        }

        return River::CONDITION_URGENT; // 3
    }

    private function formatConclusion(RiverConclusion $conclusion, bool $withRiverDetail = false): array
    {
        return array_filter([
            'id' => $conclusion->id,
            'riverId' => $conclusion->river_id,
            'river_path' => $conclusion->river_path,
            'status' => $conclusion->status,
            'pollution_type' => $conclusion->pollution_type,
            'urgency' => $conclusion->average_urgency,
            'reporter_count' => $conclusion->reporter_count,
            'reporter_user_ids' => $conclusion->reporter_user_ids,
            'reporters' => $conclusion->reporters,
            'river' => $withRiverDetail ? $conclusion->river : null,
            'latitude' => $conclusion->river?->latitude,
            'longitude' => $conclusion->river?->longitude,
        ], static fn ($value) => $value !== null);
    }
}