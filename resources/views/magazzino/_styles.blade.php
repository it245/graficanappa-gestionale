<link rel="stylesheet" href="{{ asset('css/mes-tokens.css') }}">
<style>
    .mag-page { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: var(--mes-bg-page, #f8fafc); }
    .mag-page .mag-card {
        background: var(--mes-bg-card, #fff);
        border: 1px solid var(--mes-border, #e5e7eb);
        border-radius: var(--mes-radius-lg, 12px);
        box-shadow: var(--mes-shadow-md, 0 4px 6px -1px rgba(0,0,0,0.1));
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .mag-page .mag-kpi { cursor: default; }
    .mag-page .mag-kpi:hover { transform: translateY(-2px); box-shadow: var(--mes-shadow-lg, 0 10px 15px -3px rgba(0,0,0,0.1)); }
    .mag-page table thead th {
        background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
        color: #f9fafb;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        font-size: 11px;
        border-bottom: 2px solid #374151 !important;
        border-top: none;
        padding-top: 10px;
        padding-bottom: 10px;
    }
    .mag-page table tbody tr:nth-child(even) td { background: rgba(0,0,0,0.015); }
    .mag-page .mag-num { font-family: 'IBM Plex Mono', ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric: tabular-nums; }
    .mag-page .mag-kpi-value { font-family: 'IBM Plex Mono', ui-monospace, monospace; font-variant-numeric: tabular-nums; font-size: 2rem; font-weight: 700; }
    .mag-page .mag-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        line-height: 1.4;
    }
    .mag-page .mag-pill-success { background: rgba(16,185,129,0.12); color: #047857; }
    .mag-page .mag-pill-warning { background: rgba(245,158,11,0.15); color: #92400e; }
    .mag-page .mag-pill-danger  { background: rgba(239,68,68,0.12);  color: #991b1b; }
    .mag-page .mag-pill-info    { background: rgba(59,130,246,0.12); color: #1e40af; }
    .mag-page .mag-pill-neutral { background: rgba(107,114,128,0.15); color: #374151; }
    .mag-page .form-control, .mag-page .form-select {
        border-radius: var(--mes-radius-md, 8px);
        border: 1px solid var(--mes-border, #e5e7eb);
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .mag-page .form-control:focus, .mag-page .form-select:focus {
        border-color: var(--mes-primary, #3b82f6);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        outline: none;
    }
    @media (prefers-color-scheme: dark) {
        .mag-page { background: #0f172a; color: #e5e7eb; }
        .mag-page .mag-card { background: #1e293b; border-color: #334155; color: #e5e7eb; }
        .mag-page table { color: #e5e7eb !important; }
        .mag-page table tbody tr:nth-child(even) td { background: rgba(255,255,255,0.03); }
        .mag-page table tbody td { border-color: #334155; }
        .mag-page .form-control, .mag-page .form-select { background: #0f172a; color: #e5e7eb; border-color: #334155; }
    }
</style>
