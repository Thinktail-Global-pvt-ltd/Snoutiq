@extends('layouts.admin-panel')

@section('page-title', 'Lead Management')

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
<style>
    :root {
        --crm-bg: #0f1117;
        --crm-surface: #181c27;
        --crm-surface-2: #1e2435;
        --crm-border: #272d3f;
        --crm-border-2: #313a52;
        --crm-ink: #e8eaf0;
        --crm-ink-2: #8b92a8;
        --crm-ink-3: #4a5168;
        --crm-blue: #4f7cf8;
        --crm-blue-bg: rgba(79, 124, 248, 0.14);
        --crm-green: #34d399;
        --crm-green-bg: rgba(52, 211, 153, 0.14);
        --crm-amber: #fbbf24;
        --crm-amber-bg: rgba(251, 191, 36, 0.14);
        --crm-red: #f87171;
        --crm-red-bg: rgba(248, 113, 113, 0.14);
        --crm-purple: #a78bfa;
        --crm-purple-bg: rgba(167, 139, 250, 0.14);
        --crm-teal: #22d3ee;
        --crm-teal-bg: rgba(34, 211, 238, 0.11);
        --crm-radius: 10px;
        --crm-radius-sm: 6px;
    }

    .admin-main {
        background: var(--crm-bg);
    }

    .admin-header {
        background: rgba(24, 28, 39, 0.95);
        border-bottom: 1px solid var(--crm-border);
    }

    .admin-header .page-title h1 {
        color: var(--crm-ink);
    }

    .admin-header .header-meta {
        color: var(--crm-ink-2);
    }

    .admin-header .header-meta .badge {
        background: var(--crm-surface-2) !important;
        color: var(--crm-ink);
        border: 1px solid var(--crm-border);
    }

    .admin-content {
        padding: 0;
        background: var(--crm-bg);
    }

    .crm-shell-wrap {
        font-family: 'DM Sans', sans-serif;
        color: var(--crm-ink);
        background: radial-gradient(circle at top right, rgba(79, 124, 248, 0.18), transparent 45%), var(--crm-bg);
        border: 0;
        border-radius: 0;
        overflow: hidden;
        min-height: calc(100vh - 74px);
        box-shadow: none;
        display: flex;
        flex-direction: column;
    }

    .crm-topbar {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.1rem;
        border-bottom: 1px solid var(--crm-border);
        background: rgba(24, 28, 39, 0.85);
        backdrop-filter: blur(6px);
    }

    .crm-brand {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.02em;
    }

    .crm-brand-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: var(--crm-blue);
    }

    .crm-top-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .crm-stat {
        border: 1px solid var(--crm-border);
        background: var(--crm-surface-2);
        border-radius: var(--crm-radius-sm);
        font-size: 0.75rem;
        color: var(--crm-ink-2);
        padding: 0.3rem 0.62rem;
        white-space: nowrap;
    }

    .crm-stat b {
        color: var(--crm-ink);
        font-weight: 600;
        margin-left: 0.2rem;
        font-family: 'DM Mono', monospace;
    }

    .crm-filter-bar {
        border-bottom: 1px solid var(--crm-border);
        padding: 0.92rem 1.1rem;
        background: linear-gradient(180deg, rgba(30, 36, 53, 0.58) 0%, rgba(24, 28, 39, 0.58) 100%);
    }

    .crm-filter-form {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.55rem;
    }

    .crm-filter-form label {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: var(--crm-ink-3);
    }

    .crm-input,
    .crm-select {
        height: 34px;
        border-radius: var(--crm-radius-sm);
        border: 1px solid var(--crm-border);
        background: var(--crm-surface-2);
        color: var(--crm-ink);
        padding: 0.36rem 0.55rem;
        font-size: 0.78rem;
        outline: none;
    }

    .crm-input::placeholder {
        color: var(--crm-ink-3);
    }

    .crm-input:focus,
    .crm-select:focus {
        border-color: var(--crm-blue);
    }

    .crm-filter-form .crm-select,
    .crm-filter-form .crm-input {
        min-width: 125px;
    }

    .crm-apply-btn {
        height: 34px;
        border: none;
        background: var(--crm-blue);
        color: #fff;
        border-radius: var(--crm-radius-sm);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        padding: 0 0.85rem;
    }

    .crm-warning-stack {
        padding: 0.8rem 1.1rem 0;
    }

    .crm-warning-item {
        border: 1px solid rgba(251, 191, 36, 0.42);
        background: rgba(251, 191, 36, 0.08);
        color: #f4cf63;
        border-radius: var(--crm-radius-sm);
        font-size: 0.74rem;
        padding: 0.4rem 0.55rem;
        margin-bottom: 0.45rem;
    }

    .crm-app {
        display: grid;
        grid-template-columns: 252px 1fr;
        min-height: 0;
        flex: 1;
    }

    .crm-sidebar {
        border-right: 1px solid var(--crm-border);
        background: rgba(24, 28, 39, 0.76);
        display: flex;
        flex-direction: column;
        min-height: 100%;
        overflow: hidden;
    }

    .crm-sidebar-scroll {
        overflow-y: auto;
        padding-bottom: 0.65rem;
    }

    .crm-sidebar-section {
        padding: 0.82rem 0.88rem 0.4rem;
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--crm-ink-3);
    }

    .crm-side-item {
        width: 100%;
        border: 0;
        border-left: 2px solid transparent;
        border-radius: 0;
        background: transparent;
        color: var(--crm-ink-2);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-align: left;
        padding: 0.52rem 0.88rem;
        cursor: pointer;
        font-size: 0.79rem;
        font-weight: 500;
        transition: all 0.15s ease;
    }

    .crm-side-item:hover {
        color: var(--crm-ink);
        background: rgba(30, 36, 53, 0.75);
    }

    .crm-side-item.active {
        color: var(--crm-blue);
        background: var(--crm-blue-bg);
        border-left-color: var(--crm-blue);
    }

    .crm-side-count {
        margin-left: auto;
        font-size: 0.68rem;
        font-family: 'DM Mono', monospace;
        padding: 0.08rem 0.4rem;
        border-radius: 999px;
        border: 1px solid var(--crm-border);
        color: var(--crm-ink-2);
        background: var(--crm-surface-2);
    }

    .crm-side-item.active .crm-side-count {
        border-color: var(--crm-blue);
        color: var(--crm-blue);
        background: rgba(79, 124, 248, 0.08);
    }

    .crm-side-filters {
        border-top: 1px solid var(--crm-border);
        margin-top: auto;
        padding: 0.85rem;
        background: rgba(24, 28, 39, 0.88);
    }

    .crm-side-filter-label {
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--crm-ink-3);
        margin-bottom: 0.3rem;
        display: block;
    }

    .crm-main {
        display: grid;
        grid-template-columns: 360px 1fr;
        min-height: 100%;
        overflow: hidden;
        background: rgba(15, 17, 23, 0.76);
    }

    .crm-list-panel {
        border-right: 1px solid var(--crm-border);
        background: rgba(15, 17, 23, 0.62);
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .crm-list-header {
        padding: 0.88rem 0.95rem;
        border-bottom: 1px solid var(--crm-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.55rem;
        position: sticky;
        top: 0;
        z-index: 5;
        background: rgba(15, 17, 23, 0.95);
    }

    .crm-list-title {
        font-size: 0.83rem;
        font-weight: 700;
        color: var(--crm-ink);
    }

    .crm-list-count {
        font-size: 0.68rem;
        color: var(--crm-ink-3);
        font-family: 'DM Mono', monospace;
        white-space: nowrap;
    }

    .crm-lead-list {
        overflow-y: auto;
    }

    .crm-lead-card {
        border-bottom: 1px solid var(--crm-border);
        padding: 0.8rem 0.95rem;
        cursor: pointer;
        transition: background 0.15s ease;
        position: relative;
    }

    .crm-lead-card:hover {
        background: rgba(24, 28, 39, 0.72);
    }

    .crm-lead-card.active {
        background: rgba(30, 36, 53, 0.9);
        border-left: 2px solid var(--crm-blue);
        padding-left: 0.84rem;
    }

    .crm-lead-urgent {
        position: absolute;
        right: 0.8rem;
        top: 0.8rem;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--crm-red);
        box-shadow: 0 0 0 0 rgba(248, 113, 113, 0.65);
        animation: crm-pulse 1.6s infinite;
    }

    @keyframes crm-pulse {
        0% { box-shadow: 0 0 0 0 rgba(248, 113, 113, 0.5); }
        70% { box-shadow: 0 0 0 8px rgba(248, 113, 113, 0); }
        100% { box-shadow: 0 0 0 0 rgba(248, 113, 113, 0); }
    }

    .crm-lead-top {
        display: flex;
        gap: 0.55rem;
        justify-content: space-between;
        align-items: flex-start;
    }

    .crm-lead-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--crm-ink);
        line-height: 1.3;
    }

    .crm-lead-meta-id {
        margin-top: 0.15rem;
        font-size: 0.62rem;
        color: var(--crm-ink-3);
        font-family: 'DM Mono', monospace;
    }

    .crm-status-badge {
        border-radius: 999px;
        font-size: 0.58rem;
        letter-spacing: 0.04em;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.16rem 0.42rem;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .crm-status-new { color: var(--crm-blue); background: var(--crm-blue-bg); border-color: rgba(79, 124, 248, 0.42); }
    .crm-status-contacted { color: var(--crm-amber); background: var(--crm-amber-bg); border-color: rgba(251, 191, 36, 0.42); }
    .crm-status-booked { color: var(--crm-purple); background: var(--crm-purple-bg); border-color: rgba(167, 139, 250, 0.42); }
    .crm-status-completed { color: var(--crm-green); background: var(--crm-green-bg); border-color: rgba(52, 211, 153, 0.42); }
    .crm-status-lost { color: var(--crm-red); background: var(--crm-red-bg); border-color: rgba(248, 113, 113, 0.42); }

    .crm-lead-pet {
        margin-top: 0.38rem;
        font-size: 0.73rem;
        color: var(--crm-ink-2);
        line-height: 1.4;
    }

    .crm-lead-tags {
        margin-top: 0.46rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
        align-items: center;
    }

    .crm-tag {
        border-radius: 4px;
        background: var(--crm-surface-2);
        color: var(--crm-ink-3);
        font-family: 'DM Mono', monospace;
        font-size: 0.6rem;
        padding: 0.13rem 0.36rem;
    }

    .crm-rev {
        margin-left: auto;
        color: var(--crm-green);
        font-weight: 600;
        font-family: 'DM Mono', monospace;
        font-size: 0.69rem;
    }

    .crm-next-line {
        margin-top: 0.45rem;
        font-size: 0.68rem;
        color: var(--crm-ink-3);
        line-height: 1.38;
    }

    .crm-next-line.overdue { color: var(--crm-red); }
    .crm-next-line.today { color: var(--crm-amber); }
    .crm-next-line.upcoming { color: var(--crm-green); }

    .crm-empty {
        padding: 2.4rem 1rem;
        text-align: center;
        color: var(--crm-ink-3);
        font-size: 0.78rem;
    }

    .crm-detail-panel {
        min-width: 0;
        overflow-y: auto;
        padding: 1rem;
    }

    .crm-detail-empty {
        border: 1px dashed var(--crm-border-2);
        border-radius: var(--crm-radius);
        min-height: 260px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--crm-ink-3);
        gap: 0.42rem;
        font-size: 0.78rem;
        background: rgba(24, 28, 39, 0.34);
    }

    .crm-detail-wrap {
        display: none;
    }

    .crm-detail-wrap.active {
        display: block;
    }

    .crm-dh {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.68rem;
        margin-bottom: 0.82rem;
        flex-wrap: wrap;
    }

    .crm-dh-left {
        display: flex;
        align-items: center;
        gap: 0.62rem;
        min-width: 0;
    }

    .crm-avatar {
        width: 43px;
        height: 43px;
        border-radius: 50%;
        border: 1px solid var(--crm-blue);
        background: var(--crm-blue-bg);
        color: var(--crm-blue);
        font-weight: 700;
        font-family: 'DM Mono', monospace;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.9rem;
    }

    .crm-dh-name {
        font-size: 1rem;
        font-weight: 700;
        color: var(--crm-ink);
        line-height: 1.25;
    }

    .crm-dh-sub {
        margin-top: 0.22rem;
        font-size: 0.69rem;
        color: var(--crm-ink-2);
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        align-items: center;
    }

    .crm-dh-actions {
        display: flex;
        gap: 0.36rem;
        flex-wrap: wrap;
    }

    .crm-btn,
    .crm-status-select {
        border: 1px solid var(--crm-border-2);
        border-radius: var(--crm-radius-sm);
        background: var(--crm-surface-2);
        color: var(--crm-ink-2);
        font-size: 0.7rem;
        height: 31px;
        padding: 0 0.55rem;
    }

    .crm-status-select {
        min-width: 118px;
        color: var(--crm-ink);
        font-weight: 600;
    }

    .crm-btn:hover {
        border-color: var(--crm-blue);
        color: var(--crm-ink);
    }

    .crm-btn.primary {
        border-color: var(--crm-blue);
        background: var(--crm-blue);
        color: #fff;
    }

    .crm-btn.danger {
        border-color: rgba(248, 113, 113, 0.42);
        color: var(--crm-red);
        background: rgba(248, 113, 113, 0.08);
    }

    .crm-next-box {
        background: rgba(251, 191, 36, 0.09);
        border: 1px solid rgba(251, 191, 36, 0.33);
        border-radius: var(--crm-radius);
        padding: 0.72rem;
        display: flex;
        justify-content: space-between;
        gap: 0.68rem;
        margin-bottom: 0.72rem;
        align-items: flex-start;
    }

    .crm-next-title {
        color: var(--crm-amber);
        font-weight: 700;
        font-size: 0.76rem;
    }

    .crm-next-text {
        margin-top: 0.18rem;
        color: var(--crm-ink-2);
        font-size: 0.72rem;
        line-height: 1.45;
    }

    .crm-next-date {
        border-radius: 4px;
        background: rgba(251, 191, 36, 0.16);
        color: var(--crm-amber);
        font-family: 'DM Mono', monospace;
        font-size: 0.64rem;
        padding: 0.18rem 0.38rem;
        white-space: nowrap;
    }

    .crm-blocker {
        border: 1px solid rgba(248, 113, 113, 0.3);
        background: rgba(248, 113, 113, 0.11);
        border-radius: var(--crm-radius-sm);
        color: #ffb2b2;
        font-size: 0.72rem;
        padding: 0.56rem 0.65rem;
        margin-bottom: 0.72rem;
    }

    .crm-rev-bar {
        display: flex;
        border: 1px solid var(--crm-border);
        border-radius: var(--crm-radius);
        overflow: hidden;
        margin-bottom: 0.88rem;
        background: rgba(24, 28, 39, 0.78);
    }

    .crm-rev-item {
        flex: 1;
        text-align: center;
        padding: 0.56rem 0.45rem;
        border-right: 1px solid var(--crm-border);
    }

    .crm-rev-item:last-child {
        border-right: 0;
    }

    .crm-rev-num {
        font-size: 1rem;
        line-height: 1.1;
        color: var(--crm-ink);
        font-family: 'DM Mono', monospace;
        font-weight: 600;
    }

    .crm-rev-num.green { color: var(--crm-green); }
    .crm-rev-num.blue { color: var(--crm-blue); }
    .crm-rev-num.amber { color: var(--crm-amber); }

    .crm-rev-label {
        margin-top: 0.2rem;
        font-size: 0.58rem;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: var(--crm-ink-3);
    }

    .crm-tabs {
        display: flex;
        border-bottom: 1px solid var(--crm-border);
        margin-bottom: 0.72rem;
        overflow-x: auto;
    }

    .crm-tab {
        border: 0;
        border-bottom: 2px solid transparent;
        background: transparent;
        color: var(--crm-ink-3);
        font-size: 0.76rem;
        padding: 0.42rem 0.7rem;
        white-space: nowrap;
        cursor: pointer;
    }

    .crm-tab.active {
        color: var(--crm-blue);
        border-bottom-color: var(--crm-blue);
    }

    .crm-tab-content {
        display: none;
    }

    .crm-tab-content.active {
        display: block;
    }

    .crm-grid-2,
    .crm-grid-3 {
        display: grid;
        gap: 0.55rem;
        margin-bottom: 0.62rem;
    }

    .crm-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .crm-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

    .crm-card {
        border: 1px solid var(--crm-border);
        border-radius: var(--crm-radius);
        background: rgba(24, 28, 39, 0.72);
        padding: 0.65rem;
    }

    .crm-card-title {
        font-size: 0.59rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--crm-ink-3);
        font-weight: 700;
        margin-bottom: 0.52rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.4rem;
    }

    .crm-card-title button {
        border: 0;
        background: transparent;
        padding: 0;
        color: var(--crm-blue);
        font-size: 0.58rem;
        cursor: pointer;
        text-transform: none;
        letter-spacing: 0;
        font-weight: 500;
    }

    .crm-field-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.58rem;
        padding: 0.24rem 0;
        border-bottom: 1px solid var(--crm-border);
        font-size: 0.72rem;
    }

    .crm-field-row:last-child {
        border-bottom: 0;
    }

    .crm-fr-label {
        color: var(--crm-ink-2);
        white-space: nowrap;
    }

    .crm-fr-val {
        color: var(--crm-ink);
        font-weight: 600;
        text-align: right;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .crm-fr-val.missing { color: var(--crm-ink-3); font-weight: 400; }
    .crm-fr-val.warn { color: var(--crm-amber); }
    .crm-fr-val.ok { color: var(--crm-green); }

    .crm-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        font-size: 0.55rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 0.12rem 0.4rem;
        border: 1px solid transparent;
    }

    .crm-pill-blue { color: var(--crm-blue); background: var(--crm-blue-bg); border-color: rgba(79, 124, 248, 0.35); }
    .crm-pill-green { color: var(--crm-green); background: var(--crm-green-bg); border-color: rgba(52, 211, 153, 0.35); }
    .crm-pill-amber { color: var(--crm-amber); background: var(--crm-amber-bg); border-color: rgba(251, 191, 36, 0.35); }
    .crm-pill-red { color: var(--crm-red); background: var(--crm-red-bg); border-color: rgba(248, 113, 113, 0.35); }
    .crm-pill-purple { color: var(--crm-purple); background: var(--crm-purple-bg); border-color: rgba(167, 139, 250, 0.35); }

    .crm-note-box {
        border: 1px solid var(--crm-border);
        border-radius: var(--crm-radius-sm);
        background: rgba(30, 36, 53, 0.64);
        color: var(--crm-ink-2);
        font-size: 0.72rem;
        padding: 0.5rem;
        line-height: 1.45;
        min-height: 75px;
    }

    .crm-timeline-item {
        display: flex;
        gap: 0.56rem;
        border-bottom: 1px solid var(--crm-border);
        padding: 0.5rem 0;
        align-items: flex-start;
    }

    .crm-timeline-item:last-child {
        border-bottom: 0;
    }

    .crm-tl-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1px solid var(--crm-border-2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--crm-ink-2);
        font-size: 0.74rem;
        background: rgba(30, 36, 53, 0.65);
        flex-shrink: 0;
    }

    .crm-tl-title {
        font-size: 0.74rem;
        color: var(--crm-ink);
        font-weight: 600;
        line-height: 1.35;
    }

    .crm-tl-text {
        font-size: 0.7rem;
        color: var(--crm-ink-2);
        margin-top: 0.12rem;
        line-height: 1.4;
    }

    .crm-tl-meta {
        margin-top: 0.24rem;
        display: flex;
        gap: 0.34rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .crm-tl-time {
        color: var(--crm-ink-3);
        font-size: 0.6rem;
        font-family: 'DM Mono', monospace;
    }

    .crm-service-card {
        border: 1px solid var(--crm-border);
        border-radius: var(--crm-radius-sm);
        background: rgba(24, 28, 39, 0.75);
        padding: 0.56rem;
        margin-bottom: 0.45rem;
    }

    .crm-service-head {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .crm-service-title {
        font-size: 0.74rem;
        font-weight: 600;
        color: var(--crm-ink);
    }

    .crm-service-amount {
        color: var(--crm-green);
        font-size: 0.74rem;
        font-family: 'DM Mono', monospace;
        font-weight: 600;
        white-space: nowrap;
    }

    .crm-service-meta {
        margin-top: 0.35rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.26rem;
    }

    .crm-service-tag {
        font-size: 0.6rem;
        color: var(--crm-ink-3);
        background: var(--crm-surface-2);
        border-radius: 4px;
        padding: 0.11rem 0.35rem;
        font-family: 'DM Mono', monospace;
    }

    .crm-add-service {
        border: 1px dashed var(--crm-border-2);
        border-radius: var(--crm-radius-sm);
        padding: 0.55rem;
        text-align: center;
        color: var(--crm-ink-3);
        font-size: 0.72rem;
        cursor: pointer;
        margin-bottom: 0.5rem;
    }

    .crm-add-service:hover {
        border-color: var(--crm-blue);
        color: var(--crm-blue);
        background: var(--crm-blue-bg);
    }

    .crm-notif-row {
        display: flex;
        gap: 0.4rem;
        align-items: center;
        padding: 0.35rem 0;
        border-bottom: 1px solid var(--crm-border);
    }

    .crm-notif-row:last-child {
        border-bottom: 0;
    }

    .crm-notif-title {
        color: var(--crm-ink);
        font-size: 0.7rem;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .crm-notif-time {
        color: var(--crm-ink-3);
        font-size: 0.58rem;
        font-family: 'DM Mono', monospace;
        flex-shrink: 0;
    }

    .crm-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.65rem;
        padding: 0.85rem 1.1rem 1rem;
        border-top: 1px solid var(--crm-border);
        font-size: 0.71rem;
        color: var(--crm-ink-2);
    }

    .crm-pg-pages {
        display: flex;
        align-items: center;
        gap: 0.34rem;
        flex-wrap: wrap;
    }

    .crm-pg-btn {
        border: 1px solid var(--crm-border-2);
        color: var(--crm-ink-2);
        text-decoration: none;
        border-radius: var(--crm-radius-sm);
        padding: 0.3rem 0.56rem;
        font-size: 0.7rem;
        background: rgba(30, 36, 53, 0.75);
    }

    .crm-pg-btn:hover {
        color: var(--crm-ink);
        border-color: var(--crm-blue);
    }

    .crm-pg-btn.disabled,
    .crm-pg-btn[aria-disabled='true'] {
        opacity: 0.45;
        pointer-events: none;
    }

    .crm-pg-num {
        min-width: 30px;
        height: 30px;
        border: 1px solid var(--crm-border-2);
        color: var(--crm-ink-2);
        text-decoration: none;
        border-radius: var(--crm-radius-sm);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.69rem;
        background: rgba(30, 36, 53, 0.75);
        padding: 0 0.42rem;
    }

    .crm-pg-num:hover {
        color: var(--crm-ink);
        border-color: var(--crm-blue);
    }

    .crm-pg-num.active {
        border-color: var(--crm-blue);
        background: var(--crm-blue-bg);
        color: var(--crm-blue);
        pointer-events: none;
    }

    .crm-pg-ellipsis {
        font-size: 0.72rem;
        color: var(--crm-ink-3);
        padding: 0 0.1rem;
    }

    .crm-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.72);
        z-index: 1085;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.18s ease;
        padding: 1rem;
    }

    .crm-modal-overlay.open {
        opacity: 1;
        pointer-events: auto;
    }

    .crm-modal {
        width: min(560px, 100%);
        background: var(--crm-surface);
        border: 1px solid var(--crm-border-2);
        border-radius: var(--crm-radius);
        padding: 1rem;
    }

    .crm-modal h3 {
        font-size: 1rem;
        color: var(--crm-ink);
        margin: 0;
        font-weight: 700;
    }

    .crm-modal-sub {
        color: var(--crm-ink-2);
        font-size: 0.72rem;
        margin: 0.24rem 0 0.8rem;
    }

    .crm-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.55rem;
    }

    .crm-mfield {
        margin-bottom: 0.55rem;
    }

    .crm-mfield label {
        font-size: 0.62rem;
        color: var(--crm-ink-3);
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-weight: 700;
        display: block;
        margin-bottom: 0.26rem;
    }

    .crm-mfield input,
    .crm-mfield select,
    .crm-mfield textarea {
        width: 100%;
        background: var(--crm-surface-2);
        border: 1px solid var(--crm-border);
        border-radius: var(--crm-radius-sm);
        color: var(--crm-ink);
        font-size: 0.74rem;
        padding: 0.42rem 0.52rem;
        outline: none;
    }

    .crm-mfield textarea {
        min-height: 78px;
        resize: vertical;
    }

    .crm-mfield input:focus,
    .crm-mfield select:focus,
    .crm-mfield textarea:focus {
        border-color: var(--crm-blue);
    }

    .crm-modal-actions {
        margin-top: 0.8rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.45rem;
    }

    .crm-modal-btn {
        height: 33px;
        border-radius: var(--crm-radius-sm);
        border: 1px solid var(--crm-border);
        color: var(--crm-ink-2);
        background: transparent;
        font-size: 0.72rem;
        padding: 0 0.75rem;
    }

    .crm-modal-btn.primary {
        background: var(--crm-blue);
        border-color: var(--crm-blue);
        color: #fff;
    }

    .crm-toast {
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        z-index: 1100;
        border-radius: var(--crm-radius-sm);
        border: 1px solid rgba(79, 124, 248, 0.5);
        background: rgba(79, 124, 248, 0.15);
        color: #dbe7ff;
        padding: 0.45rem 0.6rem;
        font-size: 0.72rem;
        display: none;
    }

    .crm-toast.show {
        display: block;
    }

    .crm-code {
        font-family: 'DM Mono', monospace;
    }

    .crm-text-muted {
        color: var(--crm-ink-3) !important;
    }

    @media (max-width: 1399.98px) {
        .crm-main {
            grid-template-columns: 320px 1fr;
        }
    }

    @media (max-width: 1199.98px) {
        .crm-app {
            grid-template-columns: 225px 1fr;
        }

        .crm-main {
            grid-template-columns: 300px 1fr;
        }

        .crm-grid-3 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991.98px) {
        .admin-content {
            padding: 0;
        }

        .crm-shell-wrap {
            min-height: calc(100vh - 64px);
        }

        .crm-app {
            grid-template-columns: 1fr;
        }

        .crm-sidebar {
            border-right: 0;
            border-bottom: 1px solid var(--crm-border);
        }

        .crm-sidebar-scroll {
            max-height: 230px;
        }

        .crm-main {
            grid-template-columns: 1fr;
            min-height: auto;
        }

        .crm-list-panel {
            border-right: 0;
            border-bottom: 1px solid var(--crm-border);
            max-height: 350px;
        }

        .crm-detail-panel {
            max-height: none;
        }

        .crm-grid-2,
        .crm-grid-3,
        .crm-modal-grid {
            grid-template-columns: 1fr;
        }

        .crm-rev-bar {
            flex-wrap: wrap;
        }

        .crm-rev-item {
            width: 50%;
            flex: none;
            border-bottom: 1px solid var(--crm-border);
        }

        .crm-rev-item:nth-child(2n) {
            border-right: 0;
        }
    }

    @media (max-width: 575.98px) {
        .crm-topbar,
        .crm-filter-bar,
        .crm-warning-stack,
        .crm-pagination {
            padding-left: 0.72rem;
            padding-right: 0.72rem;
        }

        .crm-dh-actions {
            width: 100%;
        }

        .crm-dh-actions .crm-btn,
        .crm-dh-actions .crm-status-select {
            flex: 1;
            min-width: 0;
        }

        .crm-rev-item {
            width: 100%;
            border-right: 0;
        }
    }
</style>
@endpush

@section('content')
@php
    $filterLabels = [
        'all' => 'All Targeted Users',
        'neutering' => 'Neutering Package Leads',
        'video_follow_up' => 'All Follow-up Leads',
        'video_follow_up_video' => 'Video Follow-up Leads',
        'video_follow_up_in_clinic' => 'In-clinic Follow-up Leads',
        'vaccination' => 'Vaccination Reminder Leads',
        'both' => 'Users In Both Categories',
    ];

    $activeFilterLabel = $filterLabels[$leadFilter] ?? $filterLabels['all'];
    $todayDate = \Illuminate\Support\Carbon::today()->toDateString();
    $currentPageUsers = $filteredTargetUsers instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? collect($filteredTargetUsers->items())
        : collect($filteredTargetUsers ?? []);
    $currentPageNumber = $filteredTargetUsers instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? (int) $filteredTargetUsers->currentPage()
        : 1;

    $formatDate = static function ($value): string {
        if (empty($value)) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $formatDateTime = static function ($value): string {
        if (empty($value)) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y, H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $formatDateShort = static function ($value): string {
        if (empty($value)) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $resolveDueState = static function (?string $date) use ($todayDate): array {
        if (empty($date)) {
            return [
                'key' => 'none',
                'label' => 'No date set',
                'css' => 'none',
            ];
        }

        if ($date < $todayDate) {
            return [
                'key' => 'overdue',
                'label' => 'Overdue',
                'css' => 'overdue',
            ];
        }

        if ($date === $todayDate) {
            return [
                'key' => 'today',
                'label' => 'Due Today',
                'css' => 'today',
            ];
        }

        return [
            'key' => 'upcoming',
            'label' => 'Upcoming',
            'css' => 'upcoming',
        ];
    };

    $resolveStatus = static function (array $leadUser): array {
        if ((bool) ($leadUser['conversion_captured'] ?? false)) {
            return ['key' => 'completed', 'label' => 'Completed', 'class' => 'crm-status-completed'];
        }

        $hasBookedSignal = ((int) ($leadUser['video_follow_up_video_count'] ?? 0) > 0)
            || ((int) ($leadUser['video_follow_up_in_clinic_count'] ?? 0) > 0);
        if ($hasBookedSignal) {
            return ['key' => 'booked', 'label' => 'Booked', 'class' => 'crm-status-booked'];
        }

        if ((int) ($leadUser['all_notifications_count'] ?? 0) > 0) {
            return ['key' => 'contacted', 'label' => 'Contacted', 'class' => 'crm-status-contacted'];
        }

        return ['key' => 'new', 'label' => 'New', 'class' => 'crm-status-new'];
    };

    $leadRecords = $currentPageUsers
        ->map(function (array $leadUser) use (
            $formatDate,
            $formatDateTime,
            $formatDateShort,
            $resolveDueState,
            $resolveStatus
        ): array {
            $leadId = (int) ($leadUser['id'] ?? 0);
            $name = trim((string) ($leadUser['name'] ?? ''));
            $email = trim((string) ($leadUser['email'] ?? ''));
            $phone = trim((string) ($leadUser['phone'] ?? ''));
            $city = trim((string) ($leadUser['city'] ?? ''));

            $statusMeta = $resolveStatus($leadUser);

            $videoDate = !empty($leadUser['next_video_follow_up_date']) ? (string) $leadUser['next_video_follow_up_date'] : null;
            $clinicDate = !empty($leadUser['next_in_clinic_follow_up_date']) ? (string) $leadUser['next_in_clinic_follow_up_date'] : null;
            $genericDate = !empty($leadUser['next_follow_up_date']) ? (string) $leadUser['next_follow_up_date'] : null;

            $nextActionType = 'Follow-up';
            $nextActionDate = $genericDate;
            if ($videoDate !== null && ($nextActionDate === null || strcmp($videoDate, (string) $nextActionDate) < 0)) {
                $nextActionDate = $videoDate;
                $nextActionType = 'Video Follow-up';
            }
            if ($clinicDate !== null && ($nextActionDate === null || strcmp($clinicDate, (string) $nextActionDate) < 0)) {
                $nextActionDate = $clinicDate;
                $nextActionType = 'In-clinic Follow-up';
            }

            $nextActionState = $resolveDueState($nextActionDate);
            $notifications = collect($leadUser['all_notifications'] ?? [])
                ->map(function (array $item): array {
                    $bucket = trim((string) ($item['bucket'] ?? ''));
                    $bucketLabel = match ($bucket) {
                        'neutering' => 'Neutering',
                        'follow_up' => 'Follow-up',
                        'vaccination' => 'Vaccination',
                        'onboarding' => 'Onboarding',
                        'profile_completion' => 'Profile Completion',
                        default => 'Other',
                    };

                    return [
                        'id' => (int) ($item['id'] ?? 0),
                        'title' => trim((string) ($item['notification_title'] ?? '')),
                        'text' => trim((string) ($item['notification_text'] ?? '')),
                        'type' => trim((string) ($item['notification_type'] ?? 'unknown')),
                        'timestamp' => trim((string) ($item['timestamp'] ?? '')),
                        'clicked' => array_key_exists('clicked', $item) ? (bool) ($item['clicked'] ?? false) : null,
                        'clicked_at' => trim((string) ($item['clicked_at'] ?? '')),
                        'bucket' => $bucket,
                        'bucket_label' => $bucketLabel,
                        'converted' => (bool) ($item['converted'] ?? false),
                        'conversion_transaction_id' => is_numeric($item['conversion_transaction_id'] ?? null)
                            ? (int) $item['conversion_transaction_id']
                            : 0,
                        'conversion_transaction_type' => trim((string) ($item['conversion_transaction_type'] ?? '')),
                        'conversion_transaction_status' => trim((string) ($item['conversion_transaction_status'] ?? '')),
                        'conversion_transaction_at' => trim((string) ($item['conversion_transaction_at'] ?? '')),
                    ];
                })
                ->values()
                ->all();

            $clickedCount = collect($notifications)
                ->filter(static fn (array $item): bool => ($item['clicked'] ?? null) === true)
                ->count();

            $primaryPetNames = collect($leadUser['neutering_pet_names'] ?? [])
                ->filter(static fn ($name): bool => trim((string) $name) !== '')
                ->values();

            $vaccinationPetNames = collect($leadUser['notified_vaccination_pet_names'] ?? [])
                ->filter(static fn ($name): bool => trim((string) $name) !== '')
                ->values();

            $allPetNames = $primaryPetNames
                ->merge($vaccinationPetNames)
                ->unique()
                ->values();

            $primaryPet = trim((string) ($allPetNames->first() ?? ''));
            if ($primaryPet === '') {
                $primaryPet = trim((string) ($primaryPetNames->first() ?? ''));
            }

            $categoryTags = [];
            if (!empty($leadUser['has_neutering'])) {
                $categoryTags[] = 'Neutering';
            }
            if (!empty($leadUser['has_video_follow_up_video'])) {
                $categoryTags[] = 'Video Follow-up';
            }
            if (!empty($leadUser['has_video_follow_up_in_clinic'])) {
                $categoryTags[] = 'In-clinic Follow-up';
            }
            if (
                !empty($leadUser['has_video_follow_up'])
                && empty($leadUser['has_video_follow_up_video'])
                && empty($leadUser['has_video_follow_up_in_clinic'])
            ) {
                $categoryTags[] = 'Follow-up';
            }
            if (!empty($leadUser['has_vaccination_reminder'])) {
                $categoryTags[] = 'Vaccination';
            }

            $followUpTypeRaw = trim((string) ($leadUser['prescription_follow_up_type'] ?? ''));
            $followUpTypeLabel = $followUpTypeRaw !== ''
                ? \Illuminate\Support\Str::title(str_replace(['_', '-'], ' ', $followUpTypeRaw))
                : '';

            return [
                'id' => $leadId,
                'name' => $name !== '' ? $name : 'Unnamed user',
                'email' => $email,
                'phone' => $phone,
                'city' => $city,
                'created_at' => (string) ($leadUser['user_created_at'] ?? ''),
                'created_at_label' => $formatDateTime($leadUser['user_created_at'] ?? null),
                'created_short' => $formatDateShort($leadUser['user_created_at'] ?? null),
                'prescription_follow_up_date' => (string) ($leadUser['prescription_follow_up_date'] ?? ''),
                'prescription_follow_up_date_label' => $formatDate($leadUser['prescription_follow_up_date'] ?? null),
                'follow_up_type_label' => $followUpTypeLabel,
                'status_key' => $statusMeta['key'],
                'status_label' => $statusMeta['label'],
                'status_class' => $statusMeta['class'],
                'neutering_pet_count' => (int) ($leadUser['neutering_pet_count'] ?? 0),
                'neutering_pet_names' => collect($leadUser['neutering_pet_names'] ?? [])->values()->all(),
                'all_pet_names' => $allPetNames->all(),
                'primary_pet' => $primaryPet,
                'video_follow_up_count' => (int) ($leadUser['video_follow_up_count'] ?? 0),
                'video_follow_up_video_count' => (int) ($leadUser['video_follow_up_video_count'] ?? 0),
                'video_follow_up_in_clinic_count' => (int) ($leadUser['video_follow_up_in_clinic_count'] ?? 0),
                'next_follow_up_date' => $genericDate,
                'next_video_follow_up_date' => $videoDate,
                'next_in_clinic_follow_up_date' => $clinicDate,
                'next_action_type' => $nextActionType,
                'next_action_date' => $nextActionDate,
                'next_action_date_label' => $formatDate($nextActionDate),
                'next_action_state' => $nextActionState,
                'neutering_notification_count' => (int) ($leadUser['neutering_notification_count'] ?? 0),
                'vaccination_notification_count' => (int) ($leadUser['vaccination_notification_count'] ?? 0),
                'last_neutering_notification_at' => (string) ($leadUser['last_neutering_notification_at'] ?? ''),
                'last_vaccination_notification_at' => (string) ($leadUser['last_vaccination_notification_at'] ?? ''),
                'all_notifications_count' => (int) ($leadUser['all_notifications_count'] ?? 0),
                'clicked_notifications_count' => $clickedCount,
                'notifications' => $notifications,
                'has_neutering' => (bool) ($leadUser['has_neutering'] ?? false),
                'has_video_follow_up' => (bool) ($leadUser['has_video_follow_up'] ?? false),
                'has_video_follow_up_video' => (bool) ($leadUser['has_video_follow_up_video'] ?? false),
                'has_video_follow_up_in_clinic' => (bool) ($leadUser['has_video_follow_up_in_clinic'] ?? false),
                'has_vaccination_reminder' => (bool) ($leadUser['has_vaccination_reminder'] ?? false),
                'category_tags' => $categoryTags,
                'conversion_captured' => (bool) ($leadUser['conversion_captured'] ?? false),
                'conversion_notification_type' => (string) ($leadUser['conversion_notification_type'] ?? ''),
                'conversion_notification_at' => (string) ($leadUser['conversion_notification_at'] ?? ''),
                'conversion_transaction_id' => (int) ($leadUser['conversion_transaction_id'] ?? 0),
                'conversion_transaction_type' => (string) ($leadUser['conversion_transaction_type'] ?? ''),
                'conversion_transaction_status' => (string) ($leadUser['conversion_transaction_status'] ?? ''),
                'conversion_transaction_at' => (string) ($leadUser['conversion_transaction_at'] ?? ''),
                'conversion_lag_minutes' => is_numeric($leadUser['conversion_lag_minutes'] ?? null)
                    ? (int) $leadUser['conversion_lag_minutes']
                    : null,
            ];
        })
        ->values()
        ->all();

    $statusCounts = collect($leadRecords)
        ->countBy('status_key')
        ->all();

    $overdueCount = collect($leadRecords)
        ->filter(static fn (array $item): bool => ($item['next_action_state']['key'] ?? '') === 'overdue')
        ->count();

    $dueTodayCount = collect($leadRecords)
        ->filter(static fn (array $item): bool => ($item['next_action_state']['key'] ?? '') === 'today')
        ->count();

    $deleteRouteTemplate = route('admin.lead-management.users.delete', ['user' => '__USER_ID__']);

    $leadPageMeta = [
        'lead_filter' => $leadFilter,
        'limit' => $limit,
        'per_page' => $perPage ?? 50,
        'page' => $currentPageNumber,
    ];

    $paginationWindow = [];
    if ($filteredTargetUsers instanceof \Illuminate\Pagination\LengthAwarePaginator && $filteredTargetUsers->total() > 0) {
        $currentPage = (int) $filteredTargetUsers->currentPage();
        $lastPage = (int) $filteredTargetUsers->lastPage();
        $windowStart = max(1, $currentPage - 2);
        $windowEnd = min($lastPage, $currentPage + 2);

        if ($windowStart > 1) {
            $paginationWindow[] = 1;
            if ($windowStart > 2) {
                $paginationWindow[] = 'ellipsis-left';
            }
        }

        for ($pageNumber = $windowStart; $pageNumber <= $windowEnd; $pageNumber++) {
            $paginationWindow[] = $pageNumber;
        }

        if ($windowEnd < $lastPage) {
            if ($windowEnd < ($lastPage - 1)) {
                $paginationWindow[] = 'ellipsis-right';
            }
            $paginationWindow[] = $lastPage;
        }
    }
@endphp

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="crm-shell-wrap">
    <div class="crm-topbar">
        <div class="crm-brand">
            <span class="crm-brand-dot"></span>
            Snoutiq CRM
        </div>
        <div class="crm-top-stats">
            <div class="crm-stat">Active leads: <b>{{ number_format($summary['filtered_users'] ?? 0) }}</b></div>
            <div class="crm-stat">Converted users: <b>{{ number_format($summary['converted_users'] ?? 0) }}</b></div>
            <div class="crm-stat">Pending actions: <b>{{ number_format($overdueCount + $dueTodayCount) }}</b></div>
            <div class="crm-stat">Filter: <b>{{ $activeFilterLabel }}</b></div>
        </div>
    </div>

    <div class="crm-filter-bar">
        <form class="crm-filter-form" method="GET" action="{{ route('admin.lead-management') }}">
            <label for="lead_filter">Category</label>
            <select id="lead_filter" name="lead_filter" class="crm-select">
                <option value="all" @selected($leadFilter === 'all')>All targeted users</option>
                <option value="neutering" @selected($leadFilter === 'neutering')>Neutering package leads</option>
                <option value="video_follow_up" @selected($leadFilter === 'video_follow_up')>All follow-up leads</option>
                <option value="video_follow_up_video" @selected($leadFilter === 'video_follow_up_video')>Video follow-up leads</option>
                <option value="video_follow_up_in_clinic" @selected($leadFilter === 'video_follow_up_in_clinic')>In-clinic follow-up leads</option>
                <option value="vaccination" @selected($leadFilter === 'vaccination')>Vaccination reminder leads</option>
                <option value="both" @selected($leadFilter === 'both')>Users in both categories</option>
            </select>

            <label for="limit">Rows</label>
            <input id="limit" name="limit" type="number" min="25" max="1000" value="{{ $limit }}" class="crm-input" style="width: 95px; min-width: 95px;">

            <label for="per_page">Page size</label>
            <select id="per_page" name="per_page" class="crm-select" style="width: 105px; min-width: 105px;">
                @foreach([25, 50, 100, 150, 200] as $size)
                    <option value="{{ $size }}" @selected(((int) ($perPage ?? 50)) === $size)>{{ $size }}</option>
                @endforeach
            </select>

            <button type="submit" class="crm-apply-btn">Apply</button>
        </form>
    </div>

    <div class="crm-warning-stack">
        @if(!empty($runtimeWarnings ?? []))
            @foreach(($runtimeWarnings ?? []) as $warning)
                <div class="crm-warning-item">{{ $warning }}</div>
            @endforeach
        @endif
        @if(!($leadConfig['supports_neutering'] ?? false))
            <div class="crm-warning-item">Neutering category is unavailable on this database (missing <code>pets.is_neutered</code>/<code>pets.is_nuetered</code>).</div>
        @endif
        @if(!($leadConfig['supports_video_follow_up'] ?? false))
            <div class="crm-warning-item">Video follow-up category is unavailable on this database (missing join columns).</div>
        @endif
        @if(($leadConfig['supports_video_follow_up'] ?? false) && !($leadConfig['supports_video_follow_up_mode_split'] ?? false))
            <div class="crm-warning-item">Video/In-clinic split is unavailable (missing <code>prescriptions.video_inclinic</code>).</div>
        @endif
        @if(!($leadConfig['supports_neutering_notification_join'] ?? false))
            <div class="crm-warning-item">Neutering notification join is unavailable (missing <code>fcm_notifications.data_payload</code>).</div>
        @endif
        @if(!($leadConfig['supports_follow_up_notification_join'] ?? false))
            <div class="crm-warning-item">Follow-up notification join is unavailable (missing <code>fcm_notifications.call_session</code> or <code>prescriptions.call_session</code>).</div>
        @endif
        @if(!($leadConfig['supports_vaccination_notification_join'] ?? false))
            <div class="crm-warning-item">Vaccination reminder module is unavailable (missing <code>fcm_notifications.notification_type</code> / <code>fcm_notifications.data_payload</code>).</div>
        @endif
        @if(!($leadConfig['supports_conversion_tracking'] ?? false))
            <div class="crm-warning-item">Lead conversion tracking is unavailable (missing <code>transactions.user_id</code> / <code>transactions.created_at</code>).</div>
        @endif
    </div>

    <div class="crm-app">
        <aside class="crm-sidebar">
            <div class="crm-sidebar-scroll" id="crmSidebarFilters">
                <div class="crm-sidebar-section">Pipeline</div>
                <button type="button" class="crm-side-item active" data-pipeline="all">
                    <span><i class="bi bi-grid-3x3-gap-fill"></i> All leads</span>
                    <span class="crm-side-count">{{ number_format($summary['filtered_users'] ?? 0) }}</span>
                </button>
                <button type="button" class="crm-side-item" data-pipeline="new">
                    <span><i class="bi bi-circle-fill"></i> New</span>
                    <span class="crm-side-count">{{ number_format((int) ($statusCounts['new'] ?? 0)) }}</span>
                </button>
                <button type="button" class="crm-side-item" data-pipeline="contacted">
                    <span><i class="bi bi-telephone"></i> Contacted</span>
                    <span class="crm-side-count">{{ number_format((int) ($statusCounts['contacted'] ?? 0)) }}</span>
                </button>
                <button type="button" class="crm-side-item" data-pipeline="booked">
                    <span><i class="bi bi-calendar2-check"></i> Booked</span>
                    <span class="crm-side-count">{{ number_format((int) ($statusCounts['booked'] ?? 0)) }}</span>
                </button>
                <button type="button" class="crm-side-item" data-pipeline="completed">
                    <span><i class="bi bi-check2-circle"></i> Completed</span>
                    <span class="crm-side-count">{{ number_format((int) ($statusCounts['completed'] ?? 0)) }}</span>
                </button>

                <div class="crm-sidebar-section">Services</div>
                <button type="button" class="crm-side-item" data-service="neutering">
                    <span><i class="bi bi-scissors"></i> Neutering</span>
                    <span class="crm-side-count">{{ number_format($summary['neutering_leads'] ?? 0) }}</span>
                </button>
                <button type="button" class="crm-side-item" data-service="video">
                    <span><i class="bi bi-camera-video"></i> Video Follow-up</span>
                    <span class="crm-side-count">{{ number_format($summary['video_follow_up_video_leads'] ?? 0) }}</span>
                </button>
                <button type="button" class="crm-side-item" data-service="clinic">
                    <span><i class="bi bi-hospital"></i> In-clinic</span>
                    <span class="crm-side-count">{{ number_format($summary['video_follow_up_in_clinic_leads'] ?? 0) }}</span>
                </button>
                <button type="button" class="crm-side-item" data-service="vaccination">
                    <span><i class="bi bi-shield-check"></i> Vaccination</span>
                    <span class="crm-side-count">{{ number_format($summary['vaccination_notified_users'] ?? 0) }}</span>
                </button>

                <div class="crm-sidebar-section">Alerts</div>
                <button type="button" class="crm-side-item" data-pipeline="overdue">
                    <span><i class="bi bi-exclamation-triangle" style="color: var(--crm-red);"></i> Overdue</span>
                    <span class="crm-side-count">{{ number_format($overdueCount) }}</span>
                </button>
                <button type="button" class="crm-side-item" data-pipeline="today">
                    <span><i class="bi bi-clock-history" style="color: var(--crm-amber);"></i> Due Today</span>
                    <span class="crm-side-count">{{ number_format($dueTodayCount) }}</span>
                </button>
            </div>

            <div class="crm-side-filters">
                <label class="crm-side-filter-label" for="crmSearchInput">Search</label>
                <input id="crmSearchInput" type="text" class="crm-input" placeholder="Name, phone, pet..." style="width: 100%; margin-bottom: 0.55rem;">

                <label class="crm-side-filter-label" for="crmSortSelect">Sort by</label>
                <select id="crmSortSelect" class="crm-select" style="width: 100%;">
                    <option value="next_action">Next action date</option>
                    <option value="last_updated">Latest notification</option>
                    <option value="highest_activity">Highest activity</option>
                    <option value="created_desc">Created date</option>
                </select>
            </div>
        </aside>

        <div class="crm-main">
            <section class="crm-list-panel">
                <div class="crm-list-header">
                    <div class="crm-list-title">Leads</div>
                    <div id="crmLeadCount" class="crm-list-count">0 total</div>
                </div>
                <div id="crmLeadList" class="crm-lead-list"></div>
            </section>

            <section class="crm-detail-panel">
                <div id="crmDetailEmpty" class="crm-detail-empty">
                    <i class="bi bi-person-vcard" style="font-size: 1.42rem;"></i>
                    <div>Select a lead to view details</div>
                </div>
                <div id="crmDetailWrap" class="crm-detail-wrap"></div>
            </section>
        </div>
    </div>

    @if($filteredTargetUsers instanceof \Illuminate\Pagination\LengthAwarePaginator && $filteredTargetUsers->total() > 0)
        <div class="crm-pagination">
            <div>
                Showing {{ number_format((int) ($filteredTargetUsers->firstItem() ?? 0)) }}
                to {{ number_format((int) ($filteredTargetUsers->lastItem() ?? 0)) }}
                of {{ number_format((int) $filteredTargetUsers->total()) }} users
                • Page {{ (int) $filteredTargetUsers->currentPage() }} / {{ (int) $filteredTargetUsers->lastPage() }}
            </div>
            <div class="crm-pg-pages">
                <a
                    href="{{ $filteredTargetUsers->onFirstPage() ? '#' : $filteredTargetUsers->previousPageUrl() }}"
                    class="crm-pg-btn {{ $filteredTargetUsers->onFirstPage() ? 'disabled' : '' }}"
                    @if($filteredTargetUsers->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
                >
                    Previous
                </a>

                @foreach($paginationWindow as $pageItem)
                    @if(is_string($pageItem) && str_starts_with($pageItem, 'ellipsis'))
                        <span class="crm-pg-ellipsis">…</span>
                    @elseif(is_numeric($pageItem))
                        @php $pageNumber = (int) $pageItem; @endphp
                        <a
                            href="{{ $filteredTargetUsers->url($pageNumber) }}"
                            class="crm-pg-num {{ $pageNumber === (int) $filteredTargetUsers->currentPage() ? 'active' : '' }}"
                            @if($pageNumber === (int) $filteredTargetUsers->currentPage()) aria-current="page" @endif
                        >
                            {{ $pageNumber }}
                        </a>
                    @endif
                @endforeach

                <a
                    href="{{ $filteredTargetUsers->hasMorePages() ? $filteredTargetUsers->nextPageUrl() : '#' }}"
                    class="crm-pg-btn {{ $filteredTargetUsers->hasMorePages() ? '' : 'disabled' }}"
                    @if(!$filteredTargetUsers->hasMorePages()) aria-disabled="true" tabindex="-1" @endif
                >
                    Next
                </a>
            </div>
        </div>
    @endif
</div>

<div class="crm-modal-overlay" id="crm-modal-log" data-modal="log">
    <div class="crm-modal">
        <h3>Log an Action</h3>
        <p class="crm-modal-sub">Record what happened with this lead.</p>
        <div class="crm-modal-grid">
            <div class="crm-mfield">
                <label for="crm-log-type">Action type</label>
                <select id="crm-log-type">
                    <option>Call made</option>
                    <option>WhatsApp sent</option>
                    <option>Email sent</option>
                    <option>Meeting completed</option>
                    <option>Note added</option>
                    <option>No answer</option>
                </select>
            </div>
            <div class="crm-mfield">
                <label for="crm-log-outcome">Outcome</label>
                <select id="crm-log-outcome">
                    <option>No answer</option>
                    <option>Spoke - interested</option>
                    <option>Spoke - follow-up needed</option>
                    <option>Message delivered</option>
                    <option>Message read, no reply</option>
                    <option>Booked</option>
                </select>
            </div>
        </div>
        <div class="crm-mfield">
            <label for="crm-log-notes">Notes</label>
            <textarea id="crm-log-notes" placeholder="What happened? Any useful context?"></textarea>
        </div>
        <div class="crm-modal-grid">
            <div class="crm-mfield">
                <label for="crm-log-datetime">Date & time</label>
                <input id="crm-log-datetime" type="datetime-local">
            </div>
            <div class="crm-mfield">
                <label for="crm-log-by">Done by</label>
                <input id="crm-log-by" type="text" value="Admin">
            </div>
        </div>
        <div class="crm-modal-actions">
            <button type="button" class="crm-modal-btn" data-close-modal="log">Cancel</button>
            <button type="button" class="crm-modal-btn primary" id="crmSaveLogAction">Save Action</button>
        </div>
    </div>
</div>

<div class="crm-modal-overlay" id="crm-modal-txn" data-modal="txn">
    <div class="crm-modal">
        <h3>Add Service Transaction</h3>
        <p class="crm-modal-sub">Record a sold service for this lead.</p>
        <div class="crm-modal-grid">
            <div class="crm-mfield">
                <label for="crm-txn-type">Service type</label>
                <select id="crm-txn-type">
                    <option>Home Visit</option>
                    <option>Video Consultation</option>
                    <option>Clinic Booking</option>
                    <option>Vaccination</option>
                    <option>Deworming</option>
                    <option>Health Check</option>
                </select>
            </div>
            <div class="crm-mfield">
                <label for="crm-txn-status">Status</label>
                <select id="crm-txn-status">
                    <option>Completed</option>
                    <option>Booked - upcoming</option>
                    <option>Cancelled</option>
                    <option>Refunded</option>
                </select>
            </div>
        </div>
        <div class="crm-mfield">
            <label for="crm-txn-desc">Service description</label>
            <input id="crm-txn-desc" type="text" placeholder="e.g. Neutering consultation">
        </div>
        <div class="crm-modal-grid">
            <div class="crm-mfield">
                <label for="crm-txn-amount">Amount charged (₹)</label>
                <input id="crm-txn-amount" type="number" placeholder="999">
            </div>
            <div class="crm-mfield">
                <label for="crm-txn-commission">Commission (₹)</label>
                <input id="crm-txn-commission" type="number" placeholder="300">
            </div>
        </div>
        <div class="crm-modal-grid">
            <div class="crm-mfield">
                <label for="crm-txn-vet">Vet / Clinic</label>
                <input id="crm-txn-vet" type="text" placeholder="Doctor/Clinic">
            </div>
            <div class="crm-mfield">
                <label for="crm-txn-date">Date</label>
                <input id="crm-txn-date" type="date">
            </div>
        </div>
        <div class="crm-modal-actions">
            <button type="button" class="crm-modal-btn" data-close-modal="txn">Cancel</button>
            <button type="button" class="crm-modal-btn primary" id="crmSaveTxn">Save Service</button>
        </div>
    </div>
</div>

<div class="crm-modal-overlay" id="crm-modal-next" data-modal="next">
    <div class="crm-modal">
        <h3>Set Next Action</h3>
        <p class="crm-modal-sub">Set what should happen next and when.</p>
        <div class="crm-mfield">
            <label for="crm-next-action">Action</label>
            <select id="crm-next-action">
                <option>Call</option>
                <option>WhatsApp follow-up</option>
                <option>Send service info</option>
                <option>Confirm booking</option>
                <option>Send reminder</option>
            </select>
        </div>
        <div class="crm-mfield">
            <label for="crm-next-details">Details</label>
            <input id="crm-next-details" type="text" placeholder="Action details">
        </div>
        <div class="crm-modal-grid">
            <div class="crm-mfield">
                <label for="crm-next-date">Due date</label>
                <input id="crm-next-date" type="date">
            </div>
            <div class="crm-mfield">
                <label for="crm-next-owner">Assigned to</label>
                <input id="crm-next-owner" type="text" value="Admin">
            </div>
        </div>
        <div class="crm-mfield">
            <label for="crm-next-blocker">Blocker (optional)</label>
            <input id="crm-next-blocker" type="text" placeholder="Any blocker for this lead">
        </div>
        <div class="crm-modal-actions">
            <button type="button" class="crm-modal-btn" data-close-modal="next">Cancel</button>
            <button type="button" class="crm-modal-btn primary" id="crmSaveNextAction">Set Action</button>
        </div>
    </div>
</div>

<div class="crm-modal-overlay" id="crm-modal-pet" data-modal="pet">
    <div class="crm-modal">
        <h3>Edit Pet Profile</h3>
        <p class="crm-modal-sub">Update known pet details for this lead.</p>
        <div class="crm-modal-grid">
            <div class="crm-mfield">
                <label for="crm-pet-name">Pet name</label>
                <input id="crm-pet-name" type="text">
            </div>
            <div class="crm-mfield">
                <label for="crm-pet-species">Species</label>
                <select id="crm-pet-species">
                    <option>Unknown</option>
                    <option>Cat</option>
                    <option>Dog</option>
                </select>
            </div>
        </div>
        <div class="crm-modal-grid">
            <div class="crm-mfield">
                <label for="crm-pet-breed">Breed</label>
                <input id="crm-pet-breed" type="text" placeholder="Optional">
            </div>
            <div class="crm-mfield">
                <label for="crm-pet-neutered">Neutered</label>
                <select id="crm-pet-neutered">
                    <option>Unknown</option>
                    <option>Yes</option>
                    <option>No</option>
                </select>
            </div>
        </div>
        <div class="crm-mfield">
            <label for="crm-pet-notes">Pet notes</label>
            <textarea id="crm-pet-notes" placeholder="Any useful medical or behavior notes"></textarea>
        </div>
        <div class="crm-modal-actions">
            <button type="button" class="crm-modal-btn" data-close-modal="pet">Cancel</button>
            <button type="button" class="crm-modal-btn primary" id="crmSavePet">Save Pet Profile</button>
        </div>
    </div>
</div>

<div id="crmToast" class="crm-toast"></div>
@endsection

@push('scripts')
<script>
(() => {
    const leadData = @json($leadRecords);
    const pageMeta = @json($leadPageMeta);
    const deleteRouteTemplate = @json($deleteRouteTemplate);
    const csrfToken = @json(csrf_token());

    const listEl = document.getElementById('crmLeadList');
    const detailWrapEl = document.getElementById('crmDetailWrap');
    const detailEmptyEl = document.getElementById('crmDetailEmpty');
    const leadCountEl = document.getElementById('crmLeadCount');
    const searchInput = document.getElementById('crmSearchInput');
    const sortSelect = document.getElementById('crmSortSelect');
    const sidebarFilterWrap = document.getElementById('crmSidebarFilters');
    const toastEl = document.getElementById('crmToast');

    if (!listEl || !detailWrapEl || !detailEmptyEl || !leadCountEl || !searchInput || !sortSelect || !sidebarFilterWrap) {
        return;
    }

    const nowDate = new Date();
    const nowDateKey = [
        nowDate.getFullYear(),
        String(nowDate.getMonth() + 1).padStart(2, '0'),
        String(nowDate.getDate()).padStart(2, '0'),
    ].join('-');

    const state = {
        pipeline: 'all',
        service: 'all',
        search: '',
        sortBy: 'next_action',
        selectedLeadId: leadData.length ? Number(leadData[0].id) : null,
        leads: (leadData || []).map((lead) => ({
            ...lead,
            manual_actions: [],
            manual_services: [],
            manual_next_action: null,
            manual_blocker: '',
            manual_pet_profile: null,
        })),
    };

    const statusStyles = {
        new: { label: 'New', className: 'crm-status-new' },
        contacted: { label: 'Contacted', className: 'crm-status-contacted' },
        booked: { label: 'Booked', className: 'crm-status-booked' },
        completed: { label: 'Completed', className: 'crm-status-completed' },
        lost: { label: 'Lost', className: 'crm-status-lost' },
    };

    const modalIds = ['log', 'txn', 'next', 'pet'];

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showToast(message) {
        if (!toastEl) return;
        toastEl.textContent = message;
        toastEl.classList.add('show');
        window.clearTimeout(showToast._timer);
        showToast._timer = window.setTimeout(() => {
            toastEl.classList.remove('show');
        }, 2200);
    }

    function formatDate(dateValue) {
        if (!dateValue) return '—';
        const date = new Date(dateValue);
        if (Number.isNaN(date.getTime())) return String(dateValue);
        return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function formatDateTime(dateValue) {
        if (!dateValue) return '—';
        const date = new Date(dateValue);
        if (Number.isNaN(date.getTime())) return String(dateValue);
        return date.toLocaleString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
    }

    function formatDateForInput(value) {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatDatetimeForInput(value) {
        const date = value ? new Date(value) : new Date();
        if (Number.isNaN(date.getTime())) return '';
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hour = String(date.getHours()).padStart(2, '0');
        const min = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hour}:${min}`;
    }

    function initials(name) {
        const parts = String(name || '')
            .split(' ')
            .map((p) => p.trim())
            .filter(Boolean);

        if (!parts.length) return 'U';
        if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
        return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
    }

    function getLeadById(id) {
        return state.leads.find((lead) => Number(lead.id) === Number(id)) || null;
    }

    function getSelectedLead() {
        if (state.selectedLeadId === null) return null;
        return getLeadById(state.selectedLeadId);
    }

    function resolveNextAction(lead) {
        if (lead.manual_next_action && lead.manual_next_action.date) {
            const manualDate = lead.manual_next_action.date;
            const dueState = resolveDueState(manualDate);
            return {
                type: lead.manual_next_action.action || 'Follow-up',
                date: manualDate,
                label: formatDate(manualDate),
                state: dueState,
                details: lead.manual_next_action.details || '',
                assignee: lead.manual_next_action.assignee || '',
                blocker: lead.manual_next_action.blocker || '',
            };
        }

        return {
            type: lead.next_action_type || 'Follow-up',
            date: lead.next_action_date || '',
            label: lead.next_action_date_label || formatDate(lead.next_action_date),
            state: lead.next_action_state || resolveDueState(lead.next_action_date),
            details: '',
            assignee: '',
            blocker: lead.manual_blocker || '',
        };
    }

    function resolveDueState(dateValue) {
        if (!dateValue) {
            return { key: 'none', label: 'No date set', css: 'none' };
        }

        const iso = String(dateValue).slice(0, 10);
        if (iso < nowDateKey) {
            return { key: 'overdue', label: 'Overdue', css: 'overdue' };
        }
        if (iso === nowDateKey) {
            return { key: 'today', label: 'Due Today', css: 'today' };
        }
        return { key: 'upcoming', label: 'Upcoming', css: 'upcoming' };
    }

    function getLeadActivityScore(lead) {
        return Number(lead.all_notifications_count || 0)
            + Number(lead.video_follow_up_count || 0)
            + Number(lead.vaccination_notification_count || 0)
            + Number(lead.neutering_notification_count || 0)
            + Number((lead.manual_actions || []).length);
    }

    function getLatestTouchTimestamp(lead) {
        const notificationTs = (lead.notifications || [])
            .map((item) => String(item.timestamp || ''))
            .filter(Boolean)
            .sort()
            .pop() || '';

        const manualTs = (lead.manual_actions || [])
            .map((item) => String(item.timestamp || ''))
            .filter(Boolean)
            .sort()
            .pop() || '';

        return [notificationTs, manualTs, String(lead.created_at || '')]
            .filter(Boolean)
            .sort()
            .pop() || '';
    }

    function matchesPipeline(lead, pipeline) {
        const next = resolveNextAction(lead);
        if (pipeline === 'all') return true;
        if (pipeline === 'today') return next.state.key === 'today';
        if (pipeline === 'overdue') return next.state.key === 'overdue';
        return String(lead.status_key || '').toLowerCase() === pipeline;
    }

    function matchesService(lead, service) {
        if (service === 'all') return true;
        if (service === 'neutering') return Boolean(lead.has_neutering);
        if (service === 'video') return Boolean(lead.has_video_follow_up_video);
        if (service === 'clinic') return Boolean(lead.has_video_follow_up_in_clinic);
        if (service === 'vaccination') return Boolean(lead.has_vaccination_reminder);
        return true;
    }

    function matchesSearch(lead, query) {
        if (!query) return true;
        const q = query.trim().toLowerCase();
        if (!q) return true;

        const blobs = [
            lead.name,
            lead.phone,
            lead.email,
            lead.city,
            lead.primary_pet,
            ...(lead.all_pet_names || []),
            ...(lead.category_tags || []),
            lead.follow_up_type_label,
        ].map((v) => String(v || '').toLowerCase());

        return blobs.some((blob) => blob.includes(q));
    }

    function compareDatesAsc(left, right) {
        const l = left ? String(left).slice(0, 10) : '9999-12-31';
        const r = right ? String(right).slice(0, 10) : '9999-12-31';
        if (l === r) return 0;
        return l < r ? -1 : 1;
    }

    function getVisibleLeads() {
        const filtered = state.leads
            .filter((lead) => matchesPipeline(lead, state.pipeline))
            .filter((lead) => matchesService(lead, state.service))
            .filter((lead) => matchesSearch(lead, state.search));

        filtered.sort((a, b) => {
            if (state.sortBy === 'created_desc') {
                const left = String(a.created_at || '');
                const right = String(b.created_at || '');
                return right.localeCompare(left);
            }

            if (state.sortBy === 'last_updated') {
                const left = getLatestTouchTimestamp(a);
                const right = getLatestTouchTimestamp(b);
                return String(right).localeCompare(String(left));
            }

            if (state.sortBy === 'highest_activity') {
                const scoreDiff = getLeadActivityScore(b) - getLeadActivityScore(a);
                if (scoreDiff !== 0) return scoreDiff;
                return String(a.name || '').localeCompare(String(b.name || ''));
            }

            const nextCmp = compareDatesAsc(resolveNextAction(a).date, resolveNextAction(b).date);
            if (nextCmp !== 0) return nextCmp;

            const statusWeight = { overdue: 0, today: 1, upcoming: 2, none: 3 };
            const leftState = resolveNextAction(a).state.key;
            const rightState = resolveNextAction(b).state.key;
            const weightDiff = (statusWeight[leftState] ?? 9) - (statusWeight[rightState] ?? 9);
            if (weightDiff !== 0) return weightDiff;

            return String(a.name || '').localeCompare(String(b.name || ''));
        });

        return filtered;
    }

    function updateSidebarActiveState() {
        sidebarFilterWrap.querySelectorAll('.crm-side-item[data-pipeline]').forEach((btn) => {
            const isActive = btn.getAttribute('data-pipeline') === state.pipeline;
            btn.classList.toggle('active', isActive);
        });

        sidebarFilterWrap.querySelectorAll('.crm-side-item[data-service]').forEach((btn) => {
            const isActive = btn.getAttribute('data-service') === state.service;
            btn.classList.toggle('active', isActive);
        });
    }

    function renderLeadList() {
        const leads = getVisibleLeads();
        leadCountEl.textContent = `${leads.length} total`;

        if (!leads.length) {
            listEl.innerHTML = '<div class="crm-empty">No leads match the selected filters.</div>';
            detailWrapEl.classList.remove('active');
            detailEmptyEl.style.display = 'flex';
            state.selectedLeadId = null;
            return;
        }

        if (!leads.some((lead) => Number(lead.id) === Number(state.selectedLeadId))) {
            state.selectedLeadId = Number(leads[0].id);
        }

        listEl.innerHTML = leads.map((lead) => {
            const isActive = Number(lead.id) === Number(state.selectedLeadId);
            const statusMeta = statusStyles[String(lead.status_key || '').toLowerCase()] || statusStyles.new;
            const next = resolveNextAction(lead);
            const cityTag = lead.city ? `<span class="crm-tag">${escapeHtml(lead.city)}</span>` : '';
            const notifsTag = `<span class="crm-tag">${Number(lead.all_notifications_count || 0)} notifs</span>`;
            const followTag = lead.follow_up_type_label ? `<span class="crm-tag">${escapeHtml(lead.follow_up_type_label)}</span>` : '';
            const revenueTag = lead.conversion_captured
                ? `<span class="crm-rev">Converted</span>`
                : `<span class="crm-rev">Open</span>`;
            const primaryPet = lead.primary_pet
                ? `${escapeHtml(lead.primary_pet)} · ${Math.max(Number(lead.all_pet_names?.length || 0), 1)} pet${Math.max(Number(lead.all_pet_names?.length || 0), 1) > 1 ? 's' : ''}`
                : `${Number(lead.neutering_pet_count || 0)} pet${Number(lead.neutering_pet_count || 0) === 1 ? '' : 's'}`;
            const nextPrefix = next.state.key === 'none' ? 'No next action set' : `${escapeHtml(next.type)} ${next.state.key === 'overdue' ? 'overdue' : 'due'}`;
            const nextSuffix = next.state.key === 'none' ? '' : ` - ${escapeHtml(next.label)}`;

            return `
                <article class="crm-lead-card ${isActive ? 'active' : ''}" data-lead-id="${Number(lead.id)}">
                    ${next.state.key === 'overdue' ? '<span class="crm-lead-urgent"></span>' : ''}
                    <div class="crm-lead-top">
                        <div>
                            <div class="crm-lead-name">${escapeHtml(lead.name)}</div>
                            <div class="crm-lead-meta-id">#${Number(lead.id)} · ${escapeHtml(lead.created_short || '—')}</div>
                        </div>
                        <span class="crm-status-badge ${statusMeta.className}">${escapeHtml(statusMeta.label)}</span>
                    </div>
                    <div class="crm-lead-pet">${escapeHtml(primaryPet)}</div>
                    <div class="crm-lead-tags">
                        ${cityTag}
                        ${notifsTag}
                        ${followTag}
                        ${revenueTag}
                    </div>
                    <div class="crm-next-line ${escapeHtml(next.state.css || 'none')}">${escapeHtml(nextPrefix + nextSuffix)}</div>
                </article>
            `;
        }).join('');
    }

    function buildTimelineItems(lead) {
        const items = [];

        if (lead.created_at) {
            items.push({
                title: 'Lead created',
                text: `${lead.name} joined Snoutiq.`,
                badge: '<span class="crm-pill crm-pill-blue">New lead</span>',
                timestamp: lead.created_at,
                icon: '<i class="bi bi-person-plus"></i>',
            });
        }

        (lead.manual_actions || []).forEach((action) => {
            items.push({
                title: action.action || 'Manual action',
                text: action.notes || action.outcome || 'Action logged from CRM panel.',
                badge: `<span class="crm-pill crm-pill-purple">${escapeHtml(action.by || 'Admin')}</span>`,
                timestamp: action.timestamp || '',
                icon: '<i class="bi bi-journal-text"></i>',
            });
        });

        (lead.notifications || []).forEach((notif) => {
            const clicked = notif.clicked === true;
            const badgeClass = clicked ? 'crm-pill-green' : 'crm-pill-red';
            const clickLabel = clicked ? 'Clicked' : 'Not clicked';
            items.push({
                title: notif.title || notif.type || 'Notification sent',
                text: notif.text || `${notif.bucket_label || 'Notification'} sent to user.`,
                badge: `<span class="crm-pill ${badgeClass}">${clickLabel}</span>`,
                timestamp: notif.timestamp || '',
                icon: '<i class="bi bi-bell"></i>',
            });
        });

        const next = resolveNextAction(lead);
        if (next.date) {
            items.push({
                title: `${next.type} ${next.state.key === 'overdue' ? 'overdue' : 'scheduled'}`,
                text: next.details || `Action date: ${next.label}`,
                badge: `<span class="crm-pill ${next.state.key === 'overdue' ? 'crm-pill-red' : (next.state.key === 'today' ? 'crm-pill-amber' : 'crm-pill-green')}">${next.state.label}</span>`,
                timestamp: next.date,
                icon: '<i class="bi bi-calendar-event"></i>',
            });
        }

        return items
            .sort((left, right) => String(right.timestamp || '').localeCompare(String(left.timestamp || '')))
            .slice(0, 18);
    }

    function buildServices(lead) {
        const services = [];

        if (lead.conversion_captured) {
            services.push({
                title: `Transaction #${Number(lead.conversion_transaction_id || 0)}`,
                amount: '₹0',
                tags: [
                    lead.conversion_transaction_type || 'Unknown type',
                    lead.conversion_transaction_status || 'status n/a',
                    formatDate(lead.conversion_transaction_at),
                    'Attributed conversion',
                ],
            });
        }

        (lead.manual_services || []).forEach((svc) => {
            services.push({
                title: svc.description || svc.type || 'Service',
                amount: svc.amount ? `₹${svc.amount}` : '₹0',
                tags: [
                    svc.type || 'Service',
                    svc.status || 'Unknown',
                    svc.date || '',
                    svc.vet || '',
                    svc.commission ? `Commission ₹${svc.commission}` : '',
                ].filter(Boolean),
            });
        });

        return services;
    }

    function buildBlocker(lead, nextAction) {
        if (lead.manual_blocker && String(lead.manual_blocker).trim() !== '') {
            return String(lead.manual_blocker).trim();
        }

        if (nextAction.state.key === 'overdue') {
            return 'Follow-up date has passed. Reschedule this lead and assign an owner immediately.';
        }

        if (Number(lead.all_notifications_count || 0) >= 3 && Number(lead.clicked_notifications_count || 0) === 0) {
            return 'User has multiple notifications delivered but no clicks. Try call-first outreach instead of push-only follow-ups.';
        }

        if (!lead.phone) {
            return 'No phone number available. Add direct contact info before next outreach.';
        }

        return '';
    }

    function buildNotificationRows(lead) {
        const rows = (lead.notifications || []).slice(0, 25);

        if (!rows.length) {
            return '<div class="crm-empty">No notifications available for this lead.</div>';
        }

        return rows.map((notif) => {
            const clicked = notif.clicked === true;
            const clickedLabel = clicked
                ? '<span class="crm-pill crm-pill-green">Clicked</span>'
                : '<span class="crm-pill crm-pill-red">Not clicked</span>';
            const title = notif.title || notif.type || 'Notification';
            const suffix = notif.bucket_label ? ` · ${notif.bucket_label}` : '';
            return `
                <div class="crm-notif-row">
                    ${clickedLabel}
                    <span class="crm-notif-title" title="${escapeHtml(title)}">${escapeHtml(title)}${escapeHtml(suffix)}</span>
                    <span class="crm-notif-time">${escapeHtml(formatDateTime(notif.timestamp))}</span>
                </div>
            `;
        }).join('');
    }

    function renderDetail() {
        const lead = getSelectedLead();

        if (!lead) {
            detailWrapEl.classList.remove('active');
            detailEmptyEl.style.display = 'flex';
            detailWrapEl.innerHTML = '';
            return;
        }

        const nextAction = resolveNextAction(lead);
        const statusMeta = statusStyles[String(lead.status_key || '').toLowerCase()] || statusStyles.new;
        const timelineItems = buildTimelineItems(lead);
        const services = buildServices(lead);
        const blockerText = buildBlocker(lead, nextAction);

        const totalNotifs = Number(lead.all_notifications_count || 0);
        const clickedNotifs = Number(lead.clicked_notifications_count || 0);
        const clickRate = totalNotifs > 0 ? Math.round((clickedNotifs / totalNotifs) * 100) : 0;
        const serviceCount = services.length;
        const daysSinceCreated = lead.created_at
            ? Math.max(0, Math.floor((Date.now() - new Date(lead.created_at).getTime()) / (1000 * 60 * 60 * 24)))
            : '—';

        const deleteActionUrl = String(deleteRouteTemplate || '').replace('__USER_ID__', String(Number(lead.id)));
        const ownerName = lead.name || 'Unnamed user';
        const ownerPhone = lead.phone || 'No phone';
        const ownerEmail = lead.email || 'No email';
        const ownerCity = lead.city || 'Unknown city';
        const petNames = (lead.all_pet_names || lead.neutering_pet_names || []).filter(Boolean);
        const firstPet = petNames.length ? petNames[0] : (lead.primary_pet || 'Not provided');
        const petCount = Math.max(Number(petNames.length || 0), Number(lead.neutering_pet_count || 0));

        const categoryPills = (lead.category_tags || []).length
            ? (lead.category_tags || []).map((tag) => `<span class="crm-pill crm-pill-blue">${escapeHtml(tag)}</span>`).join(' ')
            : '<span class="crm-pill crm-pill-red">No category</span>';

        detailWrapEl.innerHTML = `
            <div class="crm-dh">
                <div class="crm-dh-left">
                    <div class="crm-avatar">${escapeHtml(initials(lead.name))}</div>
                    <div>
                        <div class="crm-dh-name">${escapeHtml(lead.name)}</div>
                        <div class="crm-dh-sub">
                            <span><i class="bi bi-telephone"></i> ${escapeHtml(ownerPhone)}</span>
                            <span>·</span>
                            <span>${escapeHtml(ownerCity)}</span>
                            <span>·</span>
                            <span class="crm-code">#${Number(lead.id)}</span>
                        </div>
                    </div>
                </div>
                <div class="crm-dh-actions">
                    <select class="crm-status-select" id="crmStatusSelect">
                        ${Object.entries(statusStyles).map(([key, meta]) => `
                            <option value="${escapeHtml(key)}" ${key === lead.status_key ? 'selected' : ''}>${escapeHtml(meta.label)}</option>
                        `).join('')}
                    </select>
                    <button type="button" class="crm-btn primary" data-open-modal="log">+ Log Action</button>
                    <button type="button" class="crm-btn" data-open-modal="txn">+ Add Service</button>
                    <button type="button" class="crm-btn" data-open-modal="next">Set Next Action</button>
                    <form method="POST" action="${escapeHtml(deleteActionUrl)}" class="d-inline-block" onsubmit="return confirm('Delete this user and related data? This action cannot be undone.')">
                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="lead_filter" value="${escapeHtml(pageMeta.lead_filter || '')}">
                        <input type="hidden" name="limit" value="${escapeHtml(String(pageMeta.limit || ''))}">
                        <input type="hidden" name="per_page" value="${escapeHtml(String(pageMeta.per_page || ''))}">
                        <input type="hidden" name="page" value="${escapeHtml(String(pageMeta.page || '1'))}">
                        <button type="submit" class="crm-btn danger">Delete User</button>
                    </form>
                </div>
            </div>

            <div class="crm-next-box">
                <div>
                    <div class="crm-next-title">${nextAction.state.key === 'overdue' ? 'Follow-up overdue' : `Next action: ${escapeHtml(nextAction.type)}`}</div>
                    <div class="crm-next-text">${escapeHtml(nextAction.details || `${nextAction.type} for ${lead.name} is ${nextAction.state.key === 'none' ? 'not scheduled yet' : nextAction.state.label.toLowerCase()}.`)}</div>
                </div>
                <div>
                    <div class="crm-next-date">${escapeHtml(nextAction.label)}</div>
                </div>
            </div>

            ${blockerText ? `<div class="crm-blocker"><b>Blocker:</b> ${escapeHtml(blockerText)}</div>` : ''}

            <div class="crm-rev-bar">
                <div class="crm-rev-item">
                    <div class="crm-rev-num green">${Number(totalNotifs)}</div>
                    <div class="crm-rev-label">Notifications</div>
                </div>
                <div class="crm-rev-item">
                    <div class="crm-rev-num">${Number(clickedNotifs)}</div>
                    <div class="crm-rev-label">Clicked</div>
                </div>
                <div class="crm-rev-item">
                    <div class="crm-rev-num blue">${clickRate}%</div>
                    <div class="crm-rev-label">Click Rate</div>
                </div>
                <div class="crm-rev-item">
                    <div class="crm-rev-num amber">${serviceCount}</div>
                    <div class="crm-rev-label">Services</div>
                </div>
                <div class="crm-rev-item">
                    <div class="crm-rev-num">${daysSinceCreated}</div>
                    <div class="crm-rev-label">Days Since Lead</div>
                </div>
            </div>

            <div class="crm-tabs" id="crmDetailTabs">
                <button type="button" class="crm-tab active" data-tab="profile">Profile</button>
                <button type="button" class="crm-tab" data-tab="timeline">Timeline</button>
                <button type="button" class="crm-tab" data-tab="services">Services</button>
                <button type="button" class="crm-tab" data-tab="notifications">Notifications</button>
            </div>

            <div class="crm-tab-content active" data-tab-content="profile">
                <div class="crm-grid-2">
                    <div class="crm-card">
                        <div class="crm-card-title">Owner Details</div>
                        <div class="crm-field-row"><span class="crm-fr-label">Name</span><span class="crm-fr-val">${escapeHtml(ownerName)}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Phone</span><span class="crm-fr-val">${escapeHtml(ownerPhone)}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Email</span><span class="crm-fr-val">${escapeHtml(ownerEmail)}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">City</span><span class="crm-fr-val">${escapeHtml(ownerCity)}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Created</span><span class="crm-fr-val">${escapeHtml(formatDateTime(lead.created_at))}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Current status</span><span class="crm-fr-val"><span class="crm-status-badge ${escapeHtml(statusMeta.className)}">${escapeHtml(statusMeta.label)}</span></span></div>
                    </div>

                    <div class="crm-card">
                        <div class="crm-card-title">Pet Profile <button type="button" data-open-modal="pet">Edit</button></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Primary pet</span><span class="crm-fr-val">${escapeHtml(firstPet)}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Pet count</span><span class="crm-fr-val">${petCount || 0}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Neutering leads</span><span class="crm-fr-val ${lead.has_neutering ? 'warn' : 'missing'}">${lead.has_neutering ? 'Yes' : 'No'}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Vaccination signal</span><span class="crm-fr-val ${lead.has_vaccination_reminder ? 'ok' : 'missing'}">${lead.has_vaccination_reminder ? 'Yes' : 'No'}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Follow-up type</span><span class="crm-fr-val ${lead.follow_up_type_label ? '' : 'missing'}">${escapeHtml(lead.follow_up_type_label || 'Not set')}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Categories</span><span class="crm-fr-val">${categoryPills}</span></div>
                    </div>
                </div>

                <div class="crm-grid-2">
                    <div class="crm-card">
                        <div class="crm-card-title">Lead Signals</div>
                        <div class="crm-field-row"><span class="crm-fr-label">Neutering notifications</span><span class="crm-fr-val">${Number(lead.neutering_notification_count || 0)}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Vaccination notifications</span><span class="crm-fr-val">${Number(lead.vaccination_notification_count || 0)}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Follow-ups</span><span class="crm-fr-val">${Number(lead.video_follow_up_count || 0)}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Prescription follow-up</span><span class="crm-fr-val">${escapeHtml(lead.prescription_follow_up_date_label || '—')}</span></div>
                        <div class="crm-field-row"><span class="crm-fr-label">Next action state</span><span class="crm-fr-val">${escapeHtml(nextAction.state.label)}</span></div>
                    </div>

                    <div class="crm-card">
                        <div class="crm-card-title">Internal Notes</div>
                        <div class="crm-note-box" id="crmLeadNotesBox">
                            ${(lead.manual_actions || []).length
                                ? escapeHtml((lead.manual_actions[0] && lead.manual_actions[0].notes) ? lead.manual_actions[0].notes : 'Most recent action added from CRM panel.')
                                : 'Use "Log Action" to keep contextual notes for the next outreach.'}
                        </div>
                    </div>
                </div>
            </div>

            <div class="crm-tab-content" data-tab-content="timeline">
                <div class="crm-card">
                    <div class="crm-card-title">Activity Timeline <button type="button" data-open-modal="log">+ Log</button></div>
                    ${timelineItems.length ? timelineItems.map((item) => `
                        <div class="crm-timeline-item">
                            <div class="crm-tl-icon">${item.icon || '<i class="bi bi-dot"></i>'}</div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="crm-tl-title">${escapeHtml(item.title || 'Activity')}</div>
                                <div class="crm-tl-text">${escapeHtml(item.text || '')}</div>
                                <div class="crm-tl-meta">
                                    <span class="crm-tl-time">${escapeHtml(formatDateTime(item.timestamp))}</span>
                                    ${item.badge || ''}
                                </div>
                            </div>
                        </div>
                    `).join('') : '<div class="crm-empty">No timeline events available.</div>'}
                </div>
            </div>

            <div class="crm-tab-content" data-tab-content="services">
                <div class="crm-add-service" data-open-modal="txn">+ Add a service transaction</div>
                ${services.length ? services.map((svc) => `
                    <div class="crm-service-card">
                        <div class="crm-service-head">
                            <div class="crm-service-title">${escapeHtml(svc.title)}</div>
                            <div class="crm-service-amount">${escapeHtml(svc.amount || '₹0')}</div>
                        </div>
                        <div class="crm-service-meta">
                            ${(svc.tags || []).map((tag) => `<span class="crm-service-tag">${escapeHtml(tag)}</span>`).join('')}
                        </div>
                    </div>
                `).join('') : `
                    <div class="crm-card">
                        <div class="crm-empty" style="padding: 1.4rem 0.8rem;">
                            No services recorded for this lead yet.
                        </div>
                    </div>
                `}
            </div>

            <div class="crm-tab-content" data-tab-content="notifications">
                <div class="crm-card">
                    <div class="crm-card-title">Notification Log — ${Number(totalNotifs)} sent · ${Number(clickedNotifs)} clicked · ${lead.conversion_captured ? 1 : 0} converted</div>
                    ${buildNotificationRows(lead)}
                </div>
            </div>
        `;

        detailWrapEl.classList.add('active');
        detailEmptyEl.style.display = 'none';
        bindDetailHandlers();
    }

    function bindDetailHandlers() {
        const tabButtons = detailWrapEl.querySelectorAll('.crm-tab');
        const tabContents = detailWrapEl.querySelectorAll('.crm-tab-content');

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const tab = button.getAttribute('data-tab');
                tabButtons.forEach((btn) => btn.classList.toggle('active', btn === button));
                tabContents.forEach((content) => {
                    const isActive = content.getAttribute('data-tab-content') === tab;
                    content.classList.toggle('active', isActive);
                });
            });
        });

        const statusSelect = detailWrapEl.querySelector('#crmStatusSelect');
        if (statusSelect) {
            statusSelect.addEventListener('change', () => {
                const lead = getSelectedLead();
                if (!lead) return;
                const value = String(statusSelect.value || 'new').toLowerCase();
                const statusMeta = statusStyles[value] || statusStyles.new;
                lead.status_key = value;
                lead.status_label = statusMeta.label;
                lead.status_class = statusMeta.className;
                renderLeadList();
                renderDetail();
                showToast('Lead status updated in this CRM view.');
            });
        }

        detailWrapEl.querySelectorAll('[data-open-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modalKey = button.getAttribute('data-open-modal');
                openModal(modalKey);
            });
        });
    }

    function openModal(key) {
        const modal = document.getElementById(`crm-modal-${key}`);
        if (!modal) return;

        if (key === 'log') {
            const datetimeInput = document.getElementById('crm-log-datetime');
            if (datetimeInput) datetimeInput.value = formatDatetimeForInput(new Date());
        }

        if (key === 'next') {
            const lead = getSelectedLead();
            const nextDateInput = document.getElementById('crm-next-date');
            const nextDetailInput = document.getElementById('crm-next-details');
            const nextBlockerInput = document.getElementById('crm-next-blocker');
            const nextOwnerInput = document.getElementById('crm-next-owner');
            if (lead && nextDateInput) nextDateInput.value = formatDateForInput(resolveNextAction(lead).date);
            if (nextDetailInput) nextDetailInput.value = '';
            if (nextBlockerInput) nextBlockerInput.value = lead?.manual_blocker || '';
            if (nextOwnerInput) nextOwnerInput.value = 'Admin';
        }

        if (key === 'pet') {
            const lead = getSelectedLead();
            if (lead) {
                const petProfile = lead.manual_pet_profile || {};
                const petNameInput = document.getElementById('crm-pet-name');
                const petSpeciesInput = document.getElementById('crm-pet-species');
                const petBreedInput = document.getElementById('crm-pet-breed');
                const petNeuteredInput = document.getElementById('crm-pet-neutered');
                const petNotesInput = document.getElementById('crm-pet-notes');

                if (petNameInput) petNameInput.value = petProfile.name || lead.primary_pet || '';
                if (petSpeciesInput) petSpeciesInput.value = petProfile.species || 'Unknown';
                if (petBreedInput) petBreedInput.value = petProfile.breed || '';
                if (petNeuteredInput) petNeuteredInput.value = petProfile.neutered || 'Unknown';
                if (petNotesInput) petNotesInput.value = petProfile.notes || '';
            }
        }

        modal.classList.add('open');
    }

    function closeModal(key) {
        const modal = document.getElementById(`crm-modal-${key}`);
        if (!modal) return;
        modal.classList.remove('open');
    }

    function closeModalByElement(modalEl) {
        if (!modalEl) return;
        modalEl.classList.remove('open');
    }

    function attachModalHandlers() {
        modalIds.forEach((modalKey) => {
            const modal = document.getElementById(`crm-modal-${modalKey}`);
            if (!modal) return;

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal(modalKey);
                }
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modalKey = button.getAttribute('data-close-modal');
                closeModal(modalKey);
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            modalIds.forEach((modalKey) => closeModal(modalKey));
        });
    }

    function attachActionSavers() {
        const saveLogBtn = document.getElementById('crmSaveLogAction');
        const saveTxnBtn = document.getElementById('crmSaveTxn');
        const saveNextBtn = document.getElementById('crmSaveNextAction');
        const savePetBtn = document.getElementById('crmSavePet');

        if (saveLogBtn) {
            saveLogBtn.addEventListener('click', () => {
                const lead = getSelectedLead();
                if (!lead) return;

                const action = String(document.getElementById('crm-log-type')?.value || 'Action');
                const outcome = String(document.getElementById('crm-log-outcome')?.value || '');
                const notes = String(document.getElementById('crm-log-notes')?.value || '').trim();
                const doneBy = String(document.getElementById('crm-log-by')?.value || 'Admin').trim() || 'Admin';
                const timestamp = String(document.getElementById('crm-log-datetime')?.value || '');

                lead.manual_actions.unshift({
                    action,
                    outcome,
                    notes,
                    by: doneBy,
                    timestamp: timestamp ? new Date(timestamp).toISOString() : new Date().toISOString(),
                });

                closeModal('log');
                renderLeadList();
                renderDetail();
                showToast('Action logged in CRM panel.');
            });
        }

        if (saveTxnBtn) {
            saveTxnBtn.addEventListener('click', () => {
                const lead = getSelectedLead();
                if (!lead) return;

                const type = String(document.getElementById('crm-txn-type')?.value || 'Service');
                const status = String(document.getElementById('crm-txn-status')?.value || 'Completed');
                const description = String(document.getElementById('crm-txn-desc')?.value || '').trim();
                const amount = String(document.getElementById('crm-txn-amount')?.value || '').trim();
                const commission = String(document.getElementById('crm-txn-commission')?.value || '').trim();
                const vet = String(document.getElementById('crm-txn-vet')?.value || '').trim();
                const date = String(document.getElementById('crm-txn-date')?.value || '').trim();

                lead.manual_services.unshift({
                    type,
                    status,
                    description,
                    amount,
                    commission,
                    vet,
                    date,
                });

                closeModal('txn');
                renderDetail();
                showToast('Service transaction added to CRM view.');
            });
        }

        if (saveNextBtn) {
            saveNextBtn.addEventListener('click', () => {
                const lead = getSelectedLead();
                if (!lead) return;

                const action = String(document.getElementById('crm-next-action')?.value || 'Follow-up');
                const details = String(document.getElementById('crm-next-details')?.value || '').trim();
                const date = String(document.getElementById('crm-next-date')?.value || '').trim();
                const assignee = String(document.getElementById('crm-next-owner')?.value || 'Admin').trim() || 'Admin';
                const blocker = String(document.getElementById('crm-next-blocker')?.value || '').trim();

                if (!date) {
                    showToast('Please set a due date before saving next action.');
                    return;
                }

                lead.manual_next_action = {
                    action,
                    details,
                    date,
                    assignee,
                    blocker,
                };
                lead.manual_blocker = blocker;

                closeModal('next');
                renderLeadList();
                renderDetail();
                showToast('Next action updated in CRM view.');
            });
        }

        if (savePetBtn) {
            savePetBtn.addEventListener('click', () => {
                const lead = getSelectedLead();
                if (!lead) return;

                const name = String(document.getElementById('crm-pet-name')?.value || '').trim();
                const species = String(document.getElementById('crm-pet-species')?.value || 'Unknown').trim();
                const breed = String(document.getElementById('crm-pet-breed')?.value || '').trim();
                const neutered = String(document.getElementById('crm-pet-neutered')?.value || 'Unknown').trim();
                const notes = String(document.getElementById('crm-pet-notes')?.value || '').trim();

                lead.manual_pet_profile = { name, species, breed, neutered, notes };
                if (name) {
                    lead.primary_pet = name;
                    if (!Array.isArray(lead.all_pet_names)) {
                        lead.all_pet_names = [];
                    }
                    if (!lead.all_pet_names.includes(name)) {
                        lead.all_pet_names.unshift(name);
                    }
                }

                closeModal('pet');
                renderLeadList();
                renderDetail();
                showToast('Pet profile updated in CRM view.');
            });
        }
    }

    function attachListHandlers() {
        listEl.addEventListener('click', (event) => {
            const card = event.target.closest('[data-lead-id]');
            if (!card) return;
            const leadId = Number(card.getAttribute('data-lead-id'));
            if (!Number.isFinite(leadId)) return;
            state.selectedLeadId = leadId;
            renderLeadList();
            renderDetail();
        });
    }

    function attachSidebarHandlers() {
        sidebarFilterWrap.addEventListener('click', (event) => {
            const pipelineBtn = event.target.closest('[data-pipeline]');
            if (pipelineBtn) {
                state.pipeline = String(pipelineBtn.getAttribute('data-pipeline') || 'all');
                updateSidebarActiveState();
                renderLeadList();
                renderDetail();
                return;
            }

            const serviceBtn = event.target.closest('[data-service]');
            if (!serviceBtn) return;
            const selectedService = String(serviceBtn.getAttribute('data-service') || 'all');
            state.service = selectedService === state.service ? 'all' : selectedService;
            updateSidebarActiveState();
            renderLeadList();
            renderDetail();
        });
    }

    function attachSearchSortHandlers() {
        searchInput.addEventListener('input', () => {
            state.search = String(searchInput.value || '');
            renderLeadList();
            renderDetail();
        });

        sortSelect.addEventListener('change', () => {
            state.sortBy = String(sortSelect.value || 'next_action');
            renderLeadList();
            renderDetail();
        });
    }

    function bootstrap() {
        updateSidebarActiveState();
        renderLeadList();
        renderDetail();
        attachListHandlers();
        attachSidebarHandlers();
        attachSearchSortHandlers();
        attachModalHandlers();
        attachActionSavers();
    }

    bootstrap();
})();
</script>
@endpush
