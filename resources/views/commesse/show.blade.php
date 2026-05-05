@extends('layouts.app')

@php $operatore = request()->attributes->get('operatore') ?? auth('operatore')->user(); @endphp
@section('title'){{ ($operatore->nome ?? '') . ' ' . ($operatore->cognome ?? '') }}@endsection

@section('content')
<style>
/* === Pulsanti azione touch-first (operatore tablet) === */
.azioni-cerchi {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-left: 20px;
}
.azioni-cerchi label {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 96px;
    height: 96px;
    border-radius: 50%;
    color: #fff;
    font-weight: 700;
    font-size: 15px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    cursor: pointer;
    user-select: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: transform 0.1s ease, box-shadow 0.1s ease;
}
.azioni-cerchi label:active {
    transform: scale(0.95);
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
.badge-avvia { background-color: #16a34a; }
.badge-pausa { background-color: #f59e0b; color: #1f2937 !important; }
.badge-termina { background-color: #dc2626; }
.azioni-cerchi input[type="checkbox"] { display: none; }
.azioni-cerchi input[type="checkbox"]:checked + label {
    opacity: 0.65;
    box-shadow: inset 0 0 4px rgba(0,0,0,0.4);
}

@keyframes lampeggio-avvia {
    0%, 100% { opacity: 1; background-color: #16a34a; box-shadow: 0 4px 14px rgba(22,163,74,0.5); }
    50% { opacity: 0.85; background-color: #f97316; box-shadow: 0 4px 18px rgba(249,115,22,0.6); }
}
.badge-avvia.lampeggia { animation: lampeggio-avvia 1.2s ease-in-out infinite; }

/* === Hero card commessa (premium con MES tokens) === */
.hero-commessa {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #fff;
    border-radius: var(--mes-radius-xl, 16px);
    padding: 28px 32px;
    margin-bottom: 20px;
    box-shadow: var(--mes-shadow-lg, 0 10px 25px rgba(0,0,0,0.12));
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}
.hero-commessa h1 {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 6px 0;
    letter-spacing: -0.5px;
}
.hero-commessa .hero-meta {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    font-size: 14px;
    color: #cbd5e1;
    align-items: center;
}
.hero-commessa .hero-meta strong { color: #fff; font-weight: 600; }
.hero-commessa .hero-qta {
    display: inline-flex; align-items: baseline; gap: 6px;
    background: rgba(255,255,255,0.08);
    padding: 6px 14px; border-radius: 999px;
    font-size: 13px;
    font-weight: 500;
}
.hero-commessa .hero-qta .num {
    font-size: 18px; font-weight: 800; color: #fde68a;
    font-family: 'IBM Plex Mono', 'Menlo', monospace;
    font-variant-numeric: tabular-nums;
}
.hero-tags {
    display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;
}
.hero-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.10);
    border: 1px solid rgba(255,255,255,0.18);
    color: #f1f5f9;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}
.hero-pill b { color: #fde68a; font-weight: 700; }
.hero-pill small { color: #cbd5e1; font-size: 11px; }

/* Tag pills generici (numero commessa, cliente, ecc) */
.mes-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.3px;
    border: 1px solid transparent;
}
.mes-pill-primary { background: rgba(59,130,246,0.12); color: var(--mes-primary, #3b82f6); border-color: rgba(59,130,246,0.25); }
.mes-pill-success { background: rgba(16,185,129,0.12); color: var(--mes-success, #10b981); border-color: rgba(16,185,129,0.25); }
.mes-pill-warning { background: rgba(245,158,11,0.12); color: var(--mes-warning, #f59e0b); border-color: rgba(245,158,11,0.25); }
.mes-pill-danger  { background: rgba(239,68,68,0.12);  color: var(--mes-danger, #ef4444);  border-color: rgba(239,68,68,0.25); }
.mes-pill-info    { background: rgba(6,182,212,0.12);  color: var(--mes-info, #06b6d4);    border-color: rgba(6,182,212,0.25); }

/* Numeri tabular-nums globale per dati di produzione */
.mes-num, .info-box-val .num, .pc-frac, .pc-meta strong, .modern-table .num, .stato-cell {
    font-family: 'IBM Plex Mono', 'Menlo', monospace;
    font-variant-numeric: tabular-nums;
}

/* === Badge gruppi (colori, fustella, cliche) collapsabili === */
.tag-group {
    display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;
}
.tag-pill {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 14px; border-radius: 999px;
    font-size: 13px; font-weight: 600;
    border: 1px solid;
    transition: transform 0.1s ease;
}
.tag-pill:hover { transform: translateY(-1px); }
.tag-pill .tag-icon { font-size: 14px; }
.tag-pill .tag-val { background: rgba(0,0,0,0.08); padding: 2px 10px; border-radius: 12px; font-size: 12px; }
.tag-colori { background: #ecfdf5; border-color: #6ee7b7; color: #065f46; }
.tag-fustella { background: #eff6ff; border-color: #93c5fd; color: #1e40af; }
.tag-cliche { background: #fffbeb; border-color: #fcd34d; color: #92400e; }

/* === Tag stack verticale (uno sotto l'altro, allineati, compatti) === */
.tag-stack { display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }
.tag-row {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 5px 12px;
    border-radius: 8px;
    border: 1px solid;
    font-size: 12px;
    font-weight: 600;
    max-width: 100%;
    flex-wrap: wrap;
}
.tag-row.tag-colori { background: #ecfdf5; border-color: #6ee7b7; color: #065f46; }
.tag-row.tag-fustella { background: #eff6ff; border-color: #93c5fd; color: #1e40af; }
.tag-row.tag-cliche { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
.tag-row .tag-icon { font-size: 16px; text-align: center; }
.tag-row .tag-label { font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: 0.4px; }
.tag-row .tag-val { background: rgba(0,0,0,0.08); padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; }
.tag-row .tag-extra { background: rgba(0,0,0,0.06); padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
.tag-row .tag-desc { font-size: 11px; font-weight: 500; color: rgba(0,0,0,0.65); margin-left: 4px; }

/* === Box info griglia compatta === */
.info-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
    margin-bottom: 14px;
}
@media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; } }
.info-box {
    background: var(--mes-bg-card, #fff);
    border: 1px solid var(--mes-border, #e5e7eb);
    border-radius: var(--mes-radius-lg, 12px);
    padding: 12px 16px;
    border-left: 3px solid var(--mes-primary, #3b82f6);
    box-shadow: var(--mes-shadow-md, 0 4px 6px rgba(0,0,0,0.05));
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.info-box:hover {
    transform: translateY(-2px);
    box-shadow: var(--mes-shadow-lg, 0 10px 25px rgba(0,0,0,0.1));
}
.info-box-label {
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px;
    color: #64748b; margin-bottom: 3px;
}
.info-box-val { font-size: 14px; color: var(--mes-text-primary, #111827); line-height: 1.35; }
.info-box-val.muted { color: var(--mes-text-secondary, #6b7280); font-style: italic; }
.info-box.note-prestampa { border-left-color: #6b7280; }
.info-box.responsabile { border-left-color: #f59e0b; }
.info-box.commento { border-left-color: #10b981; }

/* === Anteprima foglio cliccabile === */
.anteprima-foglio { cursor: zoom-in; transition: transform 0.2s ease, box-shadow 0.2s ease; border-radius: var(--mes-radius-md, 8px); }
.anteprima-foglio:hover { transform: scale(1.02); box-shadow: var(--mes-shadow-lg, 0 10px 25px rgba(0,0,0,0.15)); }

/* === Fustella preview card === */
.card.shadow-sm {
    border-radius: var(--mes-radius-lg, 12px) !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-color: var(--mes-border, #e5e7eb) !important;
}
.card.shadow-sm:hover {
    box-shadow: var(--mes-shadow-lg, 0 10px 25px rgba(0,0,0,0.12)) !important;
    transform: translateY(-2px);
}

/* === Box note Mirko (viola) === */
.note-mirko-box {
    border-radius: var(--mes-radius-lg, 12px) !important;
    background: rgba(139,92,246,0.08) !important;
    border-left: 3px solid var(--mes-external, #7c3aed) !important;
    border-top: 1px solid rgba(139,92,246,0.18);
    border-right: 1px solid rgba(139,92,246,0.18);
    border-bottom: 1px solid rgba(139,92,246,0.18);
}

/* === Section heading minimal === */
.section-h {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #475569;
    margin: 24px 0 10px;
    display: flex; align-items: center; gap: 8px;
}
.section-h::before {
    content: "";
    width: 4px; height: 18px;
    border-radius: 2px;
    background: #0d6efd;
}

/* === Tabella moderna === */
.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--mes-bg-card, #fff);
    border-radius: var(--mes-radius-lg, 12px);
    overflow: hidden;
    box-shadow: var(--mes-shadow-md, 0 4px 6px rgba(0,0,0,0.05));
    font-size: 13px;
    transition: box-shadow 0.2s ease;
}
.modern-table:hover { box-shadow: var(--mes-shadow-lg, 0 10px 25px rgba(0,0,0,0.08)); }
.modern-table thead th {
    background: #0f172a;
    color: #fff;
    padding: 10px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    border: none;
}
.modern-table tbody td {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.modern-table tbody tr:last-child td { border-bottom: none; }
.modern-table tbody tr:hover { background: #f8fafc; }
.modern-table tbody tr.clickable { cursor: pointer; }
.modern-table .stato-cell {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 28px; height: 26px; padding: 0 8px;
    border-radius: 6px;
    font-weight: 700; font-size: 12px;
}
.stato-0 { background: #f1f5f9; color: #475569; }
.stato-1 { background: #dbeafe; color: #1e40af; }
.stato-2 { background: #fef3c7; color: #92400e; }
.stato-3 { background: #d1fae5; color: #065f46; }
.stato-4 { background: #c7d2fe; color: #312e81; }

/* === Card prossime commesse === */
.prossime-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
}
.prossima-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 14px 12px 50px;
    transition: transform 0.1s ease, box-shadow 0.1s ease;
    text-decoration: none;
    color: #0f172a;
    display: block;
    border-left: 3px solid #0d6efd;
    position: relative;
}
.prossima-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    color: #0f172a;
}
.prossima-card .pc-rank {
    position: absolute; left: 10px; top: 12px;
    width: 32px; height: 32px;
    background: #e2e8f0; color: #475569;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 800;
}
.prossima-card.prima {
    border: 2px solid #16a34a;
    background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 80%);
    box-shadow: 0 4px 14px rgba(22,163,74,0.15);
}
.prossima-card.prima .pc-rank {
    background: #16a34a; color: #fff;
    box-shadow: 0 0 0 4px rgba(22,163,74,0.2);
}
.prossima-card.prima::after {
    content: 'PROSSIMA';
    position: absolute; top: 8px; right: 10px;
    background: #16a34a; color: #fff;
    font-size: 9px; font-weight: 800; letter-spacing: 0.5px;
    padding: 2px 8px; border-radius: 10px;
}
.prossima-card .pc-num {
    font-size: 14px; font-weight: 700; color: #0d6efd;
    margin-bottom: 2px;
}
.prossima-card .pc-cliente {
    font-size: 12px; color: #64748b; margin-bottom: 6px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.prossima-card .pc-desc {
    font-size: 12px; color: #475569; margin-bottom: 10px;
    line-height: 1.4;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
}
.prossima-card .pc-meta {
    display: flex; gap: 10px; font-size: 11px; color: #64748b; margin-bottom: 8px;
}
.prossima-card .pc-meta strong { color: #0f172a; }
.prossima-card .pc-prog {
    display: flex; align-items: center; gap: 6px;
}
.prossima-card .pc-bar {
    flex: 1; background: #e2e8f0; border-radius: 4px; height: 6px;
}
.prossima-card .pc-bar-fill { height: 100%; border-radius: 4px; transition: width 0.3s ease; }
.prossima-card .pc-frac { font-size: 10px; font-weight: 700; color: #475569; min-width: 30px; }

/* === Timeline fasi orizzontale === */
.fasi-timeline {
    display: flex; gap: 4px; align-items: center;
    background: #f1f5f9; border-radius: 10px; padding: 10px;
    margin-bottom: 14px; overflow-x: auto;
}
.fasi-timeline-step {
    flex: 1; min-width: 80px;
    text-align: center;
    padding: 8px 6px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    background: #fff;
    border: 2px solid #e2e8f0;
    position: relative;
}
.fasi-timeline-step.done { background: #d1fae5; border-color: #10b981; color: #065f46; }
.fasi-timeline-step.active { background: #fef3c7; border-color: #f59e0b; color: #92400e; box-shadow: 0 0 0 4px rgba(245,158,11,0.2); }
.fasi-timeline-step.todo { background: #fff; color: #64748b; }
.fasi-timeline-step.consegnato { background: #e0e7ff; border-color: #6366f1; color: #3730a3; }

/* Numeri monospace nelle quantità */
.modern-table td strong, .modern-table td .num,
.prossima-card .pc-meta strong, .prossima-card .pc-frac, .prossima-card .pc-num,
.tag-row .tag-val, .info-box-val strong {
    font-family: 'IBM Plex Mono', 'Menlo', monospace;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.2px;
}

/* === Dark mode override === */
@media (prefers-color-scheme: dark) {
    .info-box,
    .modern-table,
    .prossima-card,
    .card.shadow-sm {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #e2e8f0;
    }
    .info-box-val { color: #f1f5f9; }
    .info-box-val.muted { color: #94a3b8; }
    .info-box-label { color: #94a3b8; }
    .modern-table tbody td { border-bottom-color: #334155; color: #e2e8f0; }
    .modern-table tbody tr:hover { background: #0f172a; }
    .prossima-card { color: #e2e8f0; }
    .prossima-card:hover { color: #f1f5f9; }
    .prossima-card .pc-cliente, .prossima-card .pc-desc, .prossima-card .pc-meta { color: #94a3b8; }
    .prossima-card .pc-meta strong, .prossima-card .pc-frac { color: #f1f5f9; }
    .fasi-timeline { background: #0f172a; }
    .fasi-timeline-step { background: #1e293b; border-color: #334155; color: #cbd5e1; }
    .section-h { color: #cbd5e1; }
}
body.dark-mode .info-box,
body.dark-mode .modern-table,
body.dark-mode .prossima-card,
body.dark-mode .card.shadow-sm {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #e2e8f0;
}
body.dark-mode .info-box-val { color: #f1f5f9; }
body.dark-mode .info-box-label { color: #94a3b8; }
</style>

@php
    $operatore = request()->attributes->get('operatore') ?? auth('operatore')->user();
    $repartiOperatore = $operatore?->reparti?->pluck('id')->toArray() ?? [];
    $isSpedizione = $operatore?->reparti?->pluck('nome')->map(fn($n) => strtolower($n))->contains('spedizione');

    $ordineFasi = config('fasi_ordine');
    $getFaseOrdine = function($fase) use ($ordineFasi) {
        $nome = $fase->faseCatalogo->nome ?? '';
        return $ordineFasi[$nome] ?? $ordineFasi[strtolower($nome)] ?? 999;
    };
@endphp

@php
    $qtaProdottaStampe = $ordine->fasi
        ->filter(function($f) {
            $rep = strtolower($f->faseCatalogo->reparto->nome ?? '');
            return $rep === 'stampa offset' && (int)($f->stato ?? 0) >= 2 && $f->qta_prod !== null;
        })
        ->sum('qta_prod');
    if ($qtaProdottaStampe == 0) {
        $qtaProdottaStampe = $ordine->fasi
            ->filter(function($f) {
                $rep = strtolower($f->faseCatalogo->reparto->nome ?? '');
                return $rep === 'digitale' && (int)($f->stato ?? 0) >= 2 && $f->qta_prod !== null;
            })
            ->sum('qta_prod');
    }
@endphp

@php
    $tutteDescOp = $ordini->pluck('descrizione')->filter()->unique()->implode(' | ');
    $cliente = $ordine->cliente_nome ?? '';
    $coloriCalc = \App\Helpers\DescrizioneParser::parseColori($tutteDescOp, $cliente);
    $fustellaCalc = \App\Helpers\DescrizioneParser::parseFustella($tutteDescOp, $cliente, $ordine->note_prestampa ?? '');
    $clicheGruppiOp = $ordini->filter(fn($o) => $o->cliche)->groupBy('cliche_numero');
@endphp

<!-- Hero card commessa -->
<div class="hero-commessa">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div class="flex-grow-1">
            <h1>Commessa {{ $ordine->commessa }}</h1>
            <div class="hero-meta">
                <div><strong>{{ $ordine->cliente_nome ?: '-' }}</strong></div>
                <div>{{ Str::limit($ordine->descrizione, 90) }}</div>
                <div class="hero-qta">Qta <span class="num">{{ number_format($ordine->qta_richiesta, 0, ',', '.') }}</span> {{ $ordine->um }}</div>
                @if($qtaProdottaStampe > 0)
                <div class="hero-qta" style="background:rgba(245,158,11,0.18); border:1px solid rgba(245,158,11,0.4);">
                    Qta Prodotta <span class="num" style="color:#fbbf24;">{{ number_format($qtaProdottaStampe, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($ordini->count() > 1)
            <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">Stampa Etichetta</button>
                <ul class="dropdown-menu">
                    @foreach($ordini as $ord)
                    <li><a class="dropdown-item" href="{{ route('operatore.etichetta', $ord->id) }}">{{ Str::limit($ord->descrizione, 60) }}</a></li>
                    @endforeach
                </ul>
            </div>
            @else
            <a href="{{ route('operatore.etichetta', $ordine->id) }}" class="btn btn-light btn-sm">Stampa Etichetta</a>
            @endif
            <a href="{{ $isSpedizione ? route('spedizione.dashboard') : route('operatore.dashboard') }}" class="btn btn-warning btn-sm fw-bold">← Dashboard</a>
        </div>
    </div>
</div>

@php
    $fasiGestibili = $ordine->fasi->filter(function($f) use ($repartiOperatore) {
        return in_array($f->faseCatalogo->reparto_id ?? null, $repartiOperatore);
    });
    $faseSelezionataId = (int) request('fase');
@endphp

<div class="container mt-3">
    {{-- Info grid: prestampa / operatore / commento --}}
    <div class="info-grid">
        <div class="info-box note-prestampa">
            <div class="info-box-label">📝 Note Prestampa</div>
            <div class="info-box-val {{ $ordine->note_prestampa ? '' : 'muted' }}">{{ $ordine->note_prestampa ?: '—' }}</div>
        </div>
        <div class="info-box responsabile">
            <div class="info-box-label">👤 Operatore Prestampa</div>
            <div class="info-box-val {{ $ordine->responsabile ? '' : 'muted' }}">{{ $ordine->responsabile ?: '—' }}</div>
        </div>
        <div class="info-box commento">
            <div class="info-box-label">💬 Commento Produzione</div>
            <div class="info-box-val {{ $ordine->commento_produzione ? '' : 'muted' }}">{{ $ordine->commento_produzione ?: '—' }}</div>
        </div>
    </div>

    {{-- Note Fustelle (note inserite da Mirko/prestampa sulle singole fasi) --}}
    @php
        $noteFasiOp = $ordini->flatMap(fn($o) => $o->fasi)
            ->filter(function($f) {
                $n = trim((string) ($f->note ?? ''));
                return $n !== '' && $n !== '-';
            })
            ->unique(function($f) {
                $nome = $f->faseCatalogo->nome_display ?? $f->fase ?? '';
                $note = trim((string) ($f->note ?? ''));
                return $nome . '|' . $note;
            })
            ->values();
    @endphp
    @if($noteFasiOp->isNotEmpty())
    <div class="info-box note-mirko-box mb-3">
        <div class="info-box-label" style="color:#7c3aed;">📌 Note Fustelle</div>
        @foreach($noteFasiOp as $nf)
            <div style="font-size:13px; padding:3px 0; {{ !$loop->last ? 'border-bottom:1px solid #ddd6fe;' : '' }}">
                <strong>{{ $nf->faseCatalogo->nome_display ?? $nf->fase }}</strong>: {{ $nf->note }}
            </div>
        @endforeach
    </div>
    @endif

    {{-- Timeline fasi ordine corrente — dedup per nome con badge ×N --}}
    @php
        $statoLabel = [0=>'Caricata',1=>'Pronta',2=>'In corso',3=>'Terminata',4=>'Consegnata',5=>'Esterna'];
        $fasiTimeline = $ordine->fasi
            ->groupBy(fn($f) => $f->faseCatalogo->nome_display ?? $f->fase ?? 'N/A')
            ->map(function($group) {
                $stati = $group->map(fn($f) => is_numeric($f->stato) ? (int)$f->stato : 0);
                return (object)[
                    'nome' => $group->first()->faseCatalogo->nome_display ?? $group->first()->fase ?? 'N/A',
                    'stato_min' => $stati->min(),
                    'count' => $group->count(),
                    'first' => $group->first(),
                ];
            })
            ->sortBy(function($f) use ($getFaseOrdine) { return $getFaseOrdine($f->first); })
            ->values();
    @endphp
    @if($fasiTimeline->count() > 0)
    <div class="fasi-timeline">
        @foreach($fasiTimeline as $ft)
            @php
                $st = $ft->stato_min;
                $cls = $st >= 4 ? 'consegnato' : ($st >= 3 ? 'done' : ($st == 2 ? 'active' : 'todo'));
            @endphp
            <div class="fasi-timeline-step {{ $cls }}" title="Stato: {{ $statoLabel[$st] ?? $st }} · {{ $ft->count }} fase/i">
                {{ $ft->nome }}@if($ft->count > 1) <span style="background:rgba(0,0,0,0.18); padding:1px 6px; border-radius:8px; font-size:10px; margin-left:3px;">×{{ $ft->count }}</span>@endif
            </div>
        @endforeach
    </div>
    @endif

    <!-- Fase selezionata (con pulsanti) + Anteprima affiancata -->
    @foreach($fasiGestibili as $fase)
        @if($fase->id === $faseSelezionataId)
        <div class="row mb-3">
            <div class="{{ (!empty($preview) || !empty($fustella)) ? 'col-md-8' : 'col-12' }}">
                <div class="card border-primary" id="card-fase-{{ $fase->id }}">
                    <div class="card-header bg-primary text-white">
                        <strong>{{ $fase->faseCatalogo->nome_display ?? '-' }}</strong>
                        @php $badgeBg = [0=>'bg-secondary',1=>'bg-info',2=>'bg-warning text-dark',3=>'bg-success',5=>'bg-purple text-white']; @endphp
                        <span class="badge {{ $badgeBg[$fase->stato] ?? 'bg-dark' }} ms-2 fs-5" id="badge-fase-{{ $fase->id }}">{{ $fase->stato }}</span>
                        <span class="ms-2" id="operatori-fase-{{ $fase->id }}">
                            @foreach($fase->operatori as $op)
                                <small class="badge bg-light text-dark">{{ $op->nome }} ({{ $op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-' }})</small>
                            @endforeach
                        </span>
                    </div>
                    <div class="card-body border-bottom py-2">
                        <small class="text-muted">{{ $fase->ordine_descrizione ?? $fase->ordine->descrizione ?? '-' }}</small>
                    </div>
                    <div class="card-body d-flex align-items-start gap-3">
                        <div class="flex-grow-1">
                            {{-- Informazioni generali / per fasi successive --}}
                            <div>
                                <label><strong>Informazioni generali / per fasi successive:</strong></label>
                                @php
                                    $noteFS = $ordine->note_fasi_successive ?? '';
                                    $righeFS = $noteFS ? json_decode($noteFS, true) : [];
                                    if (!is_array($righeFS)) $righeFS = [];
                                @endphp
                                @if(!empty($righeFS))
                                    <div class="mb-2" style="max-height:150px; overflow-y:auto; background:#f8f9fa; border-radius:4px; padding:8px; font-size:13px;">
                                        @foreach($righeFS as $riga)
                                            <div class="mb-1">
                                                <small class="text-muted">{{ $riga['data'] ?? '' }}</small>
                                                <strong>{{ $riga['reparto'] ?? '' }} - {{ $riga['nome'] ?? '' }}:</strong>
                                                {{ $riga['testo'] ?? '' }}
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mb-2 text-muted" style="font-size:13px;">Nessuna nota</div>
                                @endif
                                <div class="d-flex gap-2">
                                    <textarea id="nuova-nota-fs-{{ $fase->id }}" class="form-control form-control-sm" rows="1"
                                              placeholder="Scrivi una nota..."></textarea>
                                    <button type="button" class="btn btn-sm btn-outline-primary" style="white-space:nowrap"
                                            onclick="inviaNotaFS({{ $ordine->id }}, {{ $fase->id }})">Invia</button>
                                </div>
                            </div>

                            {{-- Tag pills colori / fustella / cliché (uno sotto l'altro, allineati) --}}
                            <div class="tag-stack mt-3">
                                @if($coloriCalc)
                                <div class="tag-row tag-colori"><span class="tag-icon">🎨</span><span class="tag-label">Colori</span><span class="tag-val">{{ $coloriCalc }}</span></div>
                                @endif
                                @if($fustellaCalc)
                                <div class="tag-row tag-fustella"
                                     @if(!empty($fustella)) style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#modalFustella" title="Apri PDF fustella" @endif>
                                    <span class="tag-icon">✂️</span><span class="tag-label">Fustella</span><span class="tag-val">{{ $fustellaCalc }}</span>
                                </div>
                                @endif
                                @if($clicheGruppiOp->isNotEmpty())
                                    @foreach($clicheGruppiOp as $numeroCl => $gruppoCl)
                                    @php
                                        $clOp = $gruppoCl->first()->cliche;
                                        $descrCl = $gruppoCl->pluck('descrizione')->filter()->unique()->implode(' | ');
                                    @endphp
                                    <div class="tag-row tag-cliche">
                                        <span class="tag-icon">🏷️</span>
                                        <span class="tag-label">Cliché</span>
                                        <span class="tag-val">{{ $clOp->numero }}</span>
                                        @if($clOp->scatola)<span class="tag-extra">Sc. {{ $clOp->scatola }}</span>@endif
                                        @if($clOp->qta)<span class="tag-extra">Qta {{ $clOp->qta }}</span>@endif
                                        @if($descrCl)<span class="tag-desc">→ {{ $descrCl }}</span>@endif
                                    </div>
                                    @endforeach
                                @endif
                            </div>

                            {{-- Scarti Prinect + Scarti Reali (solo stampa offset) --}}
                            @if(strtolower(optional(optional($fase->faseCatalogo)->reparto)->nome ?? '') === 'stampa offset')
                            <div class="mt-3 p-2" style="background:#f8f9fa; border-radius:6px;">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <div>
                                        <strong style="font-size:15px;">Fogli Buoni Prinect:</strong>
                                        <span class="badge bg-success" style="font-size:14px; padding:6px 12px;">{{ $fase->fogli_buoni ?? 0 }}</span>
                                    </div>
                                    <div>
                                        <strong style="font-size:15px;">Scarti Prinect:</strong>
                                        <span class="badge bg-secondary" style="font-size:14px; padding:6px 12px;">{{ $fase->fogli_scarto ?? 0 }}</span>
                                    </div>
                                    <div>
                                        <strong style="font-size:15px;">Scarti Reali:</strong>
                                        <input type="number" min="0" style="width:100px; padding:4px 8px; font-size:15px; border:1px solid #ced4da; border-radius:4px;"
                                               value="{{ $fase->scarti ?? '' }}"
                                               onchange="salvaScartiCommessa({{ $fase->id }}, this.value)"
                                               onkeydown="if(event.key==='Enter'){this.blur();}">
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @php
                            $inPausa = !is_numeric($fase->stato);
                            $avviaLabel = $inPausa ? 'Riprendi' : ($fase->stato == 2 ? 'Avviato' : 'Avvia');
                            $avviaAction = $inPausa ? "riprendiFase({$fase->id}, this.checked)" : "aggiornaStato({$fase->id}, 'avvia', this.checked)";
                        @endphp
                        <div class="azioni-cerchi" id="azioni-fase-{{ $fase->id }}">
                            {{-- 3 bottoni: Avvia/Avviato/Riprendi + Pausa + Termina --}}
                            <input type="checkbox" id="avvia-{{ $fase->id }}" onchange="{{ $avviaAction }}">
                            <label for="avvia-{{ $fase->id }}" class="badge-avvia{{ $fase->stato == 2 ? ' lampeggia' : '' }}">{{ $avviaLabel }}</label>

                            <input type="checkbox" id="pausa-{{ $fase->id }}" onchange="gestisciPausa({{ $fase->id }}, this.checked)">
                            <label for="pausa-{{ $fase->id }}" class="badge-pausa">Pausa</label>

                            <input type="checkbox" id="termina-{{ $fase->id }}"
                                   data-qta-fase="{{ $ordine->qta_richiesta ?? 0 }}"
                                   data-fogli-buoni="{{ $fase->fogli_buoni ?? 0 }}"
                                   data-fogli-scarto="{{ $fase->fogli_scarto ?? 0 }}"
                                   data-qta-prod="{{ $fase->qta_prod ?? 0 }}"
                                   data-fase-nome="{{ $fase->fase ?? '' }}"
                                   data-reparto="{{ strtolower(optional(optional($fase->faseCatalogo)->reparto)->nome ?? '') }}"
                                   data-cod-carta="{{ $ordine->cod_carta ?? '' }}"
                                   onchange="aggiornaStato({{ $fase->id }}, 'termina', this.checked)">
                            <label for="termina-{{ $fase->id }}" class="badge-termina">Termina</label>
                        </div>
                    </div>
                </div>
            </div>
            @if(!empty($preview) || !empty($fustella))
            <div class="col-md-4">
                <div class="d-flex flex-column gap-3 h-100">
                    @if(!empty($preview))
                    <div class="card p-3 text-center shadow-sm">
                        <div class="fw-bold mb-2" style="font-size:13px;">📄 Anteprima foglio di stampa <small class="text-muted">(click per ingrandire)</small></div>
                        <img id="anteprimaThumb" class="anteprima-foglio" src="data:{{ $preview['mimeType'] }};base64,{{ $preview['data'] }}"
                             alt="Preview" style="max-width:100%; max-height:200px; border-radius:8px;"
                             data-bs-toggle="modal" data-bs-target="#modalAnteprima">
                    </div>
                    @endif
                    @if(!empty($fustella))
                    <div class="card p-3 text-center shadow-sm">
                        <div class="fw-bold mb-1" style="font-size:13px;">📐 Fustella <strong>{{ $fustella['codice'] }}</strong></div>
                        @if(!empty($fustella['descrizione']))
                            <div class="mb-2" style="font-size:11px; color:#666;">{{ Str::limit($fustella['descrizione'], 50) }}</div>
                        @endif
                        <div id="fustellaCanvasWrap" style="position:relative; width:100%; min-height:180px; overflow:hidden; border-radius:8px; background:#fff; cursor:zoom-in;"
                             data-bs-toggle="modal" data-bs-target="#modalFustella">
                            <canvas id="fustellaCanvas" style="width:100%; display:block; background:#fff;"></canvas>
                        </div>
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
                        <script>
                        if (window.pdfjsLib) {
                            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                        }
                        (async function(){
                            try {
                                const pdf = await pdfjsLib.getDocument(@json($fustella['url'])).promise;
                                const page = await pdf.getPage(1);
                                const canvas = document.getElementById('fustellaCanvas');
                                const wrap = document.getElementById('fustellaCanvasWrap');
                                const w = wrap.clientWidth - 4;
                                const v1 = page.getViewport({scale: 1});
                                const scale = w / v1.width;
                                const v = page.getViewport({scale});
                                canvas.width = v.width;
                                canvas.height = v.height;
                                const ctx = canvas.getContext('2d');
                                ctx.fillStyle = '#fff';
                                ctx.fillRect(0, 0, canvas.width, canvas.height);
                                await page.render({canvasContext: ctx, viewport: v}).promise;
                            } catch(e) { console.warn('PDF fustella render fail:', e); }
                        })();
                        </script>
                        <button class="btn btn-sm btn-outline-primary mt-2"
                                data-bs-toggle="modal" data-bs-target="#modalFustella">Ingrandisci</button>
                    </div>
                    @endif
                </div>
            </div>
            @if(!empty($preview))
            <div class="modal fade" id="modalAnteprima" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content" style="background:#0f172a;">
                        <div class="modal-header border-0">
                            <h5 class="modal-title text-white">Anteprima foglio di stampa</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center p-2">
                            <img src="data:{{ $preview['mimeType'] }};base64,{{ $preview['data'] }}" alt="Preview full" style="max-width:100%; max-height:80vh; border-radius:8px;">
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @if(!empty($fustella))
            <div class="modal fade" id="modalFustella" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content" style="background:#0f172a;">
                        <div class="modal-header border-0">
                            <h5 class="modal-title text-white">Fustella {{ $fustella['codice'] ?? '-' }}
                                @if(!empty($fustella['dimensioni']))
                                    <span class="ms-2 badge bg-info">{{ $fustella['dimensioni'] }}</span>
                                @endif
                            </h5>
                            <a href="{{ $fustella['url'] ?? '#' }}" target="_blank" class="btn btn-sm btn-light me-2">Apri PDF</a>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-2">
                            <iframe src="{{ $fustella['url'] ?? '' }}" style="width:100%; height:80vh; border:0; border-radius:8px; background:#fff;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @endif
        </div>
        @endif
    @endforeach

    <!-- Altre fasi del tuo reparto (sola lettura) -->
    @php
        $altreFasiMieNonSelezionate = $fasiGestibili->filter(fn($f) => $f->id !== $faseSelezionataId)->sortBy($getFaseOrdine)->values();
    @endphp
    @if($altreFasiMieNonSelezionate->isNotEmpty())
    <div class="section-h">Altre fasi del tuo reparto</div>
    <table class="modern-table">
        <thead>
            <tr>
                <th>Fase</th>
                <th>Descrizione</th>
                <th style="text-align:center;">Stato</th>
                <th>Operatori</th>
                <th style="text-align:center;">Qta Prodotta</th>
                <th>Timeout</th>
            </tr>
        </thead>
        <tbody>
            @foreach($altreFasiMieNonSelezionate as $fase)
            <tr class="clickable" onclick="window.location='{{ route('commesse.show', $ordine->commessa) }}?fase={{ $fase->id }}'">
                <td><a href="{{ route('commesse.show', $ordine->commessa) }}?fase={{ $fase->id }}" style="color:#0d6efd; text-decoration:none; font-weight:600;">{{ $fase->faseCatalogo->nome_display ?? '-' }}</a></td>
                <td><small style="color:#64748b;">{{ Str::limit($fase->ordine_descrizione ?? $fase->ordine->descrizione ?? '-', 60) }}</small></td>
                <td style="text-align:center;"><span class="stato-cell stato-{{ is_numeric($fase->stato) ? (int)$fase->stato : 0 }}">{{ $fase->stato }}</span></td>
                <td>
                    @foreach($fase->operatori as $op)
                        <small>{{ $op->nome }} ({{ $op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m H:i') : '-' }})</small><br>
                    @endforeach
                </td>
                <td style="text-align:center; font-weight:600;">{{ $fase->qta_prod ?? '-' }}</td>
                <td><small>{{ $fase->timeout ?? '-' }}</small></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Fasi di altri reparti (sola lettura) -->
    @php
        $altreFasi = $ordine->fasi->filter(function($f) use ($repartiOperatore) {
            return !in_array($f->faseCatalogo->reparto_id ?? null, $repartiOperatore);
        })->sortBy($getFaseOrdine)->values();
    @endphp
    @if($altreFasi->count() > 0)
    @php
        $altreFasiGrouped = $altreFasi
            ->groupBy(fn($f) => $f->faseCatalogo->nome_display ?? $f->fase ?? 'N/A')
            ->map(function($g) {
                return (object)[
                    'nome' => $g->first()->faseCatalogo->nome_display ?? $g->first()->fase ?? 'N/A',
                    'stato_min' => $g->map(fn($f) => is_numeric($f->stato) ? (int)$f->stato : 0)->min(),
                    'count' => $g->count(),
                    'qta_prod_sum' => $g->sum(fn($f) => (int)($f->qta_prod ?? 0)),
                    'operatori' => $g->flatMap(fn($f) => $f->operatori),
                ];
            })->values();
    @endphp
    <div class="section-h">Fasi altri reparti</div>
    <table class="modern-table">
        <thead>
            <tr>
                <th>Fase</th>
                <th style="text-align:center;">Stato</th>
                <th style="text-align:center;">Qta Prodotta</th>
                <th>Operatori</th>
            </tr>
        </thead>
        <tbody>
            @foreach($altreFasiGrouped as $fg)
            <tr>
                <td style="font-weight:600;">{{ $fg->nome }}</td>
                <td style="text-align:center;"><span class="stato-cell stato-{{ $fg->stato_min }}">{{ $fg->stato_min }}</span></td>
                <td style="text-align:center;">
                    @if($fg->qta_prod_sum > 0)
                        <strong style="color:#0f172a;">{{ number_format($fg->qta_prod_sum, 0, ',', '.') }}</strong>
                    @else
                        <small style="color:#94a3b8;">-</small>
                    @endif
                </td>
                <td>
                    @foreach($fg->operatori->unique('id') as $op)
                        <small>{{ $op->nome }}@if($op->pivot && $op->pivot->data_inizio) ({{ \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m H:i') }})@endif</small>@if(!$loop->last), @endif
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Prossime commesse (card grid) -->
    <div class="section-h">Prossime commesse <small style="font-weight:400; color:#64748b; font-size:12px;">— ordine di lavorazione consigliato</small></div>
    <div class="prossime-grid">
        @foreach($prossime as $c)
            @php
                $tot = $c->fasi_count ?? 0;
                $term = $c->fasi_terminate_count ?? 0;
                $pct = $tot > 0 ? round($term / $tot * 100) : 0;
                $color = $pct >= 100 ? '#16a34a' : ($pct >= 50 ? '#f59e0b' : '#dc2626');
                $isPrima = $loop->iteration === 1;
            @endphp
            <a href="{{ route('commesse.show', $c->commessa) }}" class="prossima-card {{ $isPrima ? 'prima' : '' }}" style="border-left-color:{{ $color }};">
                <div class="pc-rank">{{ $loop->iteration }}</div>
                <div class="pc-num">{{ $c->commessa }}</div>
                <div class="pc-cliente">{{ $c->cliente_nome ?? '-' }}</div>
                <div class="pc-desc">{{ Str::limit($c->descrizione ?? '-', 90) }}</div>
                <div class="pc-meta">
                    <div>Qta <strong>{{ number_format($c->qta_richiesta ?? 0, 0, ',', '.') }}</strong></div>
                    <div>Consegna <strong>{{ $c->data_prevista_consegna ? \Carbon\Carbon::parse($c->data_prevista_consegna)->format('d/m/Y') : '-' }}</strong></div>
                </div>
                <div class="pc-prog">
                    <div class="pc-bar"><div class="pc-bar-fill" style="width:{{ $pct }}%; background:{{ $color }};"></div></div>
                    <div class="pc-frac">{{ $term }}/{{ $tot }}</div>
                </div>
            </a>
        @endforeach
    </div>
</div>

<!-- Modal Termina Fase (con prelievo carta integrato per stampa offset/digitale/tagliacarte) -->
<div class="modal fade" id="modalTermina" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Termina Fase</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="terminaFaseId">
                <input type="hidden" id="terminaConsumaCarta" value="0">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Qta prodotta <span class="text-danger">*</span></label>
                        <input type="number" id="terminaQtaProdotta" class="form-control" min="0" required oninput="ricalcolaQtaPrelievo()">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Scarti</label>
                        <input type="number" id="terminaScarti" class="form-control" min="0" value="0" oninput="ricalcolaQtaPrelievo()">
                    </div>
                </div>

                <div class="mb-3" id="terminaTiroWrap" style="display:none;">
                    <label class="form-label fw-bold">Tiro (cm foil) <span class="text-danger">*</span></label>
                    <input type="number" id="terminaTiro" class="form-control" min="1">
                </div>

                {{-- Sezione PRELIEVO CARTA (visibile solo per reparti che consumano carta) --}}
                <div id="terminaPrelievoWrap" style="display:none; border-top:2px dashed #dee2e6; padding-top:14px; margin-top:8px;">
                    <h6 class="fw-bold mb-3" style="color:#0d6efd;">📦 Prelievo carta dal magazzino</h6>

                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">Quantità totale prelievo (prodotta + scarti)</label>
                        <input type="number" id="terminaQtaPrelievo" class="form-control fw-bold" min="0">
                        <small class="text-muted">Auto-calcolata. Modificabile se diverso dalla carta usata realmente.</small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">Articolo magazzino (scrivi o cerca)</label>
                        <input type="text" id="terminaArticoloInput" class="form-control" placeholder="Codice o descrizione carta..." oninput="cercaArticoloTermina(this.value)">
                        <input type="hidden" id="terminaArticoloId">
                        <input type="hidden" id="terminaArticoloLibero">
                        <div id="terminaArticoloSuggest" style="max-height:160px; overflow-y:auto; border:1px solid #eee; border-radius:4px; margin-top:4px;"></div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">Lotto (opzionale)</label>
                        <input type="text" id="terminaLotto" class="form-control" placeholder="es. L2026-04-01">
                    </div>

                    <div id="terminaSaltaInfo" class="form-text" style="font-size:11px;">
                        Lasciando l'articolo vuoto la fase verrà chiusa <strong>senza scaricare il magazzino</strong>.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-danger fw-bold" onclick="confermaTermina()">Conferma e Termina</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pausa -->
<div class="modal fade" id="modalPausa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Pausa Fase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pausaFaseId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Motivo della pausa</label>
                    <select id="pausaMotivoSelect" class="form-select" onchange="toggleAltroPausa()">
                        <option value="">-- Seleziona --</option>
                        <option>Attesa materiale</option>
                        <option>Problema macchina</option>
                        <option>Pranzo</option>
                        <option>Fine turno</option>
                        <option value="__altro__">Altro...</option>
                    </select>
                </div>
                <div class="mb-3" id="pausaAltroWrap" style="display:none;">
                    <label class="form-label fw-bold">Specifica motivo</label>
                    <input type="text" id="pausaAltroInput" class="form-control" placeholder="Scrivi il motivo...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-warning fw-bold" onclick="confermaPausa()">Conferma Pausa</button>
            </div>
        </div>
    </div>
</div>

<script>
const badgeBgMap = {0:'bg-secondary',1:'bg-info',2:'bg-warning text-dark',3:'bg-success',5:'bg-purple text-white'};

function updateBadge(faseId, stato) {
    const badge = document.getElementById('badge-fase-'+faseId);
    if (!badge) return;
    badge.className = 'badge ms-2 fs-5 ' + (badgeBgMap[stato] || 'bg-dark');
    badge.textContent = stato;
}

function updateButtons(faseId, nuovoStato) {
    const container = document.getElementById('azioni-fase-'+faseId);
    if (!container) return;

    // 3 bottoni: Avvia/Avviato/Riprendi + Pausa + Termina
    var inPausa = (typeof nuovoStato === 'string' && isNaN(nuovoStato));
    var lampeggiaClass = (nuovoStato == 2) ? ' lampeggia' : '';
    var avviaLabel = inPausa ? 'Riprendi' : (nuovoStato == 2 ? 'Avviato' : 'Avvia');
    var avviaAction = inPausa ? 'riprendiFase('+faseId+', this.checked)' : 'aggiornaStato('+faseId+', \'avvia\', this.checked)';

    let html =
        '<input type="checkbox" id="avvia-'+faseId+'" onchange="'+avviaAction+'">' +
        '<label for="avvia-'+faseId+'" class="badge-avvia'+lampeggiaClass+'">'+avviaLabel+'</label>' +
        '<input type="checkbox" id="pausa-'+faseId+'" onchange="gestisciPausa('+faseId+', this.checked)">' +
        '<label for="pausa-'+faseId+'" class="badge-pausa">Pausa</label>' +
        '<input type="checkbox" id="termina-'+faseId+'" onchange="aggiornaStato('+faseId+', \'termina\', this.checked)">' +
        '<label for="termina-'+faseId+'" class="badge-termina">Termina</label>';

    container.innerHTML = html;
}

function updateOperatori(faseId, operatori) {
    const container = document.getElementById('operatori-fase-'+faseId);
    if (!container || !operatori) return;
    container.innerHTML = operatori.map(function(op) {
        return '<small class="badge bg-light text-dark">' + op.nome + ' (' + op.data_inizio + ')</small>';
    }).join(' ');
}

function salvaScartiCommessa(faseId, valore) {
    fetch('{{ route("produzione.aggiornaCampo") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'X-Op-Token': new URLSearchParams(window.location.search).get('op_token') || '',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId, campo: 'scarti', valore: valore })
    }).then(function(r) {
        if (r.ok) {
            var input = document.querySelector('input[onchange*="salvaScartiCommessa(' + faseId + '"]');
            if (input) { input.style.borderColor = '#28a745'; setTimeout(function() { input.style.borderColor = '#ced4da'; }, 1500); }
        } else { MES.toast('Errore salvataggio','danger'); }
    }).catch(function() { MES.toast('Errore di connessione','danger'); });
}

function aggiornaStato(faseId, azione, checked){
    if(!checked) return;
    if(azione === 'termina'){
        apriModalTermina(faseId);
        return;
    }

    let route = '{{ route("produzione.avvia") }}';

    fetch(route, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, 2);
            updateButtons(faseId, 2);
            if(data.operatori) updateOperatori(faseId, data.operatori);
        } else {
            MES.toast('Errore: ' + (data.messaggio || 'operazione fallita'),'danger');
        }
    })
    .catch(err=>console.error('Errore:', err));
}

function apriModalTermina(faseId) {
    var cb = document.getElementById('termina-'+faseId);
    var qtaFase = cb ? cb.getAttribute('data-qta-fase') : 0;
    var fogliBuoni = parseInt(cb ? cb.getAttribute('data-fogli-buoni') : 0) || 0;
    var fogliScarto = parseInt(cb ? cb.getAttribute('data-fogli-scarto') : 0) || 0;
    var qtaProd = parseInt(cb ? cb.getAttribute('data-qta-prod') : 0) || 0;
    var faseNome = (cb ? cb.getAttribute('data-fase-nome') || '' : '').toUpperCase();
    var reparto = (cb ? cb.getAttribute('data-reparto') || '' : '').toLowerCase();
    var codCarta = cb ? cb.getAttribute('data-cod-carta') || '' : '';

    document.getElementById('terminaFaseId').value = faseId;

    var prefillQta = fogliBuoni > 0 ? fogliBuoni : (qtaProd > 0 ? qtaProd : '');
    document.getElementById('terminaQtaProdotta').value = prefillQta;
    document.getElementById('terminaScarti').value = fogliScarto > 0 ? fogliScarto : 0;

    // Tiro visibile solo per stampa a caldo
    var caldoFasi = ['STAMPACALDOJOH', 'STAMPACALDOJOHEST', 'STAMPALAMINAORO'];
    var isCaldo = caldoFasi.indexOf(faseNome) !== -1;
    document.getElementById('terminaTiroWrap').style.display = isCaldo ? '' : 'none';
    document.getElementById('terminaTiro').value = '';

    // Sezione PRELIEVO CARTA: visibile solo per reparti che consumano carta
    var consumaCarta = ['stampa offset', 'digitale', 'tagliacarte'].indexOf(reparto) !== -1;
    document.getElementById('terminaConsumaCarta').value = consumaCarta ? '1' : '0';
    document.getElementById('terminaPrelievoWrap').style.display = consumaCarta ? '' : 'none';
    if (consumaCarta) {
        document.getElementById('terminaArticoloId').value = '';
        document.getElementById('terminaArticoloLibero').value = '';
        document.getElementById('terminaArticoloInput').value = codCarta;
        document.getElementById('terminaLotto').value = '';
        document.getElementById('terminaArticoloSuggest').innerHTML = '';
        ricalcolaQtaPrelievo();
        if (codCarta && codCarta.length >= 2) cercaArticoloTermina(codCarta);
    }

    new bootstrap.Modal(document.getElementById('modalTermina')).show();
}

function ricalcolaQtaPrelievo() {
    var qtaP = parseInt(document.getElementById('terminaQtaProdotta').value) || 0;
    var scarti = parseInt(document.getElementById('terminaScarti').value) || 0;
    var el = document.getElementById('terminaQtaPrelievo');
    if (el) el.value = qtaP + scarti;
}

var _terminaSearchTimer = null;
function cercaArticoloTermina(q) {
    clearTimeout(_terminaSearchTimer);
    var box = document.getElementById('terminaArticoloSuggest');
    if (!q || q.length < 2) { box.innerHTML = ''; return; }
    _terminaSearchTimer = setTimeout(function() {
        fetch('/produzione/cerca-articolo?q=' + encodeURIComponent(q), {
            headers: { 'X-Op-Token': window.opToken ? window.opToken() : '' }
        })
        .then(function(r){return r.json();})
        .then(function(items){
            var html = '';
            items.forEach(function(a){
                var safeCod = String(a.codice).replace(/"/g,'&quot;');
                html += '<div class="termina-art-item" data-id="'+a.id+'" data-cod="'+safeCod+'" '
                     + 'style="padding:6px 10px; border-bottom:1px solid #eee; cursor:pointer; font-size:13px;">'
                     + '<b>'+a.codice+'</b> — '+(a.descrizione||'')+' <small style="color:#666;">(giac '+a.giacenza+' '+a.um+')</small></div>';
            });
            box.innerHTML = html || '<div style="padding:6px; color:#999; font-size:12px;">Nessun articolo. Lascia campo libero o vuoto per saltare scarico.</div>';
            box.querySelectorAll('.termina-art-item').forEach(function(el){
                el.addEventListener('click', function(){
                    document.getElementById('terminaArticoloId').value = el.dataset.id;
                    document.getElementById('terminaArticoloLibero').value = '';
                    document.getElementById('terminaArticoloInput').value = el.dataset.cod;
                    box.innerHTML = '';
                });
            });
        });
    }, 250);
}

function confermaTermina() {
    var faseId = document.getElementById('terminaFaseId').value;
    var qtaProdotta = document.getElementById('terminaQtaProdotta').value;
    var scarti = document.getElementById('terminaScarti').value;
    var tiroWrap = document.getElementById('terminaTiroWrap');
    var isCaldo = tiroWrap.style.display !== 'none';
    var tiro = document.getElementById('terminaTiro').value;

    if (qtaProdotta === '' || parseInt(qtaProdotta) <= 0) {
        MES.toast('Inserire la quantità prodotta (>0)','warning');
        return;
    }
    if (isCaldo && (tiro === '' || parseInt(tiro) <= 0)) {
        MES.toast('Inserire il tiro (cm foil consumato)','warning');
        document.getElementById('terminaTiro').focus();
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById('modalTermina')).hide();

    var payload = {fase_id: faseId, qta_prodotta: parseInt(qtaProdotta), scarti: parseInt(scarti) || 0};
    if (isCaldo) payload.tiro = parseInt(tiro);

    fetch('{{ route("produzione.termina") }}', {
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, 3);
            updateButtons(faseId, 3);
            updateOperatori(faseId, []);
            // Se reparto consuma carta E modal aveva sezione prelievo → invia scarico inline
            if (data.richiedi_scarico && document.getElementById('terminaConsumaCarta').value === '1') {
                inviaScaricoInline(faseId);
            }
        } else {
            MES.toast('Errore: ' + (data.messaggio || 'operazione fallita'),'danger');
            document.getElementById('termina-'+faseId).checked = false;
        }
    })
    .catch(err=>{
        console.error('Errore:', err);
        document.getElementById('termina-'+faseId).checked = false;
    });
}

function inviaScaricoInline(faseId) {
    var qtaTotale = parseInt(document.getElementById('terminaQtaPrelievo').value) || 0;
    var articoloId = document.getElementById('terminaArticoloId').value || null;
    var articoloLibero = document.getElementById('terminaArticoloInput').value.trim() || null;
    var lotto = document.getElementById('terminaLotto').value || null;

    // Se nessun articolo selezionato/libero E qta=0 → salta scarico
    if (!articoloId && !articoloLibero) {
        // Salta scarico: chiude solo la fase
        fetch('{{ route("produzione.confermaScaricoFase") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ fase_id: parseInt(faseId), salta: true })
        })
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.success) MES.toast('Fase chiusa senza scarico carta','info');
        });
        return;
    }

    var payload = {
        fase_id: parseInt(faseId),
        articolo_id: articoloId,
        articolo_libero: articoloId ? null : articoloLibero,
        quantita_totale: qtaTotale,
        lotto: lotto,
    };

    fetch('{{ route("produzione.confermaScaricoFase") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function(r){return r.json();})
    .then(function(d){
        if (d.success) {
            MES.toast('Carta scaricata: ' + qtaTotale + ' fg', 'success');
        } else {
            MES.toast('Errore scarico: ' + (d.messaggio || ''), 'danger');
        }
    })
    .catch(function(){ MES.toast('Errore scarico (rete)','danger'); });
}

// Reset checkbox when modal is dismissed without confirming
document.getElementById('modalTermina').addEventListener('hidden.bs.modal', function() {
    var faseId = document.getElementById('terminaFaseId').value;
    var cb = document.getElementById('termina-'+faseId);
    if (cb) cb.checked = false;
});

function gestisciPausa(faseId, checked){
    if(!checked) return;
    document.getElementById('pausaFaseId').value = faseId;
    document.getElementById('pausaMotivoSelect').value = '';
    document.getElementById('pausaAltroInput').value = '';
    document.getElementById('pausaAltroWrap').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalPausa')).show();
}

document.getElementById('modalPausa').addEventListener('hidden.bs.modal', function() {
    var faseId = document.getElementById('pausaFaseId').value;
    var cb = document.getElementById('pausa-'+faseId);
    if (cb) cb.checked = false;
});

function toggleAltroPausa() {
    document.getElementById('pausaAltroWrap').style.display =
        document.getElementById('pausaMotivoSelect').value === '__altro__' ? '' : 'none';
}

function confermaPausa() {
    var sel = document.getElementById('pausaMotivoSelect').value;
    var motivo = sel === '__altro__' ? (document.getElementById('pausaAltroInput').value.trim() || 'Altro') : sel;
    if (!motivo) { MES.toast('Seleziona un motivo','warning'); return; }
    var faseId = document.getElementById('pausaFaseId').value;
    bootstrap.Modal.getInstance(document.getElementById('modalPausa')).hide();

    fetch('{{ route("produzione.pausa") }}',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId, motivo:motivo})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, data.nuovo_stato);
            updateButtons(faseId, data.nuovo_stato);
        } else {
            MES.toast('Errore: ' + (data.messaggio || 'operazione fallita'),'danger');
        }
    })
    .catch(err=>console.error('Errore:', err));
}

function riprendiFase(faseId, checked){
    if(!checked) return;

    fetch('{{ route("produzione.riprendi") }}',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, 2);
            updateButtons(faseId, 2);
            // Aggiorna lista operatori (nuovo operatore potrebbe essere stato aggiunto)
            if(data.operatori){
                var opCell = document.getElementById('operatori-'+faseId);
                if(opCell){
                    opCell.innerHTML = data.operatori.map(function(o){ return o.nome+' ('+o.data_inizio+')'; }).join('<br>');
                }
            }
        } else {
            MES.toast('Errore: ' + (data.messaggio || 'operazione fallita'),'danger');
            document.getElementById('riprendi-'+faseId).checked = false;
        }
    })
    .catch(err=>console.error('Errore:', err));
}

function aggiornaCampo(faseId, campo, valore){
    fetch('{{ route("produzione.aggiornaCampo") }}',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId, campo:campo, valore:valore})
    })
    .then(res=>res.json())
    .then(data=>{
        if(!data.success) MES.toast('Errore salvataggio: '+data.messaggio,'danger');
    })
    .catch(err=>console.error('Errore:', err));
}

function inviaNotaFS(ordineId, faseId) {
    var textarea = document.getElementById('nuova-nota-fs-'+faseId);
    var testo = textarea.value.trim();
    if (!testo) { MES.toast('Scrivi una nota prima di inviare','warning'); return; }

    @php
        $opNome = $operatore ? ($operatore->nome . ' ' . ($operatore->cognome ?? '')) : 'Operatore';
        $opReparto = $operatore?->reparti?->pluck('nome')->first() ?? 'N/D';
    @endphp

    // Leggi note esistenti, aggiungi la nuova, salva
    var noteEsistenti = @json($righeFS ?? []);
    noteEsistenti.push({
        data: new Date().toLocaleString('it-IT'),
        reparto: @json($opReparto),
        nome: @json(trim($opNome)),
        testo: testo
    });

    fetch('{{ route("produzione.aggiornaOrdineCampo") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json'},
        body: JSON.stringify({ordine_id: ordineId, campo: 'note_fasi_successive', valore: JSON.stringify(noteEsistenti)})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            MES.toast('Errore: ' + (data.messaggio || JSON.stringify(data.errors)),'danger');
        }
    })
    .catch(err => console.error('Errore:', err));
}

// ====== MODAL SCARICO CARTA POST-TERMINA ======
function apriModalScaricoCarta(payload) {
    var m = document.getElementById('modalScaricoCarta');
    if (!m) {
        console.error('Modal scarico carta non trovato');
        return;
    }
    document.getElementById('scfFaseId').value = payload.fase_id;
    document.getElementById('scfCommessaDisp').textContent = payload.commessa || '-';
    document.getElementById('scfFaseDisp').textContent = payload.fase_nome || '-';
    document.getElementById('scfQtaProd').textContent = (payload.qta_prod || 0).toLocaleString('it-IT');
    document.getElementById('scfScarti').textContent = (payload.scarti || 0).toLocaleString('it-IT');
    document.getElementById('scfQtaTotale').value = payload.qta_totale || 0;
    document.getElementById('scfCodCartaDisp').textContent = payload.cod_carta || '-';
    document.getElementById('scfDescCartaDisp').textContent = payload.desc_carta || '-';
    document.getElementById('scfArticoloLibero').value = '';
    document.getElementById('scfArticoloId').value = '';
    document.getElementById('scfArticoloInput').value = payload.cod_carta || '';
    document.getElementById('scfLotto').value = '';
    document.getElementById('scfSuggest').innerHTML = '';
    // Auto-search default cod_carta
    if (payload.cod_carta) cercaArticoloScf(payload.cod_carta);
    new bootstrap.Modal(m).show();
}

var _scfTimer = null;
function cercaArticoloScf(q) {
    clearTimeout(_scfTimer);
    var box = document.getElementById('scfSuggest');
    if (!q || q.length < 2) { box.innerHTML=''; return; }
    _scfTimer = setTimeout(function() {
        fetch('/produzione/cerca-articolo?q=' + encodeURIComponent(q), {
            headers: { 'X-Op-Token': window.opToken ? window.opToken() : '' }
        })
        .then(function(r){return r.json();})
        .then(function(items){
            var html = '';
            items.forEach(function(a){
                html += '<div class="scf-item" data-id="'+a.id+'" data-cod="'+a.codice.replace(/"/g,'&quot;')+'" '
                     + 'style="padding:6px 10px; border-bottom:1px solid #eee; cursor:pointer; font-size:13px;">'
                     + '<b>'+a.codice+'</b> — '+(a.descrizione||'')+' <small style="color:#666;">(giac '+a.giacenza+' '+a.um+')</small></div>';
            });
            box.innerHTML = html || '<div style="padding:6px; color:#999; font-size:12px;">Nessun articolo. Userai campo libero.</div>';
            box.querySelectorAll('.scf-item').forEach(function(el){
                el.addEventListener('click', function(){
                    document.getElementById('scfArticoloId').value = el.dataset.id;
                    document.getElementById('scfArticoloInput').value = el.dataset.cod;
                    document.getElementById('scfArticoloLibero').value = '';
                    box.innerHTML = '';
                });
            });
        });
    }, 250);
}

function confermaScaricoFaseSubmit(salta) {
    var faseId = document.getElementById('scfFaseId').value;
    var payload = {
        fase_id: parseInt(faseId),
        salta: !!salta,
        articolo_id: document.getElementById('scfArticoloId').value || null,
        articolo_libero: document.getElementById('scfArticoloLibero').value || null,
        quantita_totale: parseInt(document.getElementById('scfQtaTotale').value) || 0,
        lotto: document.getElementById('scfLotto').value || null,
    };

    fetch('{{ route("produzione.confermaScaricoFase") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function(r){return r.json();})
    .then(function(d){
        if (d.success) {
            MES.toast(salta ? 'Scarico saltato' : 'Scarico carta confermato ('+d.qta+' fg)', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalScaricoCarta')).hide();
        } else {
            MES.toast('Errore: ' + (d.messaggio || 'operazione fallita'), 'danger');
        }
    })
    .catch(function(){ MES.toast('Errore di connessione','danger'); });
}

</script>

{{-- Modal conferma scarico carta (post-termina fase) --}}
<div class="modal fade" id="modalScaricoCarta" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0d6efd; color:#fff;">
                <h5 class="modal-title">📦 Conferma Prelievo Carta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="scfFaseId">
                <input type="hidden" id="scfArticoloId">

                <div class="row mb-2 small text-muted">
                    <div class="col-6">Commessa: <strong id="scfCommessaDisp" class="text-dark"></strong></div>
                    <div class="col-6">Fase: <strong id="scfFaseDisp" class="text-dark"></strong></div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <small class="text-muted">Qta prodotta</small>
                        <div style="font-size:20px; font-weight:700; font-family:monospace;" id="scfQtaProd">0</div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Scarti</small>
                        <div style="font-size:20px; font-weight:700; font-family:monospace;" id="scfScarti">0</div>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted mb-1">Quantità totale prelievo (modificabile)</label>
                        <input type="number" id="scfQtaTotale" class="form-control fw-bold" min="0">
                    </div>
                </div>

                <div class="alert alert-light border" style="font-size:12px;">
                    Codice carta ordine: <strong id="scfCodCartaDisp">-</strong> · Descrizione: <strong id="scfDescCartaDisp">-</strong>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold">Articolo magazzino (cerca o usa quello dell'ordine)</label>
                    <input type="text" id="scfArticoloInput" class="form-control"
                           placeholder="Codice o descrizione carta..."
                           oninput="cercaArticoloScf(this.value)">
                    <div id="scfSuggest" style="max-height:200px; overflow-y:auto; border:1px solid #eee; border-radius:4px; margin-top:4px;"></div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-7">
                        <label class="small fw-bold">…oppure descrizione libera (no scarico magazzino, solo log)</label>
                        <input type="text" id="scfArticoloLibero" class="form-control" placeholder="Es. Carta extra non in lista">
                    </div>
                    <div class="col-md-5">
                        <label class="small fw-bold">Lotto (opzionale)</label>
                        <input type="text" id="scfLotto" class="form-control" placeholder="es. L2026-04-01">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="confermaScaricoFaseSubmit(true)">↷ Salta scarico</button>
                <button type="button" class="btn btn-primary" onclick="confermaScaricoFaseSubmit(false)">✓ Conferma e Preleva</button>
            </div>
        </div>
    </div>
</div>
@endsection
