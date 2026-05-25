<?php

namespace App\Services;

use App\Models\UserDeviceToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PushNotificationService
{
    public function sendToUsers(Collection $users, string $title, string $body, string $category, array $data = []): int
    {
        if ($users->isEmpty()) {
            return 0;
        }

        $userIds = $users->pluck('id')->filter()->values();
        if ($userIds->isEmpty()) {
            return 0;
        }

        $tokens = UserDeviceToken::query()
            ->whereIn('user_id', $userIds)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return 0;
        }

        if ($this->canUseHttpV1()) {
            return $this->sendUsingHttpV1($tokens, $title, $body, $category, $data);
        }

        return $this->sendUsingLegacy($tokens, $title, $body, $category, $data);
    }

    private function canUseHttpV1(): bool
    {
        $projectId = (string) config('services.fcm.project_id');
        $serviceAccountPath = (string) config('services.fcm.service_account_json');

        return $projectId !== '' && $serviceAccountPath !== '' && is_file($serviceAccountPath);
    }

    private function sendUsingHttpV1(Collection $tokens, string $title, string $body, string $category, array $data = []): int
    {
        $serviceAccount = $this->loadServiceAccount();
        if ($serviceAccount === null) {
            return 0;
        }

        $projectId = (string) config('services.fcm.project_id', $serviceAccount['project_id'] ?? '');
        if ($projectId === '') {
            return 0;
        }

        $accessToken = $this->fetchAccessToken($serviceAccount);
        if ($accessToken === null) {
            return 0;
        }

        $endpoint = (string) config('services.fcm.v1_endpoint');
        if ($endpoint === '') {
            $endpoint = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';
        } else {
            $endpoint = str_replace('{project_id}', $projectId, $endpoint);
        }

        $baseData = $this->normalizeData(array_merge($data, [
            'category' => $category,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ]));

        $sentCount = 0;

        foreach ($tokens->values() as $deviceToken) {
            $payload = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $baseData,
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'sound' => 'default',
                        ],
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)->post($endpoint, $payload);
            if ($response->successful()) {
                $sentCount++;
                continue;
            }

            // Retry once with fresh access token when token is expired/invalid.
            if (in_array($response->status(), [401, 403], true)) {
                $refreshedToken = $this->fetchAccessToken($serviceAccount);
                if ($refreshedToken !== null) {
                    $accessToken = $refreshedToken;
                    $retry = Http::withToken($accessToken)->post($endpoint, $payload);
                    if ($retry->successful()) {
                        $sentCount++;
                    }
                }
            }
        }

        return $sentCount;
    }

    private function sendUsingLegacy(Collection $tokens, string $title, string $body, string $category, array $data = []): int
    {
        $serverKey = (string) config('services.fcm.server_key');
        $endpoint = (string) config('services.fcm.endpoint', 'https://fcm.googleapis.com/fcm/send');

        if ($serverKey === '') {
            return 0;
        }

        $sentCount = 0;

        foreach ($tokens->chunk(500) as $tokenChunk) {
            $chunkValues = $tokenChunk->values()->all();

            $payload = [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => array_merge($data, [
                    'category' => $category,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]),
                'priority' => 'high',
            ];

            if (count($chunkValues) === 1) {
                $payload['to'] = $chunkValues[0];
            } else {
                $payload['registration_ids'] = $chunkValues;
            }

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payload);

            if ($response->successful()) {
                $sentCount += count($chunkValues);
            }
        }

        return $sentCount;
    }

    private function loadServiceAccount(): ?array
    {
        $path = (string) config('services.fcm.service_account_json');
        if ($path === '' || !is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        if (empty($decoded['client_email']) || empty($decoded['private_key'])) {
            return null;
        }

        return $decoded;
    }

    private function fetchAccessToken(array $serviceAccount): ?string
    {
        $now = time();

        $jwtHeader = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_UNESCAPED_SLASHES));

        $jwtClaims = $this->base64UrlEncode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'sub' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_UNESCAPED_SLASHES));

        $unsignedToken = $jwtHeader . '.' . $jwtClaims;

        $signature = '';
        $signed = openssl_sign($unsignedToken, $signature, $serviceAccount['private_key'], 'sha256WithRSAEncryption');

        if (!$signed) {
            return null;
        }

        $assertion = $unsignedToken . '.' . $this->base64UrlEncode($signature);

        $response = Http::asForm()->post(
            $serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]
        );

        if (!$response->successful()) {
            return null;
        }

        $accessToken = $response->json('access_token');

        return is_string($accessToken) && $accessToken !== '' ? $accessToken : null;
    }

    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_scalar($value)) {
                $normalized[(string) $key] = (string) $value;
                continue;
            }

            $encoded = json_encode($value);
            if ($encoded !== false) {
                $normalized[(string) $key] = $encoded;
            }
        }

        return $normalized;
    }

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }
}
