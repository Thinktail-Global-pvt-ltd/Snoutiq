@extends('snoutiq.layout')

@section('head')
<style>
    .symptom-form { display:flex; flex-direction:column; gap:14px; }
    .symptom-form .row { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px; }
    .symptom-form label { font-weight:600; margin-bottom:4px; display:block; }
    .symptom-form input,
    .symptom-form textarea { border:1px solid #d2d6dc; border-radius:6px; font-size:15px; }
    .symptom-form button { background:#1f7ae0; border:none; border-radius:6px; color:white; font-weight:600; }
    .symptom-form button:hover { background:#115bb5; }
    .symptom-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px; margin-top:12px; }
    .symptom-card { background:#f9fafc; border:1px solid #dce3eb; border-radius:10px; padding:18px; box-shadow:0 1px 2px rgba(15,23,42,0.04); }
    .symptom-card h3 { margin-top:0; margin-bottom:10px; font-size:18px; }
    .symptom-meta { display:grid; gap:8px; margin:0; }
    .symptom-meta dt { font-weight:600; color:#1f2937; }
    .symptom-meta dd { margin:0; color:#374151; }
    .symptom-chip { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:13px; font-weight:600; background:#e8f0ff; color:#1d4ed8; margin-right:8px; }
    .symptom-chip.danger { background:#fee2e2; color:#b91c1c; }
    .symptom-chip.info { background:#fef3c7; color:#92400e; }
    .symptom-list { padding-left:18px; margin:0; }
    details summary { cursor:pointer; font-weight:600; margin-bottom:8px; }
    .callout { background:#fff7ed; border:1px solid #f8dfc0; border-radius:10px; padding:16px; margin-top:12px; }
</style>
@endsection

@section('content')
<h2>RAG Snoutic Symptom Checker</h2>

<p class="muted">Fill in the details below and submit to fetch guidance from the remote RAG symptom checker API.</p>

<fieldset>
    <legend>Symptom Input</legend>
    <form method="POST" class="symptom-form">
        @csrf
        <div class="row">
            <div>
                <label for="name">Pet Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $formValues['name'] ?? '') }}">
            </div>
            <div>
                <label for="species">Species</label>
                <input type="text" id="species" name="species" value="{{ old('species', $formValues['species'] ?? '') }}">
            </div>
        </div>
        <div class="row">
            <div>
                <label for="breed">Breed</label>
                <input type="text" id="breed" name="breed" value="{{ old('breed', $formValues['breed'] ?? '') }}">
            </div>
            <div>
                <label for="age">Age</label>
                <input type="text" id="age" name="age" value="{{ old('age', $formValues['age'] ?? '') }}">
            </div>
        </div>
        <div class="row">
            <div>
                <label for="weight">Weight</label>
                <input type="text" id="weight" name="weight" value="{{ old('weight', $formValues['weight'] ?? '') }}">
            </div>
            <div>
                <label for="sex">Sex</label>
                <input type="text" id="sex" name="sex" value="{{ old('sex', $formValues['sex'] ?? '') }}">
            </div>
        </div>
        <label for="vaccination_summary">Vaccination Summary</label>
        <textarea id="vaccination_summary" name="vaccination_summary" rows="2">{{ old('vaccination_summary', $formValues['vaccination_summary'] ?? '') }}</textarea>

        <label for="medical_history">Medical History</label>
        <textarea id="medical_history" name="medical_history" rows="3">{{ old('medical_history', $formValues['medical_history'] ?? '') }}</textarea>

        <label for="query">Current Symptoms / Query</label>
        <textarea id="query" name="query" rows="4">{{ old('query', $formValues['query'] ?? '') }}</textarea>

        <button type="submit">Check Symptoms</button>
    </form>
</fieldset>

@if($requestPayload)
    <fieldset>
        <legend>Request Payload</legend>
        <details open>
            <summary>View payload sent to API</summary>
            <pre>{{ json_encode($requestPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </details>
    </fieldset>
@endif

@if($error)
    <div class="error"><strong>Status:</strong> {{ $error }}</div>
@endif

@if($responseData)
    @php($symptomData = data_get($responseData, 'data', []))

    <fieldset>
        <legend>API Response</legend>
        <div>
            <span class="symptom-chip {{ !data_get($responseData, 'success') ? 'danger' : '' }}">
                Status: {{ data_get($responseData, 'success') ? 'Successful' : 'Failed' }}
            </span>
            @if(!empty($symptomData['urgency_level']))
                <span class="symptom-chip danger">Urgency: {{ ucfirst($symptomData['urgency_level']) }}</span>
            @endif
            @if(!empty($symptomData['confidence']))
                <span class="symptom-chip info">Confidence: {{ ucfirst($symptomData['confidence']) }}</span>
            @endif
        </div>
        @if(!empty($symptomData))
            <div class="symptom-grid">
                <div class="symptom-card">
                    <h3>Pet Snapshot</h3>
                    <dl class="symptom-meta">
                        <div>
                            <dt>Pet Name</dt>
                            <dd>{{ $symptomData['pet_name'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt>Service Recommendation</dt>
                            <dd>{{ $symptomData['service_recommendation'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt>When To See Vet</dt>
                            <dd>{{ $symptomData['when_to_see_vet'] ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="symptom-card">
                    <h3>Summary</h3>
                    <p>{{ $symptomData['summary'] ?? 'N/A' }}</p>
                    @if(!empty($symptomData['what_we_found']))
                        <div class="callout">
                            <strong>What We Found:</strong>
                            <div>{{ $symptomData['what_we_found'] }}</div>
                        </div>
                    @endif
                </div>

                @if(!empty($symptomData['additional_notes']))
                    <div class="symptom-card">
                        <h3>Additional Notes</h3>
                        <p>{{ $symptomData['additional_notes'] }}</p>
                    </div>
                @endif
            </div>

            @php($immediateSteps = $symptomData['immediate_steps'] ?? [])
            @if(!empty($immediateSteps))
                <div class="symptom-card">
                    <h3>Immediate Steps</h3>
                    <ul class="symptom-list">
                        @foreach($immediateSteps as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php($homeCareTips = $symptomData['home_care_tips'] ?? [])
            @if(!empty($homeCareTips))
                <div class="symptom-card">
                    <h3>Home Care Tips</h3>
                    <ul class="symptom-list">
                        @foreach($homeCareTips as $tip)
                            <li>{{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @else
            <p>No structured symptom details were returned.</p>
        @endif
    </fieldset>

    <fieldset>
        <legend>Raw API Response</legend>
        <details>
            <summary>View raw JSON</summary>
            <pre>{{ json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </details>
    </fieldset>
@endif
@endsection
