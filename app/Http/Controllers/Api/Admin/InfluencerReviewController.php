<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RoleUpgradeRequests;
use App\Models\Role;

class InfluencerReviewController extends Controller
{
    public function review(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_note' => 'nullable|string'
        ]);

        $application = RoleUpgradeRequests::findOrFail($id);

        DB::transaction(function () use ($application, $validated, $request) {

            $application->update([
                'status' => $validated['status'],
                'admin_note' => $validated['admin_note'] ?? null,
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id
            ]);

            if ($validated['status'] === 'approved') {
                $role = Role::where('name', 'influencer')->first();

                $application->user->update([
                    'role_id' => $role->id
                ]);
            }
        });

        return response()->json([
            'message' => 'Pengajuan berhasil direview'
        ]);
    }

    public function summary()
    {
        try {
            return response()->json([
                'total' => (int) RoleUpgradeRequests::count(),
                'pending' => (int) RoleUpgradeRequests::where('status', 'pending')->count(),
                'approved' => (int) RoleUpgradeRequests::where('status', 'approved')->count(),
                'rejected' => (int) RoleUpgradeRequests::where('status', 'rejected')->count(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to get influencer summary',
                'code' => 'INFLUENCER_SUMMARY_FAILED',
            ], 500);
        }
    }


    public function index(Request $request)
    {
        $query = \App\Models\RoleUpgradeRequests::with([
            'user.profile',
            'user.role',
            'answers.question',
            'reviewer'
        ]);

        // Optional filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($applications);
    }
}
