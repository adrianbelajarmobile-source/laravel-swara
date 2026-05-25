<?php

namespace App\Jobs;

use App\Models\EventParticipant;
use App\Services\EventCertificateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateEventCertificateJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $participantId)
    {
    }

    public function handle(EventCertificateService $service): void
    {
        $participant = EventParticipant::query()->find($this->participantId);
        if (!$participant) {
            return;
        }

        if (!$participant->isCheckedOut()) {
            $participant->update([
                'certificate_status' => EventParticipant::CERTIFICATE_FAILED,
            ]);
            return;
        }

        if (
            $participant->certificate_path &&
            $participant->certificate_status === EventParticipant::CERTIFICATE_READY
        ) {
            return;
        }

        try {
            $path = $service->generate($participant);

            $participant->update([
                'certificate_status' => EventParticipant::CERTIFICATE_READY,
                'certificate_path' => $path,
                'certificate_generated_at' => now(),
            ]);
        } catch (Throwable $e) {
            $participant->update([
                'certificate_status' => EventParticipant::CERTIFICATE_FAILED,
            ]);

            report($e);
        }
    }
}
