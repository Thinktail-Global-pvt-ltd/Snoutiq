@php
    $slotMatrixTitle = $slotMatrixTitle ?? 'Hourly Coverage by Pincode';
    $slotMatrixDescription = $slotMatrixDescription ?? 'Review pincode-level availability for the selected date.';
    $slotMatrixBadge = $slotMatrixBadge ?? 'Data refreshes on demand';
    $slotMatrixCardClass = $slotMatrixCardClass ?? 'card shadow-sm border-0';
    $slotMatrixShowControls = $slotMatrixShowControls ?? false;
    $slotMatrixDateLabel = $slotMatrixDateLabel ?? 'Date (IST)';
    $slotMatrixRefreshLabel = $slotMatrixRefreshLabel ?? 'Refresh Grid';
    $slotMatrixHint = $slotMatrixHint ?? 'Each cell shows the number of doctors scheduled in that hour for the selected pincode.';
@endphp

<div class="{{ $slotMatrixCardClass }} mt-4" data-video-slot-matrix>
    <div class="card-header bg-transparent border-bottom-0 pb-0">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2">
            <div>
                <h5 class="mb-1">{{ $slotMatrixTitle }}</h5>
                <p class="text-muted small mb-0">{{ $slotMatrixDescription }}</p>
            </div>
            <span class="badge text-bg-secondary align-self-start">{{ $slotMatrixBadge }}</span>
        </div>
    </div>
    <div class="card-body">
        @if($slotMatrixShowControls)
            <div class="d-flex flex-column flex-lg-row align-items-lg-end gap-3 mb-3">
                <div class="w-100 w-lg-auto" style="max-width: 240px;">
                    <label for="slotDate" class="form-label">{{ $slotMatrixDateLabel }}</label>
                    <input type="date" id="slotDate" class="form-control">
                </div>
                <div class="w-100 w-lg-auto">
                    <label class="form-label opacity-0">{{ $slotMatrixRefreshLabel }}</label>
                    <button id="btnSlotRefresh" class="btn btn-primary w-100">{{ $slotMatrixRefreshLabel }}</button>
                </div>
                <div class="flex-grow-1 small text-muted">
                    {{ $slotMatrixHint }}
                </div>
            </div>
        @endif
        <div id="slotSummary" class="mb-3 small text-muted"></div>
        <div id="slotMatrixWrap" class="table-responsive"></div>
    </div>
</div>

@once
    <script>
        (function () {
            const pathnamePrefix = (() => {
                const path = window.location.pathname || '';
                if (path.startsWith('/backend/')) return '/backend';
                if (path === '/backend' || path === '/backend/') return '/backend';
                return '';
            })();
            const ADMIN_API_BASE = `${window.location.origin}${pathnamePrefix}/api/admin`;
            const el = (selector) => document.querySelector(selector);
            const hours = Array.from({ length: 24 }, (_, i) => i);

            function fmtInt(value) {
                return Number(value || 0).toLocaleString('en-IN');
            }

            function renderEmpty(message) {
                const wrap = el('#slotMatrixWrap');
                if (wrap) {
                    wrap.innerHTML = `<div class="text-muted">${message}</div>`;
                }
            }

            function renderSummary(summary, dateStr) {
                const target = el('#slotSummary');
                if (!target) return;
                if (!summary) {
                    target.textContent = '';
                    return;
                }
                target.innerHTML = `
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge text-bg-light">Date: <strong>${dateStr}</strong></span>
                        <span class="badge text-bg-primary">Doctor hours: ${fmtInt(summary.doctor_hours)}</span>
                        <span class="badge text-bg-success">Unique doctors scheduled: ${fmtInt(summary.unique_doctors)}</span>
                    </div>
                `;
            }

            function renderMatrix(rows) {
                const wrap = el('#slotMatrixWrap');
                if (!wrap) return;
                if (!rows || !rows.length) {
                    renderEmpty('No availability or bookings found for this date.');
                    return;
                }

                let html = '<table class="table table-bordered align-middle mb-0">';
                html += '<thead class="table-light"><tr>';
                html += '<th>Pincode</th>';
                html += '<th>Clinics</th>';
                html += '<th class="text-center">Totals</th>';
                hours.forEach((h) => {
                    const label = String(h).padStart(2, '0') + ':00';
                    html += `<th class="text-center">${label}</th>`;
                });
                html += '</tr></thead><tbody>';

                rows.forEach((row) => {
                    const clinics = (row.clinics || []).join(', ') || '—';
                    html += '<tr>';
                    html += `<td class="fw-semibold">${row.pincode ?? '—'}</td>`;
                    html += `<td class="small">${clinics}</td>`;
                    const totalDoctors = row.totals?.unique_doctors ?? 0;
                    const docHours = row.totals?.doctor_hours ?? 0;
                    html += `<td class="text-center">
                                <div class="fw-semibold">${fmtInt(totalDoctors)} doctor(s)</div>
                                <div class="text-muted small">Doctor-hours: ${fmtInt(docHours)}</div>
                            </td>`;

                    hours.forEach((h) => {
                        const cell = row.hours ? (row.hours[h] ?? row.hours[String(h)]) : null;
                        const count = cell?.count ?? 0;
                        if (!count) {
                            html += '<td class="text-center text-muted">—</td>';
                            return;
                        }
                        html += `<td class="text-center">
                                    <span class="badge text-bg-success">${count} doc${count === 1 ? '' : 's'}</span>
                                </td>`;
                    });
                    html += '</tr>';
                });

                html += '</tbody></table>';
                wrap.innerHTML = html;
            }

            async function fetchMatrix() {
                const dateInput = el('#slotDate');
                const date = dateInput?.value;
                if (!date) {
                    renderEmpty('Select a date to load data.');
                    return;
                }
                renderEmpty('Loading coverage …');
                try {
                    const res = await fetch(`${ADMIN_API_BASE}/video/pincode-slots?date=${encodeURIComponent(date)}`, { credentials: 'include' });
                    if (!res.ok) {
                        throw new Error(`Request failed (${res.status})`);
                    }
                    const data = await res.json();
                    renderSummary(data?.summary ?? null, data?.date ?? date);
                    renderMatrix(data?.rows ?? []);
                } catch (err) {
                    console.error(err);
                    renderEmpty('Failed to load slot data. Please try again.');
                    renderSummary(null, '');
                }
            }

            document.addEventListener('DOMContentLoaded', () => {
                const today = new Date();
                const pad = (n) => String(n).padStart(2, '0');
                const dateStr = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`;
                const dateInput = el('#slotDate');
                if (dateInput) {
                    if (!dateInput.value) {
                        dateInput.value = dateStr;
                    }
                    dateInput.addEventListener('change', fetchMatrix);
                }
                const refreshBtn = el('#btnSlotRefresh');
                if (refreshBtn) {
                    refreshBtn.addEventListener('click', fetchMatrix);
                }
                fetchMatrix();
            });
        })();
    </script>
@endonce
