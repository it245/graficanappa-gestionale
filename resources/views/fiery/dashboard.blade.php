@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
<style>
    .fiery-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        padding: 24px;
        margin-bottom: 20px;
    }
    .fiery-card h5 {
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: 16px;
        letter-spacing: 0.5px;
    }
    .stato-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 22px;
        font-weight: 700;
        padding: 8px 20px;
        border-radius: 8px;
    }
    .stato-stampa { background: #d4edda; color: #155724; }
    .stato-idle { background: #e2e3e5; color: #383d41; }
    .stato-errore { background: #f8d7da; color: #721c24; }
    .stato-offline { background: #f8d7da; color: #721c24; }
    .stato-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 2s infinite;
    }
    .dot-stampa { background: #28a745; }
    .dot-idle { background: #6c757d; animation: none; }
    .dot-errore { background: #dc3545; }
    .dot-offline { background: #dc3545; animation: none; }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
    .avviso-box {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 10px 16px;
        font-size: 14px;
        margin-top: 12px;
    }
    .job-info {
        font-size: 16px;
        color: #212529;
    }
    .job-info .label {
        color: #6c757d;
        font-size: 13px;
    }
    .job-info .value {
        font-weight: 600;
        display: block;
        margin-top: 2px;
    }
    .progress-bar-fiery {
        height: 24px;
        border-radius: 12px;
        background: #e9ecef;
        overflow: hidden;
        margin-top: 8px;
    }
    .progress-bar-fiery .fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745, #20c997);
        border-radius: 12px;
        transition: width 0.5s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 600;
        font-size: 13px;
    }
    .rip-section {
        padding: 12px 16px;
        border-radius: 8px;
        background: #f8f9fa;
    }
    .rip-idle { color: #6c757d; }
    .rip-busy { color: #0d6efd; }
    .refresh-info {
        font-size: 12px;
        color: #adb5bd;
        text-align: right;
        margin-top: 8px;
    }
    .back-btn {
        margin-bottom: 16px;
    }
</style>

    <div class="back-btn">
        <a href="{{ route('owner.dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
        <a href="{{ route('mes.prinect') }}" class="btn btn-outline-secondary btn-sm">Prinect XL106</a>
        <span class="ms-3 fw-bold" style="font-size:20px;">Canon imagePRESS V900 — Fiery P400</span>
    </div>

    @if($status)
    <div class="row">
        {{-- Card Stato Macchina --}}
        <div class="col-md-4">
            <div class="fiery-card">
                <h5>Stato Macchina</h5>
                <div id="stato-container">
                    <span class="stato-badge stato-{{ $status['stato'] }}">
                        <span class="stato-dot dot-{{ $status['stato'] }}"></span>
                        {{ ucfirst($status['stato']) }}
                    </span>
                </div>
                @if($status['avviso'])
                <div class="avviso-box" id="avviso-box">
                    ⚠ {{ $status['avviso'] }}
                </div>
                @endif
                <div class="refresh-info" id="ultimo-aggiornamento">
                    {{ $status['ultimo_aggiornamento'] }}
                </div>
            </div>
        </div>

        {{-- Card Job in Stampa --}}
        <div class="col-md-5">
            <div class="fiery-card">
                <h5>Job in Stampa</h5>
                <div id="stampa-container">
                    @if($status['stampa']['documento'])
                    <div class="job-info">
                        <span class="label">Documento</span>
                        <span class="value" id="print-doc">{{ $status['stampa']['documento'] }}</span>
                    </div>
                    <div class="row mt-3">
                        <div class="col-6">
                            <div class="job-info">
                                <span class="label">Copie</span>
                                <span class="value" id="print-copies">{{ $status['stampa']['copie'] }}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="job-info">
                                <span class="label">Pagine</span>
                                <span class="value" id="print-pages">{{ $status['stampa']['pagine'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="progress-bar-fiery mt-3">
                        <div class="fill" id="print-progress" style="width: {{ $status['stampa']['progresso'] }}%">
                            {{ $status['stampa']['progresso'] }}%
                        </div>
                    </div>
                    @else
                    <div class="text-muted" id="no-print">Nessun job in stampa</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card RIP / Elaborazione --}}
        <div class="col-md-3">
            <div class="fiery-card">
                <h5>Elaborazione (RIP)</h5>
                <div id="rip-container">
                    @if(!$status['rip']['idle'] && $status['rip']['documento'])
                    <div class="rip-section rip-busy">
                        <strong>In elaborazione</strong><br>
                        <span id="rip-doc">{{ $status['rip']['documento'] }}</span><br>
                        <small id="rip-size">{{ number_format($status['rip']['dimensione'] / 1024, 1) }} MB</small>
                    </div>
                    @else
                    <div class="rip-section rip-idle">
                        <strong>Idle</strong><br>
                        <span class="text-muted">Nessuna elaborazione</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @else
    <div class="row">
        <div class="col-12">
            <div class="fiery-card text-center">
                <div class="stato-badge stato-offline">
                    <span class="stato-dot dot-offline"></span>
                    Fiery P400 non raggiungibile
                </div>
                <p class="text-muted mt-3">Verificare che la stampante sia accesa e raggiungibile su {{ config('fiery.host') }}</p>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
// Auto-refresh ogni 30 secondi
setInterval(function() {
    fetch('{{ route("mes.fiery.status") }}')
        .then(r => r.json())
        .then(data => {
            if (!data.online) {
                document.getElementById('stato-container').innerHTML =
                    '<span class="stato-badge stato-offline"><span class="stato-dot dot-offline"></span>Offline</span>';
                return;
            }

            // Aggiorna stato
            document.getElementById('stato-container').innerHTML =
                '<span class="stato-badge stato-' + data.stato + '">' +
                '<span class="stato-dot dot-' + data.stato + '"></span>' +
                data.stato.charAt(0).toUpperCase() + data.stato.slice(1) +
                '</span>';

            // Aggiorna avviso
            var avvisoBox = document.getElementById('avviso-box');
            if (data.avviso) {
                if (!avvisoBox) {
                    avvisoBox = document.createElement('div');
                    avvisoBox.id = 'avviso-box';
                    avvisoBox.className = 'avviso-box';
                    document.getElementById('stato-container').parentNode.appendChild(avvisoBox);
                }
                avvisoBox.innerHTML = '\u26A0 ' + data.avviso;
                avvisoBox.style.display = '';
            } else if (avvisoBox) {
                avvisoBox.style.display = 'none';
            }

            // Aggiorna timestamp
            var ts = document.getElementById('ultimo-aggiornamento');
            if (ts && data.ultimo_aggiornamento) ts.textContent = data.ultimo_aggiornamento;

            // Aggiorna stampa
            var sc = document.getElementById('stampa-container');
            if (data.stampa && data.stampa.documento) {
                sc.innerHTML =
                    '<div class="job-info">' +
                        '<span class="label">Documento</span>' +
                        '<span class="value">' + data.stampa.documento + '</span>' +
                    '</div>' +
                    '<div class="row mt-3">' +
                        '<div class="col-6"><div class="job-info"><span class="label">Copie</span><span class="value">' + data.stampa.copie + '</span></div></div>' +
                        '<div class="col-6"><div class="job-info"><span class="label">Pagine</span><span class="value">' + data.stampa.pagine + '</span></div></div>' +
                    '</div>' +
                    '<div class="progress-bar-fiery mt-3">' +
                        '<div class="fill" style="width:' + data.stampa.progresso + '%">' + data.stampa.progresso + '%</div>' +
                    '</div>';
            } else {
                sc.innerHTML = '<div class="text-muted">Nessun job in stampa</div>';
            }

            // Aggiorna RIP
            var rc = document.getElementById('rip-container');
            if (data.rip && !data.rip.idle && data.rip.documento) {
                var sizeMB = (data.rip.dimensione / 1024).toFixed(1);
                rc.innerHTML =
                    '<div class="rip-section rip-busy">' +
                        '<strong>In elaborazione</strong><br>' +
                        '<span>' + data.rip.documento + '</span><br>' +
                        '<small>' + sizeMB + ' MB</small>' +
                    '</div>';
            } else {
                rc.innerHTML =
                    '<div class="rip-section rip-idle">' +
                        '<strong>Idle</strong><br>' +
                        '<span class="text-muted">Nessuna elaborazione</span>' +
                    '</div>';
            }
        })
        .catch(function() {
            document.getElementById('stato-container').innerHTML =
                '<span class="stato-badge stato-offline"><span class="stato-dot dot-offline"></span>Errore connessione</span>';
        });
}, 30000);
</script>
@endsection
