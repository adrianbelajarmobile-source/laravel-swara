<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\User;
use App\Models\RiverReport;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function getAdminInfo(Request $request)
    {
        $totalEvents = Event::count();

        $totalInfluencers = User::where('role_id', 3)->count();

        $totalRiverReports = RiverReport::count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_events' => $totalEvents,
                'total_influencers' => $totalInfluencers,
                'total_river_reports' => $totalRiverReports,
            ]
        ]);
    }
    public function getAdminEvents(Request $request)
{
    // Statistik event
    $stats = Event::select(
        DB::raw("COUNT(*) as total_events"),
        DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as total_rejected"),
        DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as total_approved"),
        DB::raw("SUM(CASE WHEN status = 'finished' THEN 1 ELSE 0 END) as total_finished")
    )->first();

    // Ambil events dengan kolom lengkap
    $events = Event::with(['tps:id,name'])
        ->select(
            'id',
            'title',
            'description',   // <--- tambahkan description
            'location_name',
            'event_date',
            'status',
            'quota',
            'photo_path',    // <--- tetap photo_path
            'tps_id'
        )
        ->latest()
        ->paginate(10);

    // Format data event
    $eventList = $events->map(function ($event) {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'location_name' => $event->location_name,
            'event_date' => $event->event_date,
            'status' => $event->status,
            'quota' => $event->quota,
            'photo' => $event->photo_path,
            'tps' => $event->tps ? $event->tps->name : null
        ];
    });

    return response()->json([
        'success' => true,
        'statistics' => [
            'total_events' => (int) $stats->total_events,
            'total_rejected' => (int) $stats->total_rejected,
            'total_approved' => (int) $stats->total_approved,
            'total_finished' => (int) $stats->total_finished,
        ],
        'events' => $eventList,
        'meta' => [
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'per_page' => $events->perPage(),
            'total' => $events->total(),
        ]
    ]);
}


}
