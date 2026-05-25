<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\River;

class RiverController extends Controller
{
    // ✅ Add River
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            // condition dihitung otomatis dari river reports
        ]);

        $river = River::create($validated);

        return response()->json([
            'message' => 'River berhasil ditambahkan',
            'data' => $river
        ], 201);
    }


    // ✅ Get Rivers List
    public function index()
    {
        $rivers = River::latest()->get();

        return response()->json([
            'data' => $rivers
        ]);
    }

    // ✅ Get nearest rivers from user location
    public function nearest(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];

        $radii = [100, 300, 500, 1000];
        $rivers = collect();

        foreach ($radii as $radius) {
            $rivers = $this->nearestRiversQuery($latitude, $longitude, $radius)
                ->limit(5)
                ->get();

            if ($rivers->isNotEmpty()) {
                break;
            }
        }

        if ($rivers->isEmpty()) {
            $rivers = $this->nearestRiversQuery($latitude, $longitude)
                ->limit(5)
                ->get();
        }

        $data = $rivers->map(function ($river) {
            return [
                'id' => $river->id,
                'name' => $river->name,
                'latitude' => $river->latitude,
                'longitude' => $river->longitude,
                'distance' => (int) round($river->distance),
            ];
        })->values();

        return response()->json($data);
    }

    private function nearestRiversQuery(float $latitude, float $longitude, ?int $radiusMeter = null)
    {
        $distanceExpression = '(6371000 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';

        $query = River::query()
            ->select(['id', 'name', 'latitude', 'longitude'])
            ->selectRaw($distanceExpression . ' as distance', [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($radiusMeter !== null) {
            $query->whereRaw($distanceExpression . ' <= ?', [$latitude, $longitude, $latitude, $radiusMeter]);
        }

        return $query->orderByRaw($distanceExpression . ' asc', [$latitude, $longitude, $latitude]);
    }

    // ✅ Get River Detail + Reporters
    public function show($id)
    {
        $river = River::with([
            'reports' => function ($query) {
                $query->latest();
            },
            'reports.user.profile',
        ])->findOrFail($id);

        $reports = $river->reports;
        $latestReport = $reports->first();
        $uniqueReporters = $reports->unique('user_id')->values();

        return response()->json([
            'data' => [[
                'id' => $river->id,
                'name' => $river->name,
                'description' => $river->description,
                'latitude' => $river->latitude,
                'longitude' => $river->longitude,
                'condition' => $river->condition,
                'created_at' => $river->created_at,
                'updated_at' => $river->updated_at,
                'latest_report_photo' => $latestReport?->photo_path,
                'reporter_user_ids' => $uniqueReporters->pluck('user_id')->values()->all(),
                'reporters' => $uniqueReporters->map(function ($report) {
                    return [
                        'user_id' => $report->user_id,
                        'name' => $report->user?->profile?->name,
                        'photo_profile' => $report->user?->profile?->photo_profile,
                    ];
                })->values()->all(),
            ]],
        ]);
    }
}
