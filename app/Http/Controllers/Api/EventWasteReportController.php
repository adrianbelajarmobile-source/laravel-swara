<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EventWasteReport;

class EventWasteReportController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'total_waste_kg' => 'required|numeric',
            'waste_type' => 'nullable|string',
            'photo' => 'nullable|image'
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] =
                $request->file('photo')->store('waste', 'public');
        }

        $validated['status'] = 'pending';

        $validated['reported_by'] = $request->user()->id;

        $report = EventWasteReport::create($validated);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }


    public function update(Request $request, $id)
    {
        $report = EventWasteReport::findOrFail($id);

        $report->update($request->only([
            'total_waste_kg',
            'waste_type'
        ]));

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $report = EventWasteReport::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,accepted,progress,rejected'
        ]);

        $report->update([
            'status' => $validated['status']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $report
        ]);
    }

    public function index(Request $request)
    {
        $query = EventWasteReport::with([
            'event:id,title',
            'user:id,email'
        ]);

        if ($request->has('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get()
        ]);
    }


    public function destroy($id)
    {
        $report = EventWasteReport::findOrFail($id);
        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted'
        ]);
    }
}
