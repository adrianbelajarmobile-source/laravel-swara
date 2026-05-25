<?php

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventParticipantController;
use App\Http\Controllers\Api\EventMediaController;
use App\Http\Controllers\Api\EventImpactReportController;
use App\Http\Controllers\Api\EventWasteReportController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RiverConclusionController;
use App\Http\Controllers\Api\RiverController;
use App\Http\Controllers\Api\RiverReportController;
use App\Http\Controllers\Api\TpsController;
use App\Http\Controllers\Api\RewardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\InfluencerReviewController;
use App\Http\Controllers\Api\Admin\InfluencerQuestionController;
use App\Http\Controllers\Api\Admin\RewardController as AdminRewardController;
use App\Http\Controllers\Api\RoleUpgradeRequestsController;
use App\Http\Controllers\Api\UpgradePosterController;


Route::prefix('auth')->group(function () {

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});


Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('admin')->group(function () {
        Route::get('/admin/getadmininfo', [AdminController::class, 'getAdminInfo']);
        Route::patch('/admin/influencer/{id}', [InfluencerReviewController::class, 'review']);
        Route::get('/admin/influencer/summary', [InfluencerReviewController::class, 'summary']);
        Route::get('/admin/influencer', [InfluencerReviewController::class, 'index']);
        Route::get('/admin/events', [AdminController::class, 'getAdminEvents']);
        Route::post('/admin/events/{event}/certificates/generate', [EventParticipantController::class, 'generateEventCertificates']);
        Route::post('/admin/events/{event}/certificate-template', [EventParticipantController::class, 'updateEventCertificateTemplate']);
        Route::get('/admin/events/certificate-template/layout-guide', [EventParticipantController::class, 'certificateTemplateLayoutGuide']);
        Route::apiResource('/admin/rewards', AdminRewardController::class);
        Route::post('/admin/rewards/{id}', [AdminRewardController::class, 'update']);
        Route::patch('/events/{id}/status', [EventController::class, 'updateStatus']);
        Route::post('/admin/notifications/custom', [NotificationController::class, 'sendCustom']);

        // QUESTION (admin managed)
        Route::get('influencer/questions', [InfluencerQuestionController::class, 'index']);
        Route::post('influencer/questions', [InfluencerQuestionController::class, 'store']);
        Route::get('influencer/questions/{id}', [InfluencerQuestionController::class, 'show']);
        Route::put('influencer/questions/{id}', [InfluencerQuestionController::class, 'update']);
        Route::delete('influencer/questions/{id}', [InfluencerQuestionController::class, 'destroy']);
    });

    Route::post('/influencer/apply', [RoleUpgradeRequestsController::class, 'apply']);

    // NOTIFICATIONS
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/device-token', [DeviceTokenController::class, 'store']);
    Route::delete('/notifications/device-token', [DeviceTokenController::class, 'destroy']);

    //RIVERS
    Route::post('/rivers', [RiverController::class, 'store']);
    Route::get('/rivers', [RiverController::class, 'index']);
    Route::get('/rivers/nearest', [RiverController::class, 'nearest']);
    Route::get('/rivers/{id}', [RiverController::class, 'show']);
    Route::post('/river-reports', [RiverReportController::class, 'store']);
    Route::get('/river-reports', [RiverReportController::class, 'index']);
    Route::get('/rivers-conclusion', [RiverConclusionController::class, 'index']);
    Route::get('/rivers-conclusion/{id}', [RiverConclusionController::class, 'show']);

    // EVENTS
    Route::get('/events/my/created', [EventController::class, 'myCreatedEvents']); // Events yang dibuat user
    Route::apiResource('events', EventController::class);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    Route::post('/events/{event}/join', [EventParticipantController::class, 'join']);

    // PARTICIPANTS
    Route::get('/events/history/me', [EventParticipantController::class, 'myHistory']);
    Route::post('/events/{event}/join', [EventParticipantController::class, 'join']);
    Route::post('/events/{event}/check-in', [EventParticipantController::class, 'checkIn']);
    Route::post('/events/{event}/participants/{participant}/check-out', [EventParticipantController::class, 'checkOut']);
    Route::get('/events/participants/{participant}/certificate', [EventParticipantController::class, 'certificate']);
    Route::get('/events/participants/{participant}/certificate/status', [EventParticipantController::class, 'certificateStatus']);
    Route::get('/events/{event}/progress', [EventParticipantController::class, 'progress']);
    Route::get('/events/{event}/participants', [EventParticipantController::class, 'participants']);
    Route::post('/events/scan', [EventParticipantController::class, 'scan']);

    // MEDIA
    Route::post('/events/{event}/media', [EventMediaController::class, 'store']);
    Route::get('/events/{event}/media', [EventMediaController::class, 'index']);
    Route::delete('/events/{event}/media/{media}', [EventMediaController::class, 'destroy']);

    // IMPACT REPORTS
    Route::post('/events/{event}/impact-reports', [EventImpactReportController::class, 'store']);
    Route::get('/events/{event}/impact-reports', [EventImpactReportController::class, 'index']);
    Route::get('/events/{event}/impact-reports/{report}', [EventImpactReportController::class, 'show']);
    Route::put('/events/{event}/impact-reports/{report}', [EventImpactReportController::class, 'update']);
    Route::delete('/events/{event}/impact-reports/{report}', [EventImpactReportController::class, 'destroy']);

    // WASTE REPORT
    Route::post('/event-waste-reports', [EventWasteReportController::class, 'store']);
    Route::put('/event-waste-reports/{id}', [EventWasteReportController::class, 'update']);
    // Route::get('/events/{event}/waste-reports', [EventWasteReportController::class, 'index']);
    Route::get('/event-waste-reports', [EventWasteReportController::class, 'index']);
    Route::patch('/event-waste-reports/{id}/status', [EventWasteReportController::class, 'updateStatus']);
    Route::delete('/event-waste-reports/{id}', [EventWasteReportController::class, 'destroy']);

    //TPS
    Route::post('/tps', [TpsController::class, 'store']);
    Route::get('/tps', [TpsController::class, 'index']);
    Route::put('/tps/{id}', [TpsController::class, 'update']);
    Route::delete('/tps/{id}', [TpsController::class, 'destroy']);

    // HOME (pegiat / influencer)
    Route::get('/home', [\App\Http\Controllers\Api\HomeController::class, 'index']);

    // REWARDS
    Route::get('/rewards', [RewardController::class, 'index']);
    Route::post('/rewards/{reward}/redeem', [RewardController::class, 'redeem']);
    Route::get('/rewards/redeem-histories', [RewardController::class, 'redeemHistories']);

    // UPGRADE POSTERS
    Route::get('/upgrade/posters', [UpgradePosterController::class, 'index']);
    Route::post('/upgrade/posters', [UpgradePosterController::class, 'store']);
    Route::delete('/upgrade/posters/{id}', [UpgradePosterController::class, 'destroy']);

    // COMMUNITIES
    Route::post('/communities', [CommunityController::class, 'store']); // Create komunitas baru
    Route::get('/communities', [CommunityController::class, 'index']); // List semua komunitas
    Route::get('/communities/my/created', [CommunityController::class, 'myCreatedCommunities']); // Komunitas yang dibuat user
    Route::get('/communities/my/joined', [CommunityController::class, 'myJoinedCommunities']); // Komunitas yang diikuti user
    Route::get('/communities/{community}', [CommunityController::class, 'show']); // Detail komunitas
    Route::post('/communities/{community}/join', [CommunityController::class, 'join']); // Join komunitas
    Route::post('/communities/{community}/leave', [CommunityController::class, 'leave']); // Leave komunitas
    Route::post('/communities/{community}/invite', [CommunityController::class, 'invite']); // Invite user
    Route::get('/communities/{community}/join-requests', [CommunityController::class, 'joinRequests']); // Pending join requests
    Route::post('/communities/{community}/members/{member:id}/approve', [CommunityController::class, 'approveJoinRequest']); // Approve pending member
    Route::delete('/communities/{community}/members/{member:id}/reject', [CommunityController::class, 'rejectJoinRequest']); // Reject pending member
    Route::get('/communities/{community}/members', [CommunityController::class, 'members']); // List members
    Route::patch('/communities/{community}/members/{member:id}', [CommunityController::class, 'updateMemberRole']); // Update member role
    Route::delete('/communities/{community}/members/{member:id}', [CommunityController::class, 'removeMember']); // Remove member
    Route::delete('/communities/{community}', [CommunityController::class, 'destroy']); // Delete komunitas

    // CHAT COMMUNITY
    Route::post('/communities/{community}/messages', [ChatController::class, 'sendMessage']);
    Route::get('/communities/{community}/messages', [ChatController::class, 'getMessagesByCommunity']);
    
});
