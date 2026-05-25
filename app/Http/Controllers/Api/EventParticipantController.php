<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateEventCertificateJob;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EventParticipantController extends Controller
{
    /**
     * Join event
     * POST /events/{event}/join
     */
    public function join($event, Request $request)
    {
        $event = Event::findOrFail($event);
        $user = $request->user();

        // Check if already joined
        $existing = EventParticipant::where([
            'event_id' => $event->id,
            'user_id' => $user->id
        ])->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah bergabung dengan event ini'
            ], 400);
        }

        // Check quota
        if (
            $event->quota &&
            $event->participants()->count() >= $event->quota
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota event sudah penuh'
            ], 400);
        }

        $participant = EventParticipant::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'joined'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil bergabung dengan event',
            'data' => $this->formatParticipantResponse($participant->fresh('user.profile'))
        ], 201);
    }

    /**
     * Check-in dengan QR code
     * POST /events/{event}/check-in
     */
    public function checkIn($event, Request $request)
    {
        $request->validate([
            'qr_token' => 'required'
        ]);

        $event = Event::findOrFail($event);
        
        // Verify QR token matches
        if ($event->qr_token !== $request->qr_token) {
            return response()->json([
                'success' => false,
                'message' => 'QR token tidak valid untuk event ini'
            ], 400);
        }
        
        $user = $request->user();

        $participant = EventParticipant::where([
            'event_id' => $event->id,
            'user_id' => $user->id
        ])->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum bergabung dengan event ini'
            ], 400);
        }

        if ($participant->isCheckedIn()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah check-in'
            ], 400);
        }

        $participant->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
            'points_earned' => $event->point_reward ?? 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil',
            'data' => $this->formatParticipantResponse($participant->fresh('user.profile')),
            'qr_code' => $event->qr_token // For checkout verification
        ], 200);
    }

    /**
     * Check-out event (end participation)
     * POST /events/{event}/participants/{participant}/check-out
     */
    public function checkOut($event, $participant, Request $request)
    {
        $event = Event::findOrFail($event);
        $participant = EventParticipant::where([
            'id' => $participant,
            'event_id' => $event->id
        ])->firstOrFail();

        if (!$participant->isCheckedIn()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum check-in atau sudah check-out'
            ], 400);
        }

        $participant->update([
            'status' => 'checked_out',
            'checked_out_at' => now(),
            'certificate_status' => EventParticipant::CERTIFICATE_PROCESSING,
        ]);

        GenerateEventCertificateJob::dispatch($participant->id);

        return response()->json([
            'success' => true,
            'message' => 'Check-out berhasil',
            'data' => $this->formatParticipantResponse($participant->fresh('user.profile', 'media'))
        ], 200);
    }

    /**
     * Get event progress dashboard
     * GET /events/{event}/progress
     */
    public function progress($event)
    {
        $event = Event::findOrFail($event);
        $participants = $event->participants();

        $totalRegistered = $participants->count();
        $checkedIn = $participants->where('status', 'checked_in')->count();
        $checkedOut = $participants->where('status', 'checked_out')->count();

        // Calculate current phase
        $phase = 'not_started';
        if ($event->event_date) {
            $now = now();
            $eventDateTime = $event->event_date->copy();
            
            if ($event->start_time) {
                // Parse start_time and set it
                $timeArray = explode(':', (string)$event->start_time);
                if (count($timeArray) >= 2) {
                    $eventDateTime->setHour($timeArray[0])->setMinute($timeArray[1]);
                }
            }
            
            if ($now > $eventDateTime) {
                $phase = 'in_progress';
            }
            
            if ($event->end_time) {
                $eventEndTime = $event->event_date->copy();
                $timeArray = explode(':', (string)$event->end_time);
                if (count($timeArray) >= 2) {
                    $eventEndTime->setHour($timeArray[0])->setMinute($timeArray[1]);
                }
                if ($now > $eventEndTime) {
                    $phase = 'finished';
                }
            }
        }

        // Get total waste from reports
        $totalWaste = DB::table('event_waste_reports')
            ->where('event_id', $event->id)
            ->sum('total_waste_kg');

        // Get media uploads count
        $mediaUploads = EventMedia::where('event_id', $event->id)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'event_id' => $event->id,
                'event_name' => $event->title,
                'event_date' => $event->event_date->toDateString(),
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'phase' => $phase,
                'progress' => [
                    'total_registered' => $totalRegistered,
                    'checked_in' => $checkedIn,
                    'checked_out' => $checkedOut,
                    'total_waste_kg' => round($totalWaste ?? 0, 2),
                    'media_uploads' => $mediaUploads
                ],
                'percentage' => [
                    'check_in_rate' => $totalRegistered > 0 ? round(($checkedIn / $totalRegistered) * 100, 1) : 0,
                    'check_out_rate' => $totalRegistered > 0 ? round(($checkedOut / $totalRegistered) * 100, 1) : 0
                ]
            ]
        ]);
    }

    /**
     * List participants dengan filter status
     * GET /events/{event}/participants?status=all|joined|checked_in|checked_out
     */
    public function participants($event, Request $request)
    {
        $event = Event::findOrFail($event);

        $query = EventParticipant::where('event_id', $event->id)
            ->with(['user' => function ($q) {
                $q->with('profile:user_id,name,photo_profile');
            }, 'media']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Sorting - whitelist allowed columns
        $allowedSortBy = ['created_at', 'status', 'checked_in_at', 'checked_out_at', 'points_earned'];
        $sortBy = in_array($request->get('sort_by'), $allowedSortBy) ? $request->get('sort_by') : 'created_at';
        $sortOrder = in_array($request->get('sort_order'), ['asc', 'desc']) ? $request->get('sort_order') : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $participants = $query->paginate(20);

        $data = $participants->map(function ($p) {
            return $this->formatParticipantResponse($p);
        });

        // Summary stats
        $stats = [
            'total' => $event->participants()->count(),
            'joined' => $event->participants()->where('status', 'joined')->count(),
            'checked_in' => $event->participants()->where('status', 'checked_in')->count(),
            'checked_out' => $event->participants()->where('status', 'checked_out')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'data' => $data,
            'pagination' => [
                'current_page' => $participants->currentPage(),
                'per_page' => $participants->perPage(),
                'total' => $participants->total(),
                'last_page' => $participants->lastPage(),
            ]
        ]);
    }

    /**
     * Legacy scan endpoint (deprecated, use checkIn instead)
     * POST /events/scan
     */
    public function scan(Request $request)
    {
        $request->validate([
            'qr_token' => 'required'
        ]);

        $event = Event::where('qr_token', $request->qr_token)->firstOrFail();

        $participant = EventParticipant::where([
            'event_id' => $event->id,
            'user_id' => $request->user()->id
        ])->first();

        if (!$participant) {
            return response()->json([
                'message' => 'Belum join event'
            ], 400);
        }

        $participant->update([
            'status' => 'attended',
            'checked_in_at' => now(),
            'points_earned' => $event->point_reward
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Absensi berhasil'
        ]);
    }

    /**
     * Get checked-out event history for authenticated user.
     * GET /events/history/me
     */
    public function myHistory(Request $request)
    {
        $histories = EventParticipant::query()
            ->with(['event', 'media'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'checked_out')
            ->latest('checked_out_at')
            ->paginate((int) $request->get('per_page', 10));

        $data = $histories->map(function (EventParticipant $participant) {
            return $this->formatHistoryResponse($participant);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $histories->currentPage(),
                'per_page' => $histories->perPage(),
                'total' => $histories->total(),
                'last_page' => $histories->lastPage(),
            ]
        ]);
    }

    /**
     * Get certificate status and download URL. Generate certificate on-demand when needed.
     * GET /events/participants/{participant}/certificate
     */
    public function certificate($participant, Request $request)
    {
        $participant = EventParticipant::query()
            ->with(['event', 'user.profile'])
            ->findOrFail($participant);

        if ($participant->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to certificate'
            ], 403);
        }

        if (!$participant->isCheckedOut()) {
            return response()->json([
                'success' => false,
                'message' => 'Sertifikat tersedia setelah check-out event selesai.'
            ], 422);
        }

        if ($participant->certificate_status === EventParticipant::CERTIFICATE_PROCESSING) {
            return response()->json([
                'success' => true,
                'message' => 'Sertifikat sedang diproses',
                'data' => [
                    'participant_id' => $participant->id,
                    'certificate_status' => EventParticipant::CERTIFICATE_PROCESSING,
                    'certificate_generated_at' => null,
                    'download_url' => null,
                ]
            ], 202);
        }

        if ($participant->certificate_path && Storage::disk('public')->exists($participant->certificate_path)) {
            if ($participant->certificate_status !== EventParticipant::CERTIFICATE_READY) {
                $participant->update([
                    'certificate_status' => EventParticipant::CERTIFICATE_READY,
                    'certificate_generated_at' => $participant->certificate_generated_at ?? now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sertifikat siap diunduh',
                'data' => [
                    'participant_id' => $participant->id,
                    'certificate_status' => EventParticipant::CERTIFICATE_READY,
                    'certificate_generated_at' => $participant->certificate_generated_at?->toIso8601String(),
                    'download_url' => '/storage/' . $participant->certificate_path,
                ]
            ]);
        }

        $participant->update([
            'certificate_status' => EventParticipant::CERTIFICATE_PROCESSING,
            'certificate_generated_at' => null,
        ]);

        GenerateEventCertificateJob::dispatch($participant->id);

        return response()->json([
            'success' => true,
            'message' => 'Sertifikat sedang diproses',
            'data' => [
                'participant_id' => $participant->id,
                'certificate_status' => EventParticipant::CERTIFICATE_PROCESSING,
                'certificate_generated_at' => null,
                'download_url' => null,
            ]
        ], 202);
    }

    /**
     * Get lightweight certificate status for polling.
     * GET /events/participants/{participant}/certificate/status
     */
    public function certificateStatus($participant, Request $request)
    {
        $participant = EventParticipant::query()->findOrFail($participant);

        if ($participant->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to certificate status'
            ], 403);
        }

        $isReady = false;
        $downloadUrl = null;

        if ($participant->certificate_status === EventParticipant::CERTIFICATE_PROCESSING) {
            return response()->json([
                'success' => true,
                'data' => [
                    'participant_id' => $participant->id,
                    'event_id' => $participant->event_id,
                    'checked_out' => $participant->isCheckedOut(),
                    'certificate_status' => EventParticipant::CERTIFICATE_PROCESSING,
                    'is_ready' => false,
                    'generated_at' => null,
                    'updated_at' => $participant->updated_at?->toIso8601String(),
                    'download_url' => null,
                ]
            ]);
        }

        if ($participant->certificate_path && Storage::disk('public')->exists($participant->certificate_path)) {
            $isReady = true;
            $downloadUrl = '/storage/' . $participant->certificate_path;

            if ($participant->certificate_status !== EventParticipant::CERTIFICATE_READY) {
                $participant->update([
                    'certificate_status' => EventParticipant::CERTIFICATE_READY,
                    'certificate_generated_at' => $participant->certificate_generated_at ?? now(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'participant_id' => $participant->id,
                'event_id' => $participant->event_id,
                'checked_out' => $participant->isCheckedOut(),
                'certificate_status' => $participant->certificate_status ?? EventParticipant::CERTIFICATE_NOT_GENERATED,
                'is_ready' => $isReady,
                'generated_at' => $participant->certificate_generated_at?->toIso8601String(),
                'updated_at' => $participant->updated_at?->toIso8601String(),
                'download_url' => $downloadUrl,
            ]
        ]);
    }

    /**
     * Admin: bulk generate certificates for checked-out participants in an event.
     * POST /admin/events/{event}/certificates/generate
     */
    public function generateEventCertificates($event, Request $request)
    {
        $validated = $request->validate([
            'force' => 'nullable|boolean',
        ]);

        $force = (bool) ($validated['force'] ?? false);

        $eventModel = Event::findOrFail($event);

        $participantsQuery = EventParticipant::query()
            ->where('event_id', $eventModel->id)
            ->where('status', 'checked_out');

        if (!$force) {
            $participantsQuery->where(function ($query) {
                $query->whereNull('certificate_path')
                    ->orWhere('certificate_status', '!=', EventParticipant::CERTIFICATE_READY);
            });
        }

        $participants = $participantsQuery->get(['id', 'certificate_path']);

        foreach ($participants as $participant) {
            if (
                $force &&
                $participant->certificate_path &&
                Storage::disk('public')->exists($participant->certificate_path)
            ) {
                Storage::disk('public')->delete($participant->certificate_path);
            }

            EventParticipant::where('id', $participant->id)->update([
                'certificate_status' => EventParticipant::CERTIFICATE_PROCESSING,
                'certificate_path' => $force ? null : $participant->certificate_path,
                'certificate_generated_at' => null,
            ]);

            GenerateEventCertificateJob::dispatch($participant->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Proses generate sertifikat dimulai',
            'data' => [
                'event_id' => $eventModel->id,
                'queued_count' => $participants->count(),
                'force' => $force,
            ]
        ]);
    }

    /**
     * Admin: upload certificate template image and layout for an event.
     * POST /admin/events/{event}/certificate-template
     */
    public function updateEventCertificateTemplate($event, Request $request)
    {
        $eventModel = Event::findOrFail($event);

        $request->validate([
            'template_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'layout' => 'nullable',
            'use_default' => 'nullable|boolean',
        ]);

        $useDefault = (bool) $request->boolean('use_default');

        if ($useDefault) {
            if ($eventModel->certificate_template_image_path && Storage::disk('public')->exists($eventModel->certificate_template_image_path)) {
                Storage::disk('public')->delete($eventModel->certificate_template_image_path);
            }

            $eventModel->update([
                'certificate_template_image_path' => null,
                'certificate_template_layout' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template custom dinonaktifkan. Sistem kembali pakai desain default Blade.',
                'data' => [
                    'event_id' => $eventModel->id,
                    'using_default_template' => true,
                    'template_image_path' => null,
                    'layout' => null,
                ]
            ]);
        }

        $layout = $eventModel->certificate_template_layout;

        if ($request->filled('layout')) {
            $layoutInput = $request->input('layout');
            if (is_string($layoutInput)) {
                $decoded = json_decode($layoutInput, true);
                if (!is_array($decoded)) {
                    throw ValidationException::withMessages([
                        'layout' => 'Layout harus JSON object yang valid.',
                    ]);
                }
                $layout = $decoded;
            } elseif (is_array($layoutInput)) {
                $layout = $layoutInput;
            } else {
                throw ValidationException::withMessages([
                    'layout' => 'Format layout tidak valid.',
                ]);
            }
        }

        $templateImagePath = $eventModel->certificate_template_image_path;

        if ($request->hasFile('template_image')) {
            if ($templateImagePath && Storage::disk('public')->exists($templateImagePath)) {
                Storage::disk('public')->delete($templateImagePath);
            }

            $templateImagePath = $request->file('template_image')->store('certificate-templates/events', 'public');
        }

        $eventModel->update([
            'certificate_template_image_path' => $templateImagePath,
            'certificate_template_layout' => $layout,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template sertifikat event berhasil diperbarui.',
            'data' => [
                'event_id' => $eventModel->id,
                'using_default_template' => empty($eventModel->certificate_template_image_path),
                'template_image_path' => $eventModel->certificate_template_image_path ? '/storage/' . $eventModel->certificate_template_image_path : null,
                'layout' => $eventModel->certificate_template_layout,
            ]
        ]);
    }

    /**
     * Admin: return default layout guide payload.
     * GET /admin/events/certificate-template/layout-guide
     */
    public function certificateTemplateLayoutGuide()
    {
        return response()->json([
            'success' => true,
            'message' => 'Panduan layout template sertifikat.',
            'data' => [
                'fields' => [
                    'name' => ['x' => 50, 'y' => 47, 'size' => 40, 'align' => 'center', 'color' => '#111111', 'weight' => 'bold'],
                    'event_title' => ['x' => 50, 'y' => 58, 'size' => 24, 'align' => 'center', 'color' => '#047857', 'weight' => 'bold'],
                    'event_date' => ['x' => 50, 'y' => 65, 'size' => 14, 'align' => 'center', 'color' => '#374151', 'weight' => 'normal'],
                    'checked_out_at' => ['x' => 50, 'y' => 70, 'size' => 12, 'align' => 'center', 'color' => '#374151', 'weight' => 'normal'],
                    'points' => ['x' => 50, 'y' => 74, 'size' => 12, 'align' => 'center', 'color' => '#374151', 'weight' => 'normal'],
                    'issued_at' => ['x' => 15, 'y' => 92, 'size' => 10, 'align' => 'left', 'color' => '#6b7280', 'weight' => 'normal'],
                    'certificate_id' => ['x' => 85, 'y' => 92, 'size' => 10, 'align' => 'right', 'color' => '#6b7280', 'weight' => 'normal'],
                ],
                'notes' => [
                    'x dan y memakai satuan persen (0-100).',
                    'Semua area field di template image harus dikosongkan agar teks auto-generate terlihat.',
                    'Jika template_image kosong, sistem otomatis fallback ke desain default Blade.'
                ],
                'template_guide_image_url' => '/certificate-guides/event-certificate-layout-guide.png',
            ]
        ]);
    }

    /**
     * Format participant response
     */
    private function formatParticipantResponse($participant)
    {
        return [
            'id' => $participant->id,
            'event_id' => $participant->event_id,
            'user_id' => $participant->user_id,
            'user' => [
                'id' => $participant->user->id,
                'email' => $participant->user->email,
                'name' => $participant->user?->profile?->name,
                'photo_profile' => $participant->user?->profile?->photo_profile
            ],
            'status' => $participant->status,
            'joined_at' => $participant->created_at->toIso8601String(),
            'checked_in_at' => $participant->checked_in_at?->toIso8601String(),
            'checked_out_at' => $participant->checked_out_at?->toIso8601String(),
            'check_in_duration_minutes' => $participant->check_in_duration,
            'media_uploads' => $participant->media->count(),
            'points_earned' => $participant->points_earned ?? 0
        ];
    }

    private function formatHistoryResponse(EventParticipant $participant): array
    {
        return [
            'participant_id' => $participant->id,
            'event' => [
                'id' => $participant->event?->id,
                'title' => $participant->event?->title,
                'description' => $participant->event?->description,
                'location_name' => $participant->event?->location_name,
                'event_date' => $participant->event?->event_date?->toIso8601String(),
                'start_time' => $participant->event?->start_time,
                'end_time' => $participant->event?->end_time,
                'photo_path' => $participant->event?->photo_path,
            ],
            'checked_in_at' => $participant->checked_in_at?->toIso8601String(),
            'checked_out_at' => $participant->checked_out_at?->toIso8601String(),
            'points_earned' => $participant->points_earned ?? 0,
            'media_uploads' => $participant->media->count(),
            'certificate' => [
                'status' => $participant->certificate_status ?? EventParticipant::CERTIFICATE_NOT_GENERATED,
                'generated_at' => $participant->certificate_generated_at?->toIso8601String(),
                'download_url' => $participant->certificate_path ? '/storage/' . $participant->certificate_path : null,
                'download_endpoint' => '/api/events/participants/' . $participant->id . '/certificate',
            ],
        ];
    }

}

