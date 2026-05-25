<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\User;

class HomeController extends Controller
{
    /**
     * Return home data for pegiat/influencer users.
     *
     * - event_banner: three latest approved events (title, location_name, event_date, photo_path)
     * - leaderboard: users ordered by total_points desc with profile name & photo_profile
     * - event_list: all approved events ordered by newest with full attributes
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // only non-admin users should access this endpoint
        if ($user->role_id === 1) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $eventBanner = Event::approved()
            ->orderBy('event_date', 'desc')
            ->take(3)
            ->get(['title', 'location_name', 'event_date', 'photo_path']);

        $leaderboard = User::with('profile')
            ->orderByDesc('total_points')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => optional($u->profile)->name,
                    'photo_profile' => optional($u->profile)->photo_profile,
                    'total_points' => $u->total_points,
                ];
            });

        $eventList = Event::approved()
            ->latest()
            ->get([
                'id',
                'title',
                'description',
                'location_name',
                'latitude',
                'longitude',
                'event_date',
                'quota',
                'photo_path',
                'tps_id',
                'point_reward',
                'created_by',
                'approved_by',
                'status',
                'qr_token',
            ]);

        return response()->json([
            'event_banner' => $eventBanner,
            'leaderboard' => $leaderboard,
            'event_list' => $eventList,
        ]);
    }
}
