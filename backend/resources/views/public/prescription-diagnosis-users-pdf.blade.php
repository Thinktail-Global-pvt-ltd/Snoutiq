<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px; }
        body {
            color: #172033;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.38;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }
        .muted { color: #64748b; }
        .header {
            border-bottom: 2px solid #111827;
            margin-bottom: 14px;
            padding-bottom: 10px;
        }
        .badge {
            background: #111827;
            border-radius: 4px;
            color: #fff;
            display: inline-block;
            font-size: 9px;
            font-weight: bold;
            padding: 3px 7px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background: #f1f5f9;
            border: 1px solid #d8dee8;
            color: #475569;
            font-size: 9px;
            letter-spacing: .04em;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
            vertical-align: top;
        }
        td {
            border: 1px solid #d8dee8;
            padding: 8px;
            vertical-align: top;
            word-wrap: break-word;
        }
        tr { page-break-inside: avoid; }
        .strong { font-weight: bold; }
        .diagnosis {
            font-weight: bold;
            white-space: pre-wrap;
        }
        .symptom {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>AI Diagnosis vs Doctor Diagnosis vs Patient Symptom</h1>
        <div class="muted">Generated {{ $generatedAt->format('d M Y, h:i A') }} IST</div>
        <div style="margin-top: 7px;"><span class="badge">Gemini 2.5 Flash</span></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 14%;">Prescription</th>
                <th style="width: 16%;">Patient</th>
                <th style="width: 28%;">Patient actual symptom</th>
                <th style="width: 21%;">Doctor diagnosis</th>
                <th style="width: 21%;">AI diagnosis</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php
                    $prescription = $row['prescription'];
                    $pet = $row['pet'];
                    $user = $row['user'];
                @endphp
                <tr>
                    <td>
                        <div class="strong">#{{ $prescription->id }}</div>
                        <div class="muted">{{ optional($prescription->created_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? 'Date unavailable' }}</div>
                    </td>
                    <td>
                        <div class="strong">{{ $pet->name ?? 'Pet unavailable' }}</div>
                        <div class="muted">{{ collect([$pet?->pet_type ?? $pet?->type ?? null, $pet?->breed ?? null, $pet?->pet_age ? $pet->pet_age . ' yrs' : null, $pet?->pet_gender ?? null])->filter()->join(' | ') ?: 'Pet details unavailable' }}</div>
                        <div style="margin-top: 5px;">{{ $user->name ?? 'User #' . $prescription->user_id }}</div>
                    </td>
                    <td class="symptom">{{ $row['reported_symptom'] !== '' ? $row['reported_symptom'] : 'No reported symptom' }}</td>
                    <td class="diagnosis">{{ $row['doctor_diagnosis'] }}</td>
                    <td class="diagnosis">{{ $row['ai_diagnosis'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
