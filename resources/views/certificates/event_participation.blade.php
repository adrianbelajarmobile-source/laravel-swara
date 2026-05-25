<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificate</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            background: #ffffff;
        }

        .page {
            width: 1123px;
            height: 794px;
            position: relative;
            box-sizing: border-box;
        }

        /* OUTER FRAME */
        .border-outer {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 10px solid #14532d;
        }

        /* INNER FRAME */
        .border-inner {
            position: absolute;
            top: 35px;
            left: 35px;
            right: 35px;
            bottom: 35px;
            border: 2px solid #facc15;
        }

        /* WATERMARK */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 340px;
            opacity: 0.06;
            z-index: 0;
        }

        .watermark img {
            width: 100%;
            height: auto;
            display: block;
        }

        .content {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 100px 120px;
        }

        .title {
            font-size: 42px;
            font-weight: bold;
            letter-spacing: 4px;
            color: #064e3b;
        }

        .subtitle {
            font-size: 14px;
            letter-spacing: 5px;
            color: #6b7280;
            margin-top: 10px;
        }

        .divider {
            width: 140px;
            height: 4px;
            background: #facc15;
            margin: 25px auto;
        }

        .label {
            font-size: 16px;
            color: #6b7280;
            margin-top: 20px;
        }

        .name {
            font-size: 48px;
            font-weight: bold;
            margin: 25px 0;
            color: #111827;
        }

        .event {
            font-size: 26px;
            font-weight: bold;
            color: #065f46;
            margin-bottom: 20px;
        }

        .meta {
            font-size: 14px;
            color: #374151;
            margin: 4px 0;
        }

        /* SIGNATURE AREA */
        .signature-area {
            position: absolute;
            bottom: 100px;
            left: 120px;
            right: 120px;
        }

        .signature-table {
            width: 100%;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            font-size: 13px;
        }

        .sign-line {
            margin-top: 60px;
            border-top: 1px solid #000;
            padding-top: 6px;
        }

        /* SEAL (badge bulat) */
        .seal {
            position: absolute;
            bottom: 90px;
            right: 120px;
            width: 90px;
            height: 90px;
            border: 3px solid #facc15;
            border-radius: 50%;
            text-align: center;
            font-size: 10px;
            color: #b45309;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer {
            position: absolute;
            bottom: 40px;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: #6b7280;
        }

        .certificate-id {
            font-family: monospace;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="border-outer"></div>
        <div class="border-inner"></div>

        <div class="watermark">
            <img src="{{ public_path('images/logo.png') }}" alt="Watermark Logo">
        </div>

        <div class="content">
            {{-- LOGO --}}
            {{-- <img src="{{ public_path('logo.png') }}" height="70"> --}}

            <div class="title">CERTIFICATE OF PARTICIPATION</div>
            <div class="subtitle">SWARA COMMUNITY EVENT</div>

            <div class="divider"></div>

            <div class="label">This certificate is proudly presented to</div>
            <div class="name">{{ $name }}</div>

            <div class="label">for successfully participating in</div>
            <div class="event">{{ $event->title }}</div>

            <div class="meta">Date: {{ $event->event_date?->format('d F Y') }}</div>
            <div class="meta">Checked Out: {{ $participant->checked_out_at?->format('d F Y H:i') }}</div>
            <div class="meta">Points Earned: {{ $participant->points_earned ?? 0 }}</div>
        </div>

        <div class="footer">
            Generated at {{ $issuedAt->format('d F Y H:i') }}
            <div class="certificate-id">
                CERT-{{ $participant->event_id }}-{{ $participant->id }}
            </div>
        </div>
    </div>
</body>
</html>