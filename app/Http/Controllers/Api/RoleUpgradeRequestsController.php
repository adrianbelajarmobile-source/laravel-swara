<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RoleUpgradeRequests;
use App\Models\InfluencerAnswer;
use App\Models\InfluencerQuestion;
use App\Services\NotificationService;

class RoleUpgradeRequestsController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function apply(Request $request)
    {
        $user = $request->user();

        // Cek apakah ada pengajuan pending
        if ($user->influencerApplications()
                 ->where('status', 'pending')
                 ->exists()) {
            return response()->json([
                'message' => 'Masih ada pengajuan yang diproses'
            ], 400);
        }

        // Validasi request
        $validated = $request->validate([
            'nik' => 'required|string|max:20',
            'screenshot' => 'required|image|max:2048',
            'answers' => 'required|array'
        ]);

        $application = null;

        DB::transaction(function () use ($validated, $user, $request, &$application) {

            // Simpan screenshot
            $path = $request->file('screenshot')
                            ->store('influencer', 'public');

            // Buat pengajuan baru
            $application = RoleUpgradeRequests::create([
                'user_id' => $user->id,
                'nik' => $validated['nik'],
                'screenshot_path' => $path,
            ]);

            // Ambil semua question_id yang valid dari database
            $validQuestionIds = InfluencerQuestion::pluck('id')->toArray();

            // Simpan jawaban
            foreach ($validated['answers'] as $questionId => $answer) {
                if (!in_array($questionId, $validQuestionIds)) {
                    // Skip jawaban yang tidak valid
                    continue;
                }

                InfluencerAnswer::create([
                    'application_id' => $application->id,
                    'question_id' => $questionId,
                    'answer' => $answer,
                ]);
            }
        });

        if ($application) {
            $this->notificationService->notifyAdminsForUpgradeRequest($application, $user);
        }

        return response()->json([
            'message' => 'Pengajuan berhasil dikirim'
        ]);
    }
}
