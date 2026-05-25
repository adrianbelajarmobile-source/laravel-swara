<?php

namespace App\Services;

use App\Models\EventParticipant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class EventCertificateService
{
    public function generate(EventParticipant $participant): string
    {
        $participant->loadMissing(['event', 'user.profile']);

        $path = $this->buildCertificatePath($participant);

        $event = $participant->event;
        $name = $participant->user?->profile?->name ?? $participant->user->email;
        $issuedAt = now();

        $hasCustomTemplate = $event !== null
            && !empty($event->certificate_template_image_path)
            && Storage::disk('public')->exists($event->certificate_template_image_path);

        if ($hasCustomTemplate) {
            $layout = $this->normalizeLayout($event->certificate_template_layout ?? []);
            $backgroundImagePath = public_path('storage/' . $event->certificate_template_image_path);

            $pdf = Pdf::loadView('certificates.event_participation_template', [
                'participant' => $participant,
                'event' => $event,
                'name' => $name,
                'issuedAt' => $issuedAt,
                'layout' => $layout,
                'backgroundImagePath' => $backgroundImagePath,
            ])->setPaper('a4', 'landscape');
        } else {
            $pdf = Pdf::loadView('certificates.event_participation', [
                'participant' => $participant,
                'event' => $event,
                'name' => $name,
                'issuedAt' => $issuedAt,
            ])->setPaper('a4', 'landscape');
        }

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    public function buildCertificatePath(EventParticipant $participant): string
    {
        return sprintf(
            'certificates/events/%d/participant_%d.pdf',
            $participant->event_id,
            $participant->id
        );
    }

    private function normalizeLayout(array $layout): array
    {
        $default = [
            'name' => ['x' => 50, 'y' => 47, 'size' => 40, 'align' => 'center', 'color' => '#111111'],
            'event_title' => ['x' => 50, 'y' => 58, 'size' => 24, 'align' => 'center', 'color' => '#047857'],
            'event_date' => ['x' => 50, 'y' => 65, 'size' => 14, 'align' => 'center', 'color' => '#374151'],
            'checked_out_at' => ['x' => 50, 'y' => 70, 'size' => 12, 'align' => 'center', 'color' => '#374151'],
            'points' => ['x' => 50, 'y' => 74, 'size' => 12, 'align' => 'center', 'color' => '#374151'],
            'issued_at' => ['x' => 15, 'y' => 92, 'size' => 10, 'align' => 'left', 'color' => '#6b7280'],
            'certificate_id' => ['x' => 85, 'y' => 92, 'size' => 10, 'align' => 'right', 'color' => '#6b7280'],
        ];

        foreach ($default as $key => $value) {
            if (isset($layout[$key]) && is_array($layout[$key])) {
                $default[$key] = array_merge($value, $layout[$key]);
            }
        }

        return $default;
    }
}
