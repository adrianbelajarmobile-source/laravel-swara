<?php

namespace App\Services;

use App\Models\Event;
use App\Models\RoleUpgradeRequests;
use App\Models\RiverReport;
use App\Models\User;
use App\Notifications\SystemPushNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function __construct(private readonly PushNotificationService $pushNotificationService)
    {
    }

    public function notifyAdminsForRiverAlert(RiverReport $report, User $actor): int
    {
        $urgency = strtolower((string) $report->urgency);
        if (!in_array($urgency, ['warning', 'urgent'], true)) {
            return 0;
        }

        $actorRole = strtolower((string) optional($actor->role)->name);
        if ($actorRole === 'admin') {
            return 0;
        }

        $admins = $this->usersByRoles(['admin']);
        if ($admins->isEmpty()) {
            return 0;
        }

        $riverName = $report->river?->name ?? ('River #' . $report->river_id);
        $title = $urgency === 'urgent'
            ? 'Laporan sungai URGENT'
            : 'Laporan sungai WARNING';

        $body = sprintf(
            'Ada laporan %s untuk %s dari user %s.',
            strtoupper($urgency),
            $riverName,
            $actor->email
        );

        $data = [
            'river_report_id' => $report->id,
            'river_id' => $report->river_id,
            'urgency' => $urgency,
            'reported_by' => $actor->id,
        ];

        $this->sendThroughChannels($admins, $title, $body, 'admin_river_alert', $data);

        return $admins->count();
    }

    public function notifyAdminsForUpgradeRequest(RoleUpgradeRequests $application, User $actor): int
    {
        $admins = $this->usersByRoles(['admin']);
        if ($admins->isEmpty()) {
            return 0;
        }

        $title = 'Pengajuan upgrade influencer baru';
        $body = sprintf(
            'User %s mengirim pengajuan upgrade influencer.',
            $actor->email
        );

        $data = [
            'application_id' => $application->id,
            'user_id' => $actor->id,
        ];

        $this->sendThroughChannels($admins, $title, $body, 'admin_upgrade_request', $data);

        return $admins->count();
    }

    public function notifyPegiatForNewEvent(Event $event): int
    {
        if ($event->status !== 'approved') {
            return 0;
        }

        $targets = $this->usersByRoles(['user', 'pegiat']);
        if ($targets->isEmpty()) {
            return 0;
        }

        $title = 'Event baru tersedia';
        $body = sprintf('Event "%s" sudah tersedia. Yuk ikutan!', $event->title);

        $data = [
            'event_id' => $event->id,
            'event_date' => optional($event->event_date)->toIso8601String(),
            'location_name' => $event->location_name,
        ];

        $this->sendThroughChannels($targets, $title, $body, 'event_new', $data);

        return $targets->count();
    }

    public function notifyNearbyUsersForRiverAlert(RiverReport $report): int
    {
        $urgency = strtolower((string) $report->urgency);
        if (!in_array($urgency, ['warning', 'urgent'], true)) {
            return 0;
        }

        $latitude = $report->latitude ?? $report->river?->latitude;
        $longitude = $report->longitude ?? $report->river?->longitude;

        if ($latitude === null || $longitude === null) {
            return 0;
        }

        $radiusKm = 1.0;
        $recipients = collect();

        // Pegiat/user hanya menerima urgent.
        if ($urgency === 'urgent') {
            $pegiatUsers = $this->usersWithinRadiusByRoles((float) $latitude, (float) $longitude, $radiusKm, ['user', 'pegiat']);
            $recipients = $recipients->merge($pegiatUsers);
        }

        // Influencer menerima warning dan urgent.
        $influencers = $this->usersWithinRadiusByRoles((float) $latitude, (float) $longitude, $radiusKm, ['influencer']);
        $recipients = $recipients->merge($influencers);

        $recipients = $recipients
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $report->user_id)
            ->values();

        if ($recipients->isEmpty()) {
            return 0;
        }

        $riverName = $report->river?->name ?? ('River #' . $report->river_id);
        $title = $urgency === 'urgent'
            ? 'Peringatan sungai urgent di sekitar Anda'
            : 'Peringatan sungai warning di sekitar Anda';

        $body = sprintf(
            'Status %s terdeteksi untuk %s dalam radius 1 KM dari lokasi Anda.',
            strtoupper($urgency),
            $riverName
        );

        $data = [
            'river_report_id' => $report->id,
            'river_id' => $report->river_id,
            'urgency' => $urgency,
            'radius_km' => $radiusKm,
            'center_latitude' => (float) $latitude,
            'center_longitude' => (float) $longitude,
        ];

        $this->sendThroughChannels($recipients, $title, $body, 'river_nearby_alert', $data);

        return $recipients->count();
    }

    public function sendCustomNotification(array $payload): int
    {
        $targets = $this->resolveTargets(
            $payload['target_type'],
            $payload['role'] ?? null,
            $payload['user_ids'] ?? []
        );

        if ($targets->isEmpty()) {
            return 0;
        }

        $this->sendThroughChannels(
            $targets,
            $payload['title'],
            $payload['body'],
            $payload['category'] ?? 'custom',
            $payload['data'] ?? []
        );

        return $targets->count();
    }

    private function resolveTargets(string $targetType, ?string $roleName = null, array $userIds = []): Collection
    {
        return match ($targetType) {
            'admins' => $this->usersByRoles(['admin']),
            'role' => $this->usersByRoles(array_filter([(string) $roleName])),
            'users' => User::query()->whereIn('id', $userIds)->get(),
            default => User::query()->get(),
        };
    }

    private function usersByRoles(array $roleNames): Collection
    {
        if (empty($roleNames)) {
            return collect();
        }

        return User::query()
            ->whereHas('role', function ($query) use ($roleNames) {
                $query->whereIn('name', $roleNames);
            })
            ->get();
    }

    private function usersWithinRadiusByRoles(float $latitude, float $longitude, float $radiusKm, array $roleNames): Collection
    {
        if (empty($roleNames)) {
            return collect();
        }

        $distanceExpression = '(6371 * acos(cos(radians(?)) * cos(radians(user_profiles.latitude)) * cos(radians(user_profiles.longitude) - radians(?)) + sin(radians(?)) * sin(radians(user_profiles.latitude))))';

        return User::query()
            ->select('users.*')
            ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn('roles.name', $roleNames)
            ->whereNotNull('user_profiles.latitude')
            ->whereNotNull('user_profiles.longitude')
            ->selectRaw($distanceExpression . ' as distance_km', [$latitude, $longitude, $latitude])
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km')
            ->get();
    }

    private function sendThroughChannels(Collection $targets, string $title, string $body, string $category, array $data = []): void
    {
        Notification::send(
            $targets,
            new SystemPushNotification($title, $body, $category, $data)
        );

        $this->pushNotificationService->sendToUsers($targets, $title, $body, $category, $data);
    }
}
