@extends('layouts.admin-panel')

@section('page-title', 'Pincode Heatmap')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    .heatmap-controls {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1rem;
        border: 1px solid #e2e8f0;
    }
    .heatmap-grid-card {
        border-radius: 18px;
        background: #fff;
        border: 1px solid #e2e8f0;
        padding: 1.25rem;
        min-height: 220px;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        transition: transform 0.4s ease, box-shadow 0.4s ease;
        animation: floatCard 4s ease-in-out infinite;
    }
    .heatmap-card-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.5rem;
    }
    @keyframes floatCard {
        0% {
            transform: translateY(0);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        }
        50% {
            transform: translateY(-4px);
            box-shadow: 0 26px 48px rgba(15, 23, 42, 0.18);
        }
        100% {
            transform: translateY(0);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        }
    }
    .heatmap-card-header .pincode {
        font-size: 1.4rem;
        font-weight: 700;
    }
    .heatmap-card-header .badge-sm {
        font-size: 0.65rem;
        letter-spacing: 0.05em;
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
    }
    .heatmap-card-clinics {
        font-size: 0.85rem;
        color: #475569;
        min-height: 2.5rem;
    }
    .heatbar-track {
        height: 10px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .heatbar-fill {
        height: 100%;
        border-radius: 999px;
    }
    .heatmap-stats {
        font-size: 0.8rem;
        color: #475569;
        display: flex;
        justify-content: space-between;
    }
    .heatmap-legend {
        border-radius: 16px;
        border: 1px dashed #cbd5f5;
        padding: 0.75rem 1rem;
        background: #f8fafc;
    }
    .heatmap-gradient {
        height: 6px;
        border-radius: 999px;
        background: linear-gradient(90deg, #dbeafe, #22c55e, #15803d);
        margin: 0.35rem 0;
    }
    .heatmap-map-card {
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        background: #fff;
        padding: 1rem;
        box-shadow: 0 24px 40px rgba(2, 6, 23, 0.09);
        min-height: 400px;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .heatmap-map-card-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.85rem;
        color: #475569;
    }
    .heatmap-map-card-title span {
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .heatmap-map {
        height: 360px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        background: #030712;
    }
    .heatmap-map .leaflet-tile {
        filter: grayscale(1) brightness(0.4) contrast(1.05) saturate(0.8);
    }
    .heatmap-grid .heatmap-grid-card {
        height: 100%;
    }
    .heatmap-marker {
        stroke-width: 3 !important;
        filter: drop-shadow(0 6px 16px rgba(15, 23, 42, 0.35));
        animation: pulse 1.8s infinite ease-in-out;
    }
    @media (max-width: 991px) {
        .heatmap-map {
            height: 280px;
        }
    }
    @keyframes pulse {
        0% {
            stroke-width: 2;
            opacity: 0.9;
        }
        50% {
            stroke-width: 7;
            opacity: 0.35;
        }
        100% {
            stroke-width: 2;
            opacity: 0.9;
        }
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="card admin-card border-0">
        <div class="card-body heatmap-controls">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="heatmapDate" class="form-label small text-uppercase">Date (IST)</label>
                    <input type="date" id="heatmapDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label for="heatmapFilter" class="form-label small text-uppercase">Pincode or clinic</label>
                    <input type="text" id="heatmapFilter" class="form-control form-control-sm" placeholder="Type to filter results">
                </div>
                <div class="col-md-2">
                    <button id="heatmapRefresh" class="btn btn-primary w-100">Refresh Heatmap</button>
                </div>
                <div class="col-md-4">
                    <div id="heatmapSummary" class="small text-muted"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="card admin-card border-0">
        <div class="card-header bg-transparent border-bottom-0 pb-0">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2">
                <div>
                    <h5 class="mb-1">Pincode Heatmap</h5>
                    <p class="text-muted small mb-0">Visualize scheduled video doctor-hours per pincode for the selected date.</p>
                </div>
                <span class="badge text-bg-secondary align-self-start">Updated on demand</span>
            </div>
        </div>
        <div class="card-body">
            <div class="heatmap-legend">
                <strong class="d-block mb-1">Intensity represents doctor-hours across video slots</strong>
                <div class="heatmap-gradient"></div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>Lower activity</span>
                    <span>Higher activity</span>
                </div>
            </div>
            <div class="row g-4 mt-3 align-items-start">
                <div class="col-lg-7">
                    <div class="heatmap-map-card">
                        <div class="heatmap-map-card-title">
                            <span>Map View</span>
                            <small>Markers sized by doctor-hours</small>
                        </div>
                        <div id="heatmapMap" class="heatmap-map"></div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div id="heatmapMessage" class="text-muted small mb-3">Choose a date and refresh to load the data.</div>
                    <div class="row g-3 heatmap-grid" id="heatmapGrid"></div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    (function () {
        const pathPrefix = (() => {
            const path = window.location.pathname || '';
            if (path.startsWith('/backend/')) return '/backend';
            if (path === '/backend' || path === '/backend/') return '/backend';
            return '';
        })();
        const ADMIN_API_BASE = `${window.location.origin}${pathPrefix}/api/admin`;
        const dateInput = document.getElementById('heatmapDate');
        const filterInput = document.getElementById('heatmapFilter');
        const refreshButton = document.getElementById('heatmapRefresh');
        const summaryTarget = document.getElementById('heatmapSummary');
        const messageTarget = document.getElementById('heatmapMessage');
        const gridTarget = document.getElementById('heatmapGrid');
        const mapElement = document.getElementById('heatmapMap');

        let heatmapMap = null;
        let mapLayers = [];
        let currentRows = [];
        let lastSummary = null;
        let lastDateLabel = '';

        const today = new Date();
        const defaultDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

        const fmtInt = (value) => Number(value || 0).toLocaleString('en-IN');
        const escapeHtml = (value) => {
            if (value === null || value === undefined) {
                return '';
            }
            const str = String(value);
            const entityMap = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
            return str.replace(/[&<>"']/g, (char) => entityMap[char] || char);
        };
        const heatColor = (value, max) => {
            if (!max) {
                return '#e2e8f0';
            }
            const ratio = Math.min(Math.max(value / max, 0), 1);
            const hue = 205 - ratio * 85;
            const lightness = 82 - ratio * 28;
            return `hsl(${hue}, 78%, ${lightness}%)`;
        };

        const initMap = () => {
            if (!mapElement || typeof L === 'undefined') {
                return null;
            }
            const map = L.map(mapElement, {
                zoomControl: true,
                scrollWheelZoom: false,
            }).setView([28.4595, 77.0266], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: 'Map data © OpenStreetMap contributors',
                maxZoom: 18,
            }).addTo(map);
            return map;
        };

        const updateMap = (entries, maxHours) => {
            if (!heatmapMap) {
                return;
            }
            mapLayers.forEach((layer) => heatmapMap.removeLayer(layer));
            mapLayers = [];
            const bounds = [];

            entries.forEach((entry) => {
                const lat = Number(entry.lat ?? entry.coordinates?.lat ?? entry.location?.lat);
                const lon = Number(entry.lon ?? entry.coordinates?.lon ?? entry.location?.lon);
                if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                    return;
                }
                const doctorHours = entry.totals?.doctor_hours ?? 0;
                const baseRadius = maxHours ? Math.max(300, Math.min(1400, Math.round((doctorHours / Math.max(maxHours, 1)) * 1200))) : 420;
                const color = heatColor(doctorHours, maxHours);
                const circle = L.circle([lat, lon], {
                    radius: baseRadius,
                    color,
                    fillColor: color,
                    className: 'heatmap-marker',
                    fillOpacity: 0.45,
                    weight: 2,
                }).addTo(heatmapMap);
                const clinicList = (entry.clinics && entry.clinics.length) ? entry.clinics.join(', ') : 'N/A';
                circle.bindPopup(`
                    <strong>${escapeHtml(entry.clinic_name ?? entry.pincode ?? '—')}</strong><br/>
                    ${escapeHtml(clinicList)}<br/>
                    Doctor-hours: ${fmtInt(doctorHours)}<br/>
                    Doctors: ${fmtInt(entry.totals?.unique_doctors ?? 0)}
                `);
                mapLayers.push(circle);
                bounds.push(circle.getLatLng());
            });

            if (bounds.length) {
                heatmapMap.fitBounds(L.latLngBounds(bounds).pad(0.2), { maxZoom: 14 });
            }
        };

        const renderSummary = (summary, dateLabel, visible, total) => {
            if (!summaryTarget) {
                return;
            }
            if (!summary) {
                summaryTarget.textContent = '';
                return;
            }
            const displayLabel = dateLabel || defaultDate;
            const displayCount = total && visible !== total ? `${visible} of ${total}` : `${total}`;
            summaryTarget.innerHTML = `
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge text-bg-light">Date: <strong>${displayLabel}</strong></span>
                    <span class="badge text-bg-primary">Doctor-hours: ${fmtInt(summary.doctor_hours)}</span>
                    <span class="badge text-bg-success">Unique doctors: ${fmtInt(summary.unique_doctors)}</span>
                    <span class="badge text-bg-info text-dark">Clinics showing: ${displayCount}</span>
                </div>
            `;
        };

        const renderGrid = (rows) => {
            if (!gridTarget) {
                return;
            }
            const dataset = Array.isArray(rows) ? rows : [];
            const normalizedFilter = filterInput?.value?.trim().toLowerCase() || '';
            const matches = dataset.filter((row) => {
                if (!normalizedFilter) {
                    return true;
                }
                const pincodeMatch = String(row.pincode ?? '').toLowerCase().includes(normalizedFilter);
                const clinicMatch = (row.clinics || []).some((clinic) => clinic.toLowerCase().includes(normalizedFilter));
                return pincodeMatch || clinicMatch;
            });
            const maxHours = matches.reduce((max, entry) => Math.max(max, entry.totals?.doctor_hours ?? 0), 0);

            renderSummary(lastSummary, lastDateLabel, matches.length, dataset.length);

            if (!matches.length) {
                gridTarget.innerHTML = '';
                if (!dataset.length) {
                    messageTarget.textContent = 'No slot schedules available for the chosen date.';
                } else {
                    messageTarget.textContent = normalizedFilter ? 'No clinics match that filter.' : 'No schedule data available for this date.';
                }
                updateMap([], 0);
                return;
            }

            const infoText = normalizedFilter
                ? `Showing ${matches.length} of ${dataset.length} clinics matching "${normalizedFilter}".`
                : `Showing ${matches.length} clinics; use filters to surface other regions.`;
            messageTarget.textContent = infoText;

            gridTarget.innerHTML = matches.map((entry) => {
                const doctorHours = entry.totals?.doctor_hours ?? 0;
                const doctors = entry.totals?.unique_doctors ?? 0;
                const clinics = entry.clinics ?? [];
                const clinicLabel = escapeHtml(entry.clinic_name ?? clinics[0] ?? 'Clinic data missing');
                const badgeText = entry.pincode ? `Pincode: ${escapeHtml(entry.pincode)}` : 'Pincode missing';
                const latVal = Number(entry.lat ?? null);
                const lonVal = Number(entry.lon ?? null);
                const coordsLabel = (Number.isFinite(latVal) && Number.isFinite(lonVal))
                    ? `Lat ${latVal.toFixed(4)}, Lon ${lonVal.toFixed(4)}`
                    : 'Coordinates unavailable';
                const fillWidth = maxHours ? Math.min(100, Math.round((doctorHours / maxHours) * 100)) : 0;
                const fillColor = heatColor(doctorHours, maxHours);
                return `
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="heatmap-grid-card" style="border-color: ${fillColor}">
                            <div class="heatmap-card-header">
                                <div class="pincode">${clinicLabel}</div>
                                <span class="badge-sm text-bg-primary">${badgeText}</span>
                            </div>
                            <div class="heatmap-card-clinics">${coordsLabel}</div>
                            <div class="heatbar-track">
                                <div class="heatbar-fill" style="width: ${fillWidth}%; background: linear-gradient(90deg, ${fillColor}, #1e40af);"></div>
                            </div>
                            <div class="heatmap-stats">
                                <span>Doctor-hours <strong>${fmtInt(doctorHours)}</strong></span>
                                <span>Doctors <strong>${fmtInt(doctors)}</strong></span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            updateMap(matches, maxHours);
        };

        const setLoadingState = (message) => {
            if (messageTarget) {
                messageTarget.textContent = message;
            }
            if (gridTarget) {
                gridTarget.innerHTML = '';
            }
            updateMap([], 0);
        };

        const fetchHeatmap = async () => {
            if (!dateInput?.value) {
                return;
            }
            setLoadingState('Loading heatmap data…');
            try {
                const url = new URL(`${ADMIN_API_BASE}/video/pincode-slots`);
                url.searchParams.set('date', dateInput.value);
                const response = await fetch(url.toString(), { credentials: 'include' });
                if (!response.ok) {
                    throw new Error(`Request failed (${response.status})`);
                }
                const payload = await response.json();
                currentRows = payload.rows ?? [];
                lastSummary = payload.summary ?? null;
                lastDateLabel = payload.date ?? dateInput.value;
                renderGrid(currentRows);
            } catch (error) {
                console.error(error);
                setLoadingState('Failed to load heatmap. Please try again.');
                summaryTarget && (summaryTarget.textContent = '');
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            heatmapMap = initMap();
            if (heatmapMap) {
                setTimeout(() => heatmapMap.invalidateSize(), 250);
            }
            if (dateInput && !dateInput.value) {
                dateInput.value = defaultDate;
            }
            dateInput?.addEventListener('change', fetchHeatmap);
            filterInput?.addEventListener('input', () => renderGrid(currentRows));
            refreshButton?.addEventListener('click', fetchHeatmap);
            fetchHeatmap();
        });
    })();
</script>
@endpush
