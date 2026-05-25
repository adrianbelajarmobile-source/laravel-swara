<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventImpactReport;

class EventImpactReportController extends Controller
{
    /**
     * Submit impact report
     * POST /events/{event}/impact-reports
     */
    public function store($eventId, Request $request)
    {
        $event = Event::findOrFail($eventId);

        $validated = $request->validate([
            'activity_report' => 'required|string|max:5000',
            'suggestions' => 'required|string|max:5000'
        ]);

        $report = EventImpactReport::create([
            'event_id' => $event->id,
            'reported_by' => $request->user()->id,
            'activity_report' => $validated['activity_report'],
            'suggestions' => $validated['suggestions']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report dampak berhasil disimpan',
            'data' => $this->formatReportResponse($report->fresh('reporter'))
        ], 201);
    }

    /**
     * Get all impact reports for an event
     * GET /events/{event}/impact-reports
     */
    public function index($eventId, Request $request)
    {
        $event = Event::findOrFail($eventId);

        $query = EventImpactReport::where('event_id', $event->id)
            ->with('reporter');

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $reports = $query->paginate(20);

        $data = $reports->map(function ($report) {
            return $this->formatReportResponse($report);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'last_page' => $reports->lastPage(),
            ]
        ]);
    }

    /**
     * Get single impact report
     * GET /events/{event}/impact-reports/{id}
     */
    public function show($eventId, $reportId)
    {
        $event = Event::findOrFail($eventId);
        $report = EventImpactReport::where([
            'id' => $reportId,
            'event_id' => $event->id
        ])->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $this->formatReportResponse($report->fresh('reporter'))
        ]);
    }

    /**
     * Update impact report (only by creator)
     * PUT /events/{event}/impact-reports/{id}
     */
    public function update($eventId, $reportId, Request $request)
    {
        $event = Event::findOrFail($eventId);
        $report = EventImpactReport::where([
            'id' => $reportId,
            'event_id' => $event->id
        ])->firstOrFail();

        // Only creator can update
        if ($report->reported_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya akses untuk update report ini'
            ], 403);
        }

        $validated = $request->validate([
            'activity_report' => 'required|string|max:5000',
            'suggestions' => 'required|string|max:5000'
        ]);

        $report->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Report dampak berhasil diupdate',
            'data' => $this->formatReportResponse($report->fresh('reporter'))
        ]);
    }

    /**
     * Delete impact report (only by creator)
     * DELETE /events/{event}/impact-reports/{id}
     */
    public function destroy($eventId, $reportId, Request $request)
    {
        $event = Event::findOrFail($eventId);
        $report = EventImpactReport::where([
            'id' => $reportId,
            'event_id' => $event->id
        ])->firstOrFail();

        // Only creator can delete
        if ($report->reported_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak punya akses untuk delete report ini'
            ], 403);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report dampak berhasil dihapus'
        ]);
    }

    /**
     * Format report response
     */
    private function formatReportResponse($report)
    {
        return [
            'id' => $report->id,
            'event_id' => $report->event_id,
            'activity_report' => $report->activity_report,
            'suggestions' => $report->suggestions,
            'reported_by' => [
                'id' => $report->reporter->id,
                'name' => $report->reporter?->profile?->name,
                'photo_profile' => $report->reporter?->profile?->photo_profile
            ],
            'created_at' => $report->created_at->toIso8601String(),
            'updated_at' => $report->updated_at->toIso8601String()
        ];
    }
}
