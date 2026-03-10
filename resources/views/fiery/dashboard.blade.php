@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
<style>
    body { background: #f0f2f5; }
    .fiery-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 24px; padding: 16px 0;
    }
    .fiery-header .machine-name { font-size: 22px; font-weight: 700; color: #1a1a2e; letter-spacing: -0.5px; }
    .fiery-header .machine-name small { font-size: 13px; font-weight: 400; color: #6c757d; margin-left: 12px; }
    .fiery-header .nav-links a {
        color: #495057; text-decoration: none; font-size: 13px;
        padding: 6px 14px; border: 1px solid #dee2e6; border-radius: 6px;
        margin-left: 8px; transition: all 0.2s;
    }
    .fiery-header .nav-links a:hover { background: #e9ecef; border-color: #adb5bd; color: #212529; }

    .fc {
        background: #fff; border-radius: 14px; border: 1px solid #dee2e6;
        padding: 20px 24px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .fc-label {
        font-size: 11px; font-weight: 600; text-transform: uppercase;
        color: #6c757d; letter-spacing: 1px; margin-bottom: 12px;
    }

    /* Status */
    .status-pill {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 10px 22px; border-radius: 50px; font-size: 16px; font-weight: 700;
    }
    .sp-stampa { background: #d1fae5; color: #059669; }
    .sp-idle { background: #f3f4f6; color: #6b7280; }
    .sp-errore { background: #fee2e2; color: #dc2626; }
    .sp-offline { background: #fee2e2; color: #dc2626; }
    .status-dot { width: 10px; height: 10px; border-radius: 50%; }
    .sd-stampa { background: #059669; box-shadow: 0 0 8px rgba(5,150,105,0.5); animation: glow 2s infinite; }
    .sd-idle { background: #9ca3af; }
    .sd-errore { background: #dc2626; box-shadow: 0 0 8px rgba(220,38,38,0.5); animation: glow 1s infinite; }
    .sd-offline { background: #dc2626; }
    @keyframes glow { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

    .warning-bar {
        background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;
        border-radius: 8px; padding: 8px 16px; font-size: 13px; margin-top: 12px;
    }

    /* Big progress */
    .big-progress-wrap { margin-top: 16px; }
    .big-progress-bar { height: 32px; background: #e5e7eb; border-radius: 16px; overflow: hidden; position: relative; }
    .big-progress-fill {
        height: 100%; border-radius: 16px; background: linear-gradient(90deg, #059669, #10b981);
        transition: width 0.8s ease; position: relative; overflow: hidden;
    }
    .big-progress-fill::after {
        content: ''; position: absolute; top: 0; left: -100%; width: 200%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
        animation: shimmer 3s infinite;
    }
    @keyframes shimmer { 0% { transform: translateX(-50%); } 100% { transform: translateX(50%); } }
    .big-progress-text {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 700; color: #fff; text-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .big-progress-stats { display: flex; justify-content: space-between; margin-top: 10px; font-size: 13px; color: #6b7280; }

    /* Info */
    .info-label { font-size: 11px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-value { font-size: 18px; font-weight: 700; color: #1a1a2e; margin-top: 2px; }
    .info-value-sm { font-size: 14px; font-weight: 600; color: #343a40; }

    /* RIP */
    .rip-active { background: #dbeafe; border: 1px solid #93c5fd; color: #1d4ed8; padding: 10px 16px; border-radius: 8px; font-size: 13px; }
    .rip-idle-box { color: #9ca3af; font-size: 13px; }

    .print-doc-name { font-size: 16px; font-weight: 600; color: #212529; word-break: break-all; line-height: 1.4; }
    .operatore-name { font-size: 20px; font-weight: 700; color: #1a1a2e; }
    .no-job-msg { color: #adb5bd; font-size: 14px; padding: 20px 0; }
    .timestamp-info { font-size: 11px; color: #adb5bd; text-align: right; margin-top: 8px; }
    .offline-screen { text-align: center; padding: 60px 20px; }
    .offline-screen .icon { font-size: 48px; margin-bottom: 16px; }
    .offline-screen h3 { color: #dc2626; font-weight: 700; }
    .offline-screen p { color: #6c757d; font-size: 14px; }

    /* Section titles */
    .section-title {
        font-size: 14px; font-weight: 700; color: #212529; margin-bottom: 16px;
        display: flex; align-items: center; gap: 8px;
    }
    .section-title .count-badge { background: #e9ecef; color: #495057; font-size: 11px; padding: 2px 8px; border-radius: 10px; }

    /* Fase pills */
    .mes-fasi { display: flex; gap: 4px; flex-wrap: wrap; }
    .fase-pill { display: inline-block; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 10px; white-space: nowrap; }
    .fase-s0 { background: #f3f4f6; color: #6b7280; }
    .fase-s1 { background: #dbeafe; color: #1d4ed8; }
    .fase-s2 { background: #fef3c7; color: #92400e; }
    .fase-s3 { background: #d1fae5; color: #059669; }
    .fase-ext { background: #f3e8ff; color: #7c3aed; }

    /* Commessa tag */
    .commessa-tag { display: inline-block; background: #dbeafe; color: #1d4ed8; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }

    /* ===== CODA: card layout ===== */
    .queue-cards { display: flex; flex-direction: column; gap: 12px; }
    .queue-card {
        background: #fafbfc; border: 1px solid #e9ecef; border-radius: 10px;
        padding: 16px 20px; display: grid;
        grid-template-columns: 1fr 200px 120px 100px;
        gap: 12px 20px; align-items: start;
    }
    .queue-card:hover { border-color: #93c5fd; background: #f0f7ff; }
    .qc-main { min-width: 0; }
    .qc-title { font-size: 13px; font-weight: 600; color: #212529; word-break: break-all; margin-bottom: 4px; }
    .qc-commessa { margin-bottom: 6px; }
    .qc-desc { font-size: 12px; color: #6c757d; margin-bottom: 6px; line-height: 1.4; }
    .qc-notes { font-size: 11px; color: #92400e; background: #fef3c7; border-radius: 4px; padding: 4px 8px; margin-bottom: 6px; display: inline-block; }
    .qc-carta .qc-label { font-size: 10px; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .qc-carta .qc-val { font-size: 13px; font-weight: 500; color: #212529; margin-top: 2px; }
    .qc-carta .qc-sub { font-size: 11px; color: #6c757d; }
    .qc-qta { text-align: right; }
    .qc-qta .qc-num { font-size: 18px; font-weight: 700; color: #1a1a2e; }
    .qc-qta .qc-sub { font-size: 11px; color: #6c757d; }
    .qc-meta { text-align: right; }
    .qc-meta .qc-date { font-size: 12px; color: #495057; font-weight: 500; }
    .qc-meta .qc-copies { font-size: 11px; color: #6c757d; margin-top: 2px; }

    /* ===== COMPLETATI: tabella compatta ===== */
    .compact-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .compact-table thead th {
        font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
        color: #6c757d; font-weight: 600; padding: 8px 10px;
        border-bottom: 2px solid #dee2e6; text-align: left; white-space: nowrap;
    }
    .compact-table tbody td {
        font-size: 12px; color: #495057; padding: 8px 10px;
        border-bottom: 1px solid #f1f3f5; vertical-align: middle;
    }
    .compact-table tbody tr:hover { background: #f8f9fa; }
    .compact-table .ct-title { font-weight: 500; color: #212529; max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mini-progress { width: 60px; height: 5px; background: #e5e7eb; border-radius: 3px; overflow: hidden; display: inline-block; vertical-align: middle; }
    .mini-progress .fill { height: 100%; background: #059669; border-radius: 3px; }
    .copies-text { font-size: 11px; color: #6c757d; margin-left: 4px; }

    @media (max-width: 992px) {
        .queue-card { grid-template-columns: 1fr; }
        .qc-qta, .qc-meta { text-align: left; }
    }
</style>

<div class="fiery-header">
    <div>
        <span class="machine-name">Canon imagePRESS V900 <small>Fiery P400</small></span>
    </div>
    <div class="nav-links">
        <a href="{{ route('owner.dashboard') }}">Dashboard</a>
        <a href="{{ route('mes.fiery.contatori') }}">Contatori</a>
        <a href="{{ route('mes.prinect') }}">Prinect XL106</a>
    </div>
</div>

@if($status)
<div class="row">
    {{-- Col sinistra: Stato + Job in stampa --}}
    <div class="col-lg-8">
        <div class="fc">
            <div class="d-flex align-items-center justify-content-between">
                <div id="stato-container">
                    <span class="status-pill sp-{{ $status['stato'] }}">
                        <span class="status-dot sd-{{ $status['stato'] }}"></span>
                        {{ ucfirst($status['stato']) }}
                    </span>
                </div>
                <div>
                    @if(!$status['rip']['idle'] && $status['rip']['documento'])
                    <div class="rip-active" id="rip-container">RIP: {{ $status['rip']['documento'] }}</div>
                    @else
                    <span class="rip-idle-box" id="rip-container">RIP idle</span>
                    @endif
                </div>
                <div class="timestamp-info" id="ultimo-aggiornamento">{{ $status['ultimo_aggiornamento'] }}</div>
            </div>
            @if($status['avviso'])
            <div class="warning-bar" id="avviso-box">{{ $status['avviso'] }}</div>
            @endif
        </div>

        <div class="fc" id="print-card">
            <div class="fc-label">Job in stampa</div>
            <div id="stampa-container">
            @if($status['stampa']['documento'])
                <div class="print-doc-name" id="print-doc">{{ $status['stampa']['documento'] }}</div>
                @if(!empty($status['commessa']))
                <div class="mt-2" id="commessa-inline">
                    <span style="color:#1d4ed8;font-weight:600;">{{ $status['commessa']['commessa'] }}</span>
                    <span style="color:#6c757d;margin-left:8px;">{{ $status['commessa']['cliente'] }}</span>
                </div>
                @else
                <div class="mt-2" id="commessa-inline"></div>
                @endif

                <div class="big-progress-wrap">
                    <div class="big-progress-bar">
                        <div class="big-progress-fill" id="progress-fill" style="width:{{ $status['stampa']['progresso'] }}%"></div>
                        <div class="big-progress-text" id="progress-text">{{ $status['stampa']['progresso'] }}%</div>
                    </div>
                    <div class="big-progress-stats">
                        <span id="copies-info">Copie: <strong>{{ $status['stampa']['copie_fatte'] }}</strong> / {{ $status['stampa']['copie_totali'] }}</span>
                        <span id="pages-info">Pagine: {{ $status['stampa']['pagine'] }}</span>
                        <span>Utente: {{ $status['stampa']['utente'] }}</span>
                    </div>
                </div>
                @if(!empty($jobData['commessa_sheets']) && $jobData['commessa_sheets']['fogli_totali'] > 0)
                <div id="commessa-sheets-info" style="margin-top:10px; padding:8px 14px; background:#dbeafe; border:1px solid #93c5fd; border-radius:8px; font-size:13px; color:#495057;">
                    Fogli totali commessa: <strong style="color:#1d4ed8;">{{ $jobData['commessa_sheets']['fogli_totali'] }}</strong>
                    <span style="margin-left:8px;">{{ $jobData['commessa_sheets']['copie_totali'] }} copie</span>
                    <span style="margin-left:8px; color:#6c757d;">{{ $jobData['commessa_sheets']['run_count'] }} run</span>
                </div>
                @endif
            @else
                <div class="no-job-msg" id="no-print">Nessun job in stampa</div>
            @endif
            </div>
        </div>
    </div>

    {{-- Col destra: Operatore + Info --}}
    <div class="col-lg-4">
        <div class="fc">
            <div class="fc-label">Operatore assegnato</div>
            <div class="operatore-name" id="operatore-nome">{{ config('fiery.operatore') }}</div>
            <div id="commessa-detail" class="mt-3">
                @if(!empty($status['commessa']))
                <div class="mb-2"><div class="info-label">Commessa</div><div class="info-value">{{ $status['commessa']['commessa'] }}</div></div>
                <div class="mb-2"><div class="info-label">Cliente</div><div class="info-value-sm">{{ $status['commessa']['cliente'] }}</div></div>
                <div class="mb-2"><div class="info-label">Descrizione</div><div class="info-value-sm" style="font-size:12px;color:#6c757d;">{{ \Illuminate\Support\Str::limit($status['commessa']['descrizione'] ?? '', 80) }}</div></div>
                @endif
            </div>
            @php $printMes = $jobData['printing']['mes'] ?? null; @endphp
            @if($printMes)
            <div class="mt-3" style="border-top:1px solid #e9ecef; padding-top:12px;">
                @if($printMes['carta'])
                <div class="mb-2"><div class="info-label">Carta</div><div class="info-value-sm">{{ $printMes['carta'] }}</div>
                @if($printMes['cod_carta'])<div style="font-size:11px;color:#6c757d;">{{ $printMes['cod_carta'] }}</div>@endif</div>
                @endif
                <div class="mb-2"><div class="info-label">Quantita</div>
                    <div class="info-value-sm">{{ number_format($printMes['qta_richiesta'] ?? 0, 0, ',', '.') }} pz
                    @if($printMes['qta_carta']) <span style="color:#6c757d;font-size:12px;"> / {{ number_format($printMes['qta_carta'], 0, ',', '.') }} fg</span>@endif</div>
                </div>
                @if($printMes['data_prevista'])<div class="mb-2"><div class="info-label">Consegna prevista</div><div class="info-value-sm">{{ $printMes['data_prevista'] }}</div></div>@endif
                @if($printMes['responsabile'])<div class="mb-2"><div class="info-label">Responsabile</div><div class="info-value-sm">{{ $printMes['responsabile'] }}</div></div>@endif
                @if($printMes['note_prestampa'])<div class="mb-2"><div class="info-label">Note prestampa</div><div style="font-size:12px;color:#495057;">{{ $printMes['note_prestampa'] }}</div></div>@endif
                @if(!empty($printMes['fasi']))
                <div class="mb-2"><div class="info-label">Fasi</div><div class="mes-fasi">
                    @foreach($printMes['fasi'] as $f)<span class="fase-pill {{ $f['esterno'] ? 'fase-ext' : 'fase-s'.$f['stato'] }}">{{ $f['fase'] }}</span>@endforeach
                </div></div>
                @endif
            </div>
            @endif
        </div>

        @if(!empty($jobData))
        <div class="fc">
            <div class="fc-label">Statistiche coda</div>
            <div class="row text-center">
                <div class="col-4"><div class="info-value" style="color:#059669;" id="stat-completed">{{ count($jobData['completed']) }}</div><div class="info-label">Completati</div></div>
                <div class="col-4"><div class="info-value" style="color:#d97706;" id="stat-queue">{{ count($jobData['queue']) }}</div><div class="info-label">In coda</div></div>
                <div class="col-4"><div class="info-value" style="color:#495057;" id="stat-total">{{ $jobData['total'] }}</div><div class="info-label">Totale</div></div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ===== CODA DI STAMPA (card layout) ===== --}}
@if(!empty($jobData['queue']))
<div class="fc">
    <div class="section-title">Coda di stampa <span class="count-badge">{{ count($jobData['queue']) }}</span></div>
    <div class="queue-cards" id="queue-container">
        @foreach($jobData['queue'] as $job)
        <div class="queue-card">
            <div class="qc-main">
                <div class="qc-title">{{ $job['title'] }}</div>
                @if($job['mes'])
                <div class="qc-commessa">
                    <span class="commessa-tag">{{ $job['mes']['commessa'] }}</span>
                    <span style="font-size:11px;color:#6c757d;margin-left:6px;">{{ $job['mes']['cliente'] }}</span>
                </div>
                <div class="qc-desc">{{ \Illuminate\Support\Str::limit($job['mes']['descrizione'] ?? '', 100) }}</div>
                @if(!empty($job['mes']['note_prestampa']))
                <div class="qc-notes">{{ $job['mes']['note_prestampa'] }}</div>
                @endif
                @if(!empty($job['mes']['fasi']))
                <div class="mes-fasi">
                    @foreach($job['mes']['fasi'] as $f)
                    <span class="fase-pill {{ $f['esterno'] ? 'fase-ext' : 'fase-s'.$f['stato'] }}">{{ $f['fase'] }}</span>
                    @endforeach
                </div>
                @endif
                @endif
            </div>
            <div class="qc-carta">
                @if($job['mes'] && $job['mes']['carta'])
                <div class="qc-label">Carta</div>
                <div class="qc-val">{{ $job['mes']['carta'] }}</div>
                <div class="qc-sub">{{ $job['mes']['cod_carta'] ?? '' }}</div>
                @endif
            </div>
            <div class="qc-qta">
                @if($job['mes'])
                <div class="qc-num">{{ number_format($job['mes']['qta_richiesta'] ?? 0, 0, ',', '.') }}</div>
                <div class="qc-sub">pezzi</div>
                @if($job['mes']['qta_carta'])<div class="qc-sub">{{ number_format($job['mes']['qta_carta'], 0, ',', '.') }} fg</div>@endif
                @endif
            </div>
            <div class="qc-meta">
                <div class="qc-date">{{ $job['mes']['data_prevista'] ?? '-' }}</div>
                <div class="qc-copies">{{ $job['num_copies'] ?: '-' }} copie</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ===== COMPLETATI (tabella compatta) ===== --}}
@if(!empty($jobData['completed']))
<div class="fc">
    <div class="section-title">Completati di recente <span class="count-badge">{{ count($jobData['completed']) }}</span></div>
    <div style="overflow-x:auto;">
    <table class="compact-table">
        <thead>
            <tr>
                <th>Job</th>
                <th>Commessa</th>
                <th>Carta</th>
                <th>Copie</th>
                <th>Fogli</th>
                <th>B/V</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody id="completed-body">
            @foreach($jobData['completed'] as $job)
            @php $pct = $job['num_copies'] > 0 ? round(($job['copies_printed'] / $job['num_copies']) * 100) : 100; @endphp
            <tr>
                <td class="ct-title">{{ $job['title'] }}</td>
                <td>
                    @if($job['mes'])
                    <span class="commessa-tag">{{ $job['mes']['commessa'] }}</span>
                    <span style="font-size:10px;color:#6c757d;margin-left:4px;">{{ $job['mes']['cliente'] }}</span>
                    @endif
                </td>
                <td style="font-size:11px;">{{ $job['mes']['carta'] ?? '' }}</td>
                <td>
                    <div class="mini-progress"><div class="fill" style="width:{{ $pct }}%"></div></div>
                    <span class="copies-text">{{ $job['copies_printed'] }}/{{ $job['num_copies'] }}</span>
                </td>
                <td style="font-weight:600;">{{ $job['total_sheets'] }}</td>
                <td>{{ $job['duplex'] ? 'Si' : '-' }}</td>
                <td style="font-size:11px;color:#6c757d;white-space:nowrap;">{{ $job['date'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

@else
<div class="fc">
    <div class="offline-screen">
        <div class="icon">&#9888;</div>
        <h3>Fiery P400 non raggiungibile</h3>
        <p>Verificare che la stampante sia accesa e raggiungibile su <strong>{{ config('fiery.host') }}</strong></p>
    </div>
</div>
@endif
</div>

<script>
function fasiHtml(fasi) {
    if (!fasi || !fasi.length) return '';
    var h = '<div class="mes-fasi">';
    fasi.forEach(function(f) { h += '<span class="fase-pill ' + (f.esterno ? 'fase-ext' : 'fase-s' + f.stato) + '">' + f.fase + '</span>'; });
    return h + '</div>';
}

function fmt(n) { return Number(n || 0).toLocaleString('it-IT'); }

setInterval(function() {
    fetch('{{ route("mes.fiery.status") }}')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.online) {
                document.getElementById('stato-container').innerHTML = '<span class="status-pill sp-offline"><span class="status-dot sd-offline"></span>Offline</span>';
                return;
            }

            // Stato
            document.getElementById('stato-container').innerHTML = '<span class="status-pill sp-' + data.stato + '"><span class="status-dot sd-' + data.stato + '"></span>' + data.stato.charAt(0).toUpperCase() + data.stato.slice(1) + '</span>';

            // Avviso
            var avvBox = document.getElementById('avviso-box');
            if (data.avviso) {
                if (!avvBox) { avvBox = document.createElement('div'); avvBox.id = 'avviso-box'; avvBox.className = 'warning-bar'; document.getElementById('stato-container').closest('.fc').appendChild(avvBox); }
                avvBox.textContent = data.avviso; avvBox.style.display = '';
            } else if (avvBox) { avvBox.style.display = 'none'; }

            // Timestamp
            var ts = document.getElementById('ultimo-aggiornamento');
            if (ts && data.ultimo_aggiornamento) ts.textContent = data.ultimo_aggiornamento;

            // RIP
            var rc = document.getElementById('rip-container');
            if (data.rip && !data.rip.idle && data.rip.documento) { rc.className = 'rip-active'; rc.innerHTML = 'RIP: ' + data.rip.documento; }
            else { rc.className = 'rip-idle-box'; rc.innerHTML = 'RIP idle'; }

            // Job in stampa
            var sc = document.getElementById('stampa-container');
            if (data.stampa && data.stampa.documento) {
                var commHtml = '';
                if (data.commessa) {
                    commHtml = '<div class="mt-2"><span style="color:#1d4ed8;font-weight:600;">' + data.commessa.commessa + '</span><span style="color:#6c757d;margin-left:8px;">' + data.commessa.cliente + '</span></div>';
                }
                sc.innerHTML = '<div class="print-doc-name">' + data.stampa.documento + '</div>' + commHtml +
                    '<div class="big-progress-wrap"><div class="big-progress-bar"><div class="big-progress-fill" style="width:' + data.stampa.progresso + '%"></div><div class="big-progress-text">' + data.stampa.progresso + '%</div></div>' +
                    '<div class="big-progress-stats"><span>Copie: <strong>' + data.stampa.copie_fatte + '</strong> / ' + data.stampa.copie_totali + '</span><span>Pagine: ' + data.stampa.pagine + '</span><span>Utente: ' + (data.stampa.utente || '') + '</span></div></div>';
                if (data.jobs && data.jobs.commessa_sheets && data.jobs.commessa_sheets.fogli_totali > 0) {
                    var cs = data.jobs.commessa_sheets;
                    sc.innerHTML += '<div style="margin-top:10px;padding:8px 14px;background:#dbeafe;border:1px solid #93c5fd;border-radius:8px;font-size:13px;color:#495057;">Fogli totali: <strong style="color:#1d4ed8;">' + cs.fogli_totali + '</strong> <span style="margin-left:8px;">' + cs.copie_totali + ' copie</span> <span style="margin-left:8px;color:#6c757d;">' + cs.run_count + ' run</span></div>';
                }
            } else {
                sc.innerHTML = '<div class="no-job-msg">Nessun job in stampa</div>';
            }

            // Sidebar
            var cd = document.getElementById('commessa-detail');
            if (cd && data.commessa) {
                cd.innerHTML = '<div class="mb-2"><div class="info-label">Commessa</div><div class="info-value">' + data.commessa.commessa + '</div></div><div class="mb-2"><div class="info-label">Cliente</div><div class="info-value-sm">' + data.commessa.cliente + '</div></div>';
            } else if (cd) { cd.innerHTML = ''; }

            // Stats + Tabelle
            if (data.jobs) {
                var sc2 = document.getElementById('stat-completed');
                var sq = document.getElementById('stat-queue');
                var st = document.getElementById('stat-total');
                if (sc2) sc2.textContent = data.jobs.completed.length;
                if (sq) sq.textContent = data.jobs.queue.length;
                if (st) st.textContent = data.jobs.total;

                // Queue cards
                var qc = document.getElementById('queue-container');
                if (qc) {
                    var qh = '';
                    data.jobs.queue.forEach(function(j) {
                        var m = j.mes;
                        qh += '<div class="queue-card"><div class="qc-main"><div class="qc-title">' + j.title + '</div>';
                        if (m) {
                            qh += '<div class="qc-commessa"><span class="commessa-tag">' + m.commessa + '</span><span style="font-size:11px;color:#6c757d;margin-left:6px;">' + m.cliente + '</span></div>';
                            qh += '<div class="qc-desc">' + (m.descrizione || '').substring(0, 100) + '</div>';
                            if (m.note_prestampa) qh += '<div class="qc-notes">' + m.note_prestampa + '</div>';
                            qh += fasiHtml(m.fasi);
                        }
                        qh += '</div><div class="qc-carta">';
                        if (m && m.carta) qh += '<div class="qc-label">Carta</div><div class="qc-val">' + m.carta + '</div><div class="qc-sub">' + (m.cod_carta || '') + '</div>';
                        qh += '</div><div class="qc-qta">';
                        if (m) { qh += '<div class="qc-num">' + fmt(m.qta_richiesta) + '</div><div class="qc-sub">pezzi</div>'; if (m.qta_carta) qh += '<div class="qc-sub">' + fmt(m.qta_carta) + ' fg</div>'; }
                        qh += '</div><div class="qc-meta"><div class="qc-date">' + (m ? m.data_prevista || '-' : '-') + '</div><div class="qc-copies">' + (j.num_copies || '-') + ' copie</div></div></div>';
                    });
                    qc.innerHTML = qh;
                }

                // Completed table
                var cb = document.getElementById('completed-body');
                if (cb) {
                    var ch = '';
                    data.jobs.completed.forEach(function(j) {
                        var pct = j.num_copies > 0 ? Math.round((j.copies_printed / j.num_copies) * 100) : 100;
                        var mesHtml = '', cartaHtml = '';
                        if (j.mes) {
                            mesHtml = '<span class="commessa-tag">' + j.mes.commessa + '</span><span style="font-size:10px;color:#6c757d;margin-left:4px;">' + j.mes.cliente + '</span>';
                            cartaHtml = j.mes.carta || '';
                        }
                        ch += '<tr><td class="ct-title">' + j.title + '</td><td>' + mesHtml + '</td><td style="font-size:11px;">' + cartaHtml + '</td><td><div class="mini-progress"><div class="fill" style="width:' + pct + '%"></div></div><span class="copies-text">' + j.copies_printed + '/' + j.num_copies + '</span></td><td style="font-weight:600;">' + j.total_sheets + '</td><td>' + (j.duplex ? 'Si' : '-') + '</td><td style="font-size:11px;color:#6c757d;white-space:nowrap;">' + j.date + '</td></tr>';
                    });
                    cb.innerHTML = ch;
                }
            }
        })
        .catch(function() {});
}, 15000);
</script>
@endsection
