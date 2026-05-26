<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #172033;
            font-size: 12px;
            line-height: 1.45;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }
        h2 {
            font-size: 15px;
            margin: 0 0 8px;
        }
        .muted { color: #64748b; }
        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 4px;
            background: #111827;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
        }
        .card {
            border: 1px solid #d8dee8;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .meta-table,
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .meta-table td,
        .comparison-table td {
            border: 1px solid #d8dee8;
            vertical-align: top;
            padding: 9px;
        }
        .label {
            display: block;
            color: #64748b;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: .04em;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .strong { font-weight: bold; }
        .diagnosis {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .summary {
            background: #f8fafc;
            border: 1px solid #d8dee8;
            border-radius: 6px;
            padding: 10px;
            margin-top: 10px;
        }
        .source-image {
            max-width: 235px;
            max-height: 170px;
            border: 1px solid #d8dee8;
            border-radius: 6px;
            margin-top: 6px;
        }
        ul { margin: 4px 0 0 18px; padding: 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Doctor vs AI Diagnosis Comparison</h1>
        <div class="muted">Transactions 855 and 866 &middot; Generated {{ $generatedAt->format('d M Y, h:i A') }} IST</div>
        <div style="margin-top: 8px;"><span class="badge">Gemini 2.5 Flash</span></div>
    </div>

    @foreach($rows as $row)
        @php
            $transaction = $row['transaction'];
            $user = $row['user'];
            $doctor = $row['doctor'];
            $pet = $row['pet'];
            $comparisonPayload = $row['comparison_payload'] ?? null;
            $comparison = is_array($comparisonPayload) ? ($comparisonPayload['comparison'] ?? []) : [];
            $aiOk = is_array($comparisonPayload) && ($comparisonPayload['success'] ?? false);
        @endphp
        <section class="card">
            <h2>Transaction #{{ $transaction->id }}</h2>
            <div class="muted">{{ optional($transaction->created_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? 'Date unavailable' }}</div>

            <table class="meta-table">
                <tr>
                    <td width="25%">
                        <span class="label">Pet</span>
                        <div class="strong">{{ $pet->name ?? 'Pet unavailable' }}</div>
                        <div class="muted">{{ collect([$pet?->pet_type ?? $pet?->type ?? null, $pet?->breed ?? null, $pet?->pet_age ? $pet->pet_age . ' yrs' : null, $pet?->pet_gender ?? null])->filter()->join(' | ') ?: 'Details unavailable' }}</div>
                    </td>
                    <td width="25%">
                        <span class="label">Doctor</span>
                        <div class="strong">{{ $doctor->doctor_name ?? 'Doctor unavailable' }}</div>
                        <div class="muted">{{ collect([$doctor?->degree ?? null, $doctor?->doctor_license ?? null])->filter()->join(' | ') ?: 'Details unavailable' }}</div>
                    </td>
                    <td width="25%">
                        <span class="label">User</span>
                        <div class="strong">{{ $user->name ?? 'User #' . $transaction->user_id }}</div>
                        <div class="muted">{{ collect([$user?->phone ?? null, $user?->email ?? null])->filter()->join(' | ') ?: 'Contact unavailable' }}</div>
                    </td>
                    <td width="25%">
                        <span class="label">Image used</span>
                        @forelse($row['image_documents'] as $document)
                            <div class="strong">{{ $document['label'] }}</div>
                            <div class="muted">{{ $document['mime_type'] }}</div>
                        @empty
                            <div class="muted">No image/report blob available.</div>
                        @endforelse
                    </td>
                </tr>
            </table>

            @if(!empty($row['reported_symptom']))
                <div class="summary">
                    <span class="label">Reported symptom</span>
                    <div class="diagnosis">{{ $row['reported_symptom'] }}</div>
                </div>
            @endif

            <table class="comparison-table">
                <tr>
                    <td width="50%">
                        <span class="label">Doctor diagnosis</span>
                        @forelse($row['doctor_diagnoses'] as $diagnosis)
                            <div class="diagnosis strong">{{ $diagnosis }}</div>
                        @empty
                            <div class="muted">No prescription diagnosis found.</div>
                        @endforelse
                    </td>
                    <td width="50%">
                        <span class="label">AI diagnosis</span>
                        @if($aiOk)
                            <div class="diagnosis strong">{{ $comparison['ai_diagnosis'] ?? 'Limited-evidence AI impression: veterinary consultation recommended for further assessment' }}</div>
                            <div class="muted">Confidence: {{ $comparison['confidence'] ?? 'low' }}</div>
                            <span class="label" style="margin-top: 8px;">AI basis</span>
                            @if(!empty($comparison['basis']) && is_array($comparison['basis']))
                                <ul>
                                    @foreach($comparison['basis'] as $basis)
                                        <li>{{ $basis }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="muted">No AI basis returned.</div>
                            @endif
                        @else
                            <div class="muted">{{ $comparisonPayload['message'] ?? 'Unable to generate AI diagnosis.' }}</div>
                        @endif
                    </td>
                </tr>
            </table>

            @if($aiOk)
                <div class="summary">
                    <span class="label">Comparison</span>
                    <div>{{ $comparison['comparison_summary'] ?? 'AI generated a limited-evidence impression for doctor review.' }}</div>
                    <div class="muted" style="margin-top: 4px;">Status: {{ $comparison['match_status'] ?? 'insufficient_data' }}</div>
                    <div class="muted">{{ $comparison['recommended_review'] ?? 'Use as internal comparison only. A veterinarian should review any mismatch.' }}</div>
                </div>
            @endif

            @if(!empty($row['image_documents']))
                <table class="meta-table">
                    <tr>
                        @foreach($row['image_documents'] as $document)
                            <td>
                                <span class="label">{{ $document['label'] }}</span>
                                @if(!empty($document['data_uri']))
                                    <img src="{{ $document['data_uri'] }}" class="source-image" alt="{{ $document['label'] }}">
                                @else
                                    <div class="muted">Preview unavailable for {{ $document['mime_type'] }}.</div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </table>
            @endif
        </section>
    @endforeach
</body>
</html>
