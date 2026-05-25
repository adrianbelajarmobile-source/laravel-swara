<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificate Template</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 297mm;
            height: 210mm;
            font-family: DejaVu Sans, sans-serif;
        }

        .page {
            position: relative;
            width: 297mm;
            height: 210mm;
            overflow: hidden;
        }

        .background {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        .field {
            position: absolute;
            transform: translate(-50%, -50%);
            white-space: nowrap;
            line-height: 1.15;
        }

        .align-left {
            transform: translate(0, -50%);
            text-align: left;
        }

        .align-right {
            transform: translate(-100%, -50%);
            text-align: right;
        }
    </style>
</head>
<body>
@php
    $certificateId = 'CERT-' . $participant->event_id . '-' . $participant->id;
    $fields = [
        'name' => $name,
        'event_title' => $event->title,
        'event_date' => 'Date: ' . ($event->event_date?->format('d F Y') ?? '-'),
        'checked_out_at' => 'Checked out: ' . ($participant->checked_out_at?->format('d F Y H:i') ?? '-'),
        'points' => 'Points: ' . ($participant->points_earned ?? 0),
        'issued_at' => 'Generated at ' . $issuedAt->format('d F Y H:i'),
        'certificate_id' => $certificateId,
    ];
@endphp

<div class="page">
    <img class="background" src="{{ $backgroundImagePath }}" alt="Certificate template">

    @foreach($fields as $key => $value)
        @php
            $rule = $layout[$key] ?? null;
            if (!$rule) {
                continue;
            }

            $x = (float) ($rule['x'] ?? 50);
            $y = (float) ($rule['y'] ?? 50);
            $size = (int) ($rule['size'] ?? 12);
            $align = (string) ($rule['align'] ?? 'center');
            $color = (string) ($rule['color'] ?? '#111111');
            $weight = (string) ($rule['weight'] ?? 'normal');

            $alignClass = 'field';
            if ($align === 'left') {
                $alignClass .= ' align-left';
            } elseif ($align === 'right') {
                $alignClass .= ' align-right';
            }
        @endphp

        <div
            class="{{ $alignClass }}"
            style="left: {{ $x }}%; top: {{ $y }}%; font-size: {{ $size }}px; color: {{ $color }}; font-weight: {{ $weight }};"
        >
            {{ $value }}
        </div>
    @endforeach
</div>
</body>
</html>
