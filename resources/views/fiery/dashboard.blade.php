@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
<style>
    body { background: #f0f2f5; }
    .fiery-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        padding: 16px 0;
    }
    .fiery-header .machine-name {
        font-size: 22px;
        font-weight: 700;
        color: #1a1a2e;
        letter-spacing: -0.5px;
    }
    .fiery-header .machine-name small {
        font-size: 13px;
        font-weight: 400;
        color: #6c757d;
        margin-left: 12px;
    }
    .fiery-header .nav-links a {
        color: #495057;
        text-decoration: none;
        font-size: 13px;
        padding: 6px 14px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-left: 8px;
        transition: all 0.2s;
    }
    .fiery-header .nav-links a:hover {
        background: #e9ecef;
        border-color: #adb5bd;
        color: #212529;
    }

    .fc {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #dee2e6;
        padding: 20px 24px;
        margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .fc-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: #6c757d;
        letter-spacing: 1px;
        margin-bottom: 12px;
    }

    /* Status badge */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 22px;
        border-radius: 50px;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }
    .sp-stampa { background: #d1fae5; color: #059669; }
    .sp-idle { background: #f3f4f6; color: #6b7280; }
    .sp-errore { background: #fee2e2; color: #dc2626; }
    .sp-offline { background: #fee2e2; color: #dc2626; }

    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }
    .sd-stampa { background: #059669; box-shadow: 0 0 8px rgba(5,150,105,0.5); animation: glow 2s infinite; }
    .sd-idle { background: #9ca3af; }
    .sd-errore { background: #dc2626; box-shadow: 0 0 8px rgba(220,38,38,0.5); animation: glow 1s infinite; }
    .sd-offline { background: #dc2626; }
    @keyframes glow {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    .warning-bar {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 13px;
        margin-top: 12px;
    }

    /* Big progress */
    .big-progress-wrap {
        margin-top: 16px;
    }
    .big-progress-bar {
        height: 32px;
        background: #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
        position: relative;
    }
    .big-progress-fill {
        height: 100%;
        border-radius: 16px;
        background: linear-gradient(90deg, #059669, #10b981);
        transition: width 0.8s ease;
        position: relative;
        overflow: hidden;
    }
    .big-progress-fill::after {
        content: '';
        position: absolute;
        top: 0; left: -100%; width: 200%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
        animation: shimmer 3s infinite;
    }
    @keyframes shimmer {
        0% { transform: translateX(-50%); }
        100% { transform: translateX(50%); }
    }
    .big-progress-text {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        text-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .big-progress-stats {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        font-size: 13px;
        color: #6b7280;
    }

    /* Info values */
    .info-label {
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .info-value {
        font-size: 18px;
        font-weight: 700;
        color: #1a1a2e;
        margin-top: 2px;
    }
    .info-value-sm {
        font-size: 14px;
        font-weight: 600;
        color: #343a40;
    }
    .info-value .commessa-link {
        color: #2563eb;
        text-decoration: none;
    }

    /* RIP */
    .rip-active {
        background: #dbeafe;
        border: 1px solid #93c5fd;
        color: #1d4ed8;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 13px;
    }
    .rip-idle-box {
        color: #9ca3af;
        font-size: 13px;
    }

    /* Job tables */
    .job-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .job-table thead th {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6c757d;
        font-weight: 600;
        padding: 8px 12px;
        border-bottom: 2px solid #dee2e6;
        text-align: left;
    }
    .job-table tbody td {
        font-size: 13px;
        color: #495057;
        padding: 10px 12px;
        border-bottom: 1px solid #f1f3f5;
        vertical-align: middle;
    }
    .job-table tbody tr:hover {
        background: #f8f9fa;
    }
    .job-table .job-title {
        font-weight: 500;
        color: #212529;
        max-width: 320px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .job-table .commessa-tag {
        display: inline-block;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 4px;
    }
    .job-table .client-name {
        font-size: 11px;
        color: #6c757d;
    }

    /* State pills in tables */
    .state-pill {
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 12px;
    }
    .state-queue { background: #fef3c7; color: #92400e; }
    .state-completed { background: #d1fae5; color: #059669; }
    .state-printing { background: #d1fae5; color: #059669; }
    .state-waiting { background: #dbeafe; color: #1d4ed8; }
    .state-canceled { background: #fee2e2; color: #dc2626; }

    /* Mini progress in table */
    .mini-progress {
        width: 80px;
        height: 6px;
        background: #e5e7eb;
        border-radius: 3px;
        overflow: hidden;
        display: inline-block;
        vertical-align: middle;
    }
    .mini-progress .fill {
        height: 100%;
        background: #059669;
        border-radius: 3px;
    }
    .copies-text {
        font-size: 12px;
        color: #6c757d;
        margin-left: 6px;
    }

    /* Section titles */
    .section-title {
        font-size: 14px;
        font-weight: 700;
        color: #212529;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title .count-badge {
        background: #e9ecef;
        color: #495057;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 10px;
    }

    .timestamp-info {
        font-size: 11px;
        color: #adb5bd;
        text-align: right;
        margin-top: 8px;
    }

    /* Print doc name - big */
    .print-doc-name {
        font-size: 16px;
        font-weight: 600;
        color: #212529;
        word-break: break-all;
        line-height: 1.4;
    }

    .operatore-name {
        font-size: 20px;
        font-weight: 700;
        color: #1a1a2e;
    }

    .no-job-msg {
        color: #adb5bd;
        font-size: 14px;
        padding: 20px 0;
    }

    .offline-screen {
        text-align: center;
        padding: 60px 20px;
    }
    .offline-screen .icon {
        font-size: 48px;
        margin-bottom: 16px;
    }
    .offline-screen h3 {
        color: #dc2626;
        font-weight: 700;
    }
    .offline-screen p {
        color: #6c757d;
        font-size: 14px;
    }
</style>

<div class="fiery-header">
    <div>
        <span class="machine-name">Canon imagePRESS V900 <small>Fiery P400</small></span>
    </div>
    <div class="nav-links">
        <a href="{{ route('owner.dashboard') }}">Dashboard</a>
        <a href="{{ route('mes.prinect') }}">Prinect XL106</a>
    </div>
</div>

@if($status)
<div class="row">
    {{-- Col sinistra: Stato + Job in stampa --}}
    <div class="col-lg-8">
        {{-- Riga stato --}}
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
                    <div class="rip-active" id="rip-container">
                        RIP: {{ $status['rip']['documento'] }}
                    </div>
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

        {{-- Job in stampa --}}
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
                <div class="mb-2">
                    <div class="info-label">Commessa</div>
                    <div class="info-value">{{ $status['commessa']['commessa'] }}</div>
                </div>
                <div class="mb-2">
                    <div class="info-label">Cliente</div>
                    <div class="info-value-sm">{{ $status['commessa']['cliente'] }}</div>
                </div>
                <div>
                    <div class="info-label">Descrizione</div>
                    <div class="info-value-sm" style="font-size:12px;color:#6c757d;">{{ \Illuminate\Support\Str::limit($status['commessa']['descrizione'] ?? '', 80) }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Stats --}}
        @if(!empty($jobData))
        <div class="fc">
            <div class="fc-label">Statistiche coda</div>
            <div class="row text-center">
                <div class="col-4">
                    <div class="info-value" style="color:#059669;" id="stat-completed">{{ count($jobData['completed']) }}</div>
                    <div class="info-label">Completati</div>
                </div>
                <div class="col-4">
                    <div class="info-value" style="color:#d97706;" id="stat-queue">{{ count($jobData['queue']) }}</div>
                    <div class="info-label">In coda</div>
                </div>
                <div class="col-4">
                    <div class="info-value" style="color:#495057;" id="stat-total">{{ $jobData['total'] }}</div>
                    <div class="info-label">Totale</div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Job in coda --}}
@if(!empty($jobData['queue']))
<div class="fc">
    <div class="section-title">
        Coda di stampa
        <span class="count-badge">{{ count($jobData['queue']) }}</span>
    </div>
    <table class="job-table">
        <thead>
            <tr>
                <th>Job</th>
                <th>Commessa</th>
                <th>Pagine</th>
                <th>Copie</th>
                <th>Stato</th>
            </tr>
        </thead>
        <tbody id="queue-body">
            @foreach($jobData['queue'] as $job)
            <tr>
                <td class="job-title">{{ $job['title'] }}</td>
                <td>
                    @if($job['mes'])
                        <span class="commessa-tag">{{ $job['mes']['commessa'] }}</span>
                        <div class="client-name">{{ $job['mes']['cliente'] }}</div>
                    @endif
                </td>
                <td>{{ $job['num_pages'] }}</td>
                <td>{{ $job['num_copies'] ?: '-' }}</td>
                <td><span class="state-pill state-queue">In coda</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Job completati recenti --}}
@if(!empty($jobData['completed']))
<div class="fc">
    <div class="section-title">
        Completati di recente
        <span class="count-badge">{{ count($jobData['completed']) }}</span>
    </div>
    <table class="job-table">
        <thead>
            <tr>
                <th>Job</th>
                <th>Commessa</th>
                <th>Copie</th>
                <th>Fogli</th>
                <th>Duplex</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody id="completed-body">
            @foreach($jobData['completed'] as $job)
            @php
                $pct = $job['num_copies'] > 0 ? round(($job['copies_printed'] / $job['num_copies']) * 100) : 100;
            @endphp
            <tr>
                <td class="job-title">{{ $job['title'] }}</td>
                <td>
                    @if($job['mes'])
                        <span class="commessa-tag">{{ $job['mes']['commessa'] }}</span>
                        <div class="client-name">{{ $job['mes']['cliente'] }}</div>
                    @endif
                </td>
                <td>
                    <div class="mini-progress"><div class="fill" style="width:{{ $pct }}%"></div></div>
                    <span class="copies-text">{{ $job['copies_printed'] }}/{{ $job['num_copies'] }}</span>
                </td>
                <td>{{ $job['total_sheets'] }}</td>
                <td>{{ $job['duplex'] ? 'B/V' : 'Solo F' }}</td>
                <td style="font-size:12px;color:#6c757d;">{{ $job['date'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@else
{{-- Offline --}}
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
setInterval(function() {
    fetch('{{ route("mes.fiery.status") }}')
        .then(r => r.json())
        .then(data => {
            if (!data.online) {
                document.getElementById('stato-container').innerHTML =
                    '<span class="status-pill sp-offline"><span class="status-dot sd-offline"></span>Offline</span>';
                return;
            }

            // Stato
            document.getElementById('stato-container').innerHTML =
                '<span class="status-pill sp-' + data.stato + '">' +
                '<span class="status-dot sd-' + data.stato + '"></span>' +
                data.stato.charAt(0).toUpperCase() + data.stato.slice(1) + '</span>';

            // Avviso
            var avvBox = document.getElementById('avviso-box');
            if (data.avviso) {
                if (!avvBox) {
                    avvBox = document.createElement('div');
                    avvBox.id = 'avviso-box';
                    avvBox.className = 'warning-bar';
                    document.getElementById('stato-container').closest('.fc').appendChild(avvBox);
                }
                avvBox.textContent = data.avviso;
                avvBox.style.display = '';
            } else if (avvBox) {
                avvBox.style.display = 'none';
            }

            // Timestamp
            var ts = document.getElementById('ultimo-aggiornamento');
            if (ts && data.ultimo_aggiornamento) ts.textContent = data.ultimo_aggiornamento;

            // RIP
            var rc = document.getElementById('rip-container');
            if (data.rip && !data.rip.idle && data.rip.documento) {
                rc.className = 'rip-active';
                rc.innerHTML = 'RIP: ' + data.rip.documento;
            } else {
                rc.className = 'rip-idle-box';
                rc.innerHTML = 'RIP idle';
            }

            // Job in stampa
            var sc = document.getElementById('stampa-container');
            if (data.stampa && data.stampa.documento) {
                var commHtml = '';
                if (data.commessa) {
                    commHtml = '<div class="mt-2" id="commessa-inline">' +
                        '<span style="color:#1d4ed8;font-weight:600;">' + data.commessa.commessa + '</span>' +
                        '<span style="color:#6c757d;margin-left:8px;">' + data.commessa.cliente + '</span></div>';
                }
                sc.innerHTML =
                    '<div class="print-doc-name">' + data.stampa.documento + '</div>' +
                    commHtml +
                    '<div class="big-progress-wrap">' +
                        '<div class="big-progress-bar">' +
                            '<div class="big-progress-fill" style="width:' + data.stampa.progresso + '%"></div>' +
                            '<div class="big-progress-text">' + data.stampa.progresso + '%</div>' +
                        '</div>' +
                        '<div class="big-progress-stats">' +
                            '<span>Copie: <strong>' + data.stampa.copie_fatte + '</strong> / ' + data.stampa.copie_totali + '</span>' +
                            '<span>Pagine: ' + data.stampa.pagine + '</span>' +
                            '<span>Utente: ' + (data.stampa.utente || '') + '</span>' +
                        '</div>' +
                    '</div>';
                // Fogli totali commessa (da Accounting)
                if (data.jobs && data.jobs.commessa_sheets && data.jobs.commessa_sheets.fogli_totali > 0) {
                    var cs = data.jobs.commessa_sheets;
                    sc.innerHTML += '<div id="commessa-sheets-info" style="margin-top:10px; padding:8px 14px; background:#dbeafe; border:1px solid #93c5fd; border-radius:8px; font-size:13px; color:#495057;">' +
                        'Fogli totali commessa: <strong style="color:#1d4ed8;">' + cs.fogli_totali + '</strong>' +
                        '<span style="margin-left:8px;">' + cs.copie_totali + ' copie</span>' +
                        '<span style="margin-left:8px; color:#6c757d;">' + cs.run_count + ' run</span></div>';
                }
            } else {
                sc.innerHTML = '<div class="no-job-msg">Nessun job in stampa</div>';
            }

            // Commessa detail (sidebar)
            var cd = document.getElementById('commessa-detail');
            if (cd && data.commessa) {
                cd.innerHTML =
                    '<div class="mb-2"><div class="info-label">Commessa</div><div class="info-value">' + data.commessa.commessa + '</div></div>' +
                    '<div class="mb-2"><div class="info-label">Cliente</div><div class="info-value-sm">' + data.commessa.cliente + '</div></div>';
            } else if (cd) {
                cd.innerHTML = '';
            }

            // Job tables (API v5)
            if (data.jobs) {
                // Stats
                var sc2 = document.getElementById('stat-completed');
                var sq = document.getElementById('stat-queue');
                var st = document.getElementById('stat-total');
                if (sc2) sc2.textContent = data.jobs.completed.length;
                if (sq) sq.textContent = data.jobs.queue.length;
                if (st) st.textContent = data.jobs.total;

                // Queue table
                var qb = document.getElementById('queue-body');
                if (qb) {
                    var qh = '';
                    data.jobs.queue.forEach(function(j) {
                        var mesHtml = j.mes ? '<span class="commessa-tag">' + j.mes.commessa + '</span><div class="client-name">' + j.mes.cliente + '</div>' : '';
                        qh += '<tr><td class="job-title">' + j.title + '</td><td>' + mesHtml + '</td><td>' + j.num_pages + '</td><td>' + (j.num_copies || '-') + '</td><td><span class="state-pill state-queue">In coda</span></td></tr>';
                    });
                    qb.innerHTML = qh;
                }

                // Completed table
                var cb = document.getElementById('completed-body');
                if (cb) {
                    var ch = '';
                    data.jobs.completed.forEach(function(j) {
                        var pct = j.num_copies > 0 ? Math.round((j.copies_printed / j.num_copies) * 100) : 100;
                        var mesHtml = j.mes ? '<span class="commessa-tag">' + j.mes.commessa + '</span><div class="client-name">' + j.mes.cliente + '</div>' : '';
                        ch += '<tr><td class="job-title">' + j.title + '</td><td>' + mesHtml + '</td><td><div class="mini-progress"><div class="fill" style="width:' + pct + '%"></div></div><span class="copies-text">' + j.copies_printed + '/' + j.num_copies + '</span></td><td>' + j.total_sheets + '</td><td>' + (j.duplex ? 'B/V' : 'Solo F') + '</td><td style="font-size:12px;color:#6c757d;">' + j.date + '</td></tr>';
                    });
                    cb.innerHTML = ch;
                }
            }
        })
        .catch(function() {});
}, 15000);
</script>
@endsection
