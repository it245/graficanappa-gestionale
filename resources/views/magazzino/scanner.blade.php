@extends('layouts.mes')

@section('page-title', 'Scanner QR')
@section('topbar-title', 'Scanner QR Magazzino')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('vendor-css')
<style>
    #qr-reader { width: 100%; max-width: 500px; margin: 0 auto; }
    #qr-reader video { border-radius: 8px; }
    .risultato-qr { display: none; }
    .risultato-qr.show { display: block; }
</style>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">

        {{-- Scanner --}}
        <div class="card border-0 shadow-sm mb-4" style="background:var(--bg-card);">
            <div class="card-header text-center" style="background:transparent; border-bottom:1px solid var(--border-color);">
                <strong>Scansiona QR bancale</strong>
            </div>
            <div class="card-body text-center">
                <div id="qr-reader"></div>
                <p class="text-muted small mt-2">Inquadra il QR code del bancale con la fotocamera</p>
            </div>
        </div>

        {{-- Risultato scansione --}}
        <div class="card border-0 shadow-sm risultato-qr" id="risultatoCard" style="background:var(--bg-card);">
            <div class="card-header" style="background:transparent; border-bottom:1px solid var(--border-color);">
                <strong>Bancale riconosciuto</strong>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Codice</small>
                        <div class="fw-bold" id="res-codice">-</div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Descrizione</small>
                        <div class="fw-bold" id="res-descrizione">-</div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Formato</small>
                        <div id="res-formato">-</div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Grammatura</small>
                        <div id="res-grammatura">-</div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Giacenza disponibile</small>
                        <div class="fw-bold text-success fs-4" id="res-giacenza">-</div>
                    </div>
                </div>

                @if(($modalita ?? 'magazzino') === 'operatore')
                {{-- Prelievo rapido operatore --}}
                <hr>
                <form id="formPrelievo">
                    @csrf
                    <input type="hidden" name="qr_code" id="inp-qr">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Commessa</label>
                            <input type="text" name="commessa" class="form-control" required placeholder="es. 67007">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantita</label>
                            <input type="number" name="quantita" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-warning w-100">Preleva carta</button>
                        </div>
                    </div>
                </form>
                @else
                {{-- Azioni magazzino completo --}}
                <hr>
                <div class="d-flex gap-2">
                    <a id="btn-carico" href="#" class="btn btn-success flex-fill">Carico</a>
                    <a id="btn-prelievo" href="#" class="btn btn-warning flex-fill">Prelievo</a>
                    <button id="btn-reso" class="btn btn-info flex-fill" onclick="apriReso()">Reso</button>
                </div>
                @endif

                <div id="msgResult" class="mt-3" style="display:none;"></div>
            </div>
        </div>

    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const html5QrCode = new Html5Qrcode("qr-reader");
    const opToken = '{{ request("op_token") }}';

    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        onScanSuccess,
        () => {}
    ).catch(err => {
        document.getElementById('qr-reader').innerHTML =
            '<div class="alert alert-warning">Fotocamera non disponibile. Inserisci il codice manualmente.</div>' +
            '<input type="text" id="manual-qr" class="form-control mb-2" placeholder="Codice QR">' +
            '<button class="btn btn-primary" onclick="lookupManual()">Cerca</button>';
    });

    function onScanSuccess(decodedText) {
        // Estrai QR dal URL se presente
        let qr = decodedText;
        if (decodedText.includes('qr=')) {
            qr = new URL(decodedText).searchParams.get('qr');
        }

        html5QrCode.pause();
        lookup(qr);
    }

    window.lookupManual = function() {
        const qr = document.getElementById('manual-qr').value.trim();
        if (qr) lookup(qr);
    };

    function lookup(qrCode) {
        fetch('{{ route("magazzino.scan.lookup") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ qr: qrCode }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.found) {
                document.getElementById('res-codice').textContent = data.articolo.codice;
                document.getElementById('res-descrizione').textContent = data.articolo.descrizione;
                document.getElementById('res-formato').textContent = data.articolo.formato || '-';
                document.getElementById('res-grammatura').textContent = data.articolo.grammatura ? data.articolo.grammatura + 'g' : '-';
                document.getElementById('res-giacenza').textContent = new Intl.NumberFormat('it-IT').format(data.giacenza) + ' fg';

                @if(($modalita ?? 'magazzino') === 'operatore')
                    document.getElementById('inp-qr').value = qrCode;
                @else
                    document.getElementById('btn-carico').href =
                        '{{ route("magazzino.carico") }}?op_token=' + opToken + '&articolo_id=' + data.articolo.id;
                    document.getElementById('btn-prelievo').href =
                        '{{ route("magazzino.prelievo") }}?op_token=' + opToken + '&articolo_id=' + data.articolo.id;
                @endif

                document.getElementById('risultatoCard').classList.add('show');
            } else {
                showMsg('QR non riconosciuto', 'danger');
                setTimeout(() => html5QrCode.resume(), 2000);
            }
        })
        .catch(() => {
            showMsg('Errore di connessione', 'danger');
            setTimeout(() => html5QrCode.resume(), 2000);
        });
    }

    @if(($modalita ?? 'magazzino') === 'operatore')
    document.getElementById('formPrelievo')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fetch('{{ route("operatore.prelevaCarta.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify(Object.fromEntries(fd)),
        })
        .then(r => r.json())
        .then(data => {
            showMsg(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                setTimeout(() => {
                    document.getElementById('risultatoCard').classList.remove('show');
                    html5QrCode.resume();
                }, 2000);
            }
        });
    });
    @endif

    function showMsg(text, type) {
        const el = document.getElementById('msgResult');
        el.innerHTML = '<div class="alert alert-' + type + ' mb-0">' + text + '</div>';
        el.style.display = 'block';
        setTimeout(() => el.style.display = 'none', 4000);
    }
});
</script>
@endsection
