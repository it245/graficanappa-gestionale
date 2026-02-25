<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Test Tracking BRT</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; padding: 30px; }
        .card { max-width: 900px; margin: 0 auto; }
        .evento-row td { font-size: 14px; }
        .badge-stato { font-size: 16px; }
    </style>
</head>
<body>

<div class="card shadow">
    <div class="card-header text-white" style="background:#d4380d;">
        <h4 class="mb-0">Test Tracking BRT</h4>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('spedizione.trackingTest') }}" class="row g-3 mb-4">
            <div class="col-md-8">
                <input type="text" name="segnacollo" class="form-control form-control-lg"
                       placeholder="Inserisci segnacollo BRT (es. 067138050411341)"
                       value="{{ $segnacollo ?? '' }}" autofocus required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-lg w-100 text-white" style="background:#d4380d;">Cerca</button>
            </div>
        </form>

        @if($data)
            @php
                $resp = $data['ttParcelIdResponse'] ?? null;
                $errCode = $resp['executionMessage']['code'] ?? -1;
            @endphp

            @if(!$resp || $errCode !== 0 || isset($data['error']))
                <div class="alert alert-danger">
                    <strong>Errore:</strong>
                    {{ $data['message'] ?? ($resp['executionMessage']['codeDesc'] ?? 'Risposta non valida') }}
                    (code: {{ $errCode }})
                </div>
            @else
                @php
                    $bolla = $resp['bolla'];
                    $sped = $bolla['dati_spedizione'];
                    $cons = $bolla['dati_consegna'];
                    $rif = $bolla['riferimenti'];
                    $mitt = $bolla['mittente'];
                    $dest = $bolla['destinatario'];
                    $merce = $bolla['merce'];
                    $eventi = collect($resp['lista_eventi'])->filter(fn($e) => !empty($e['evento']['descrizione']))->values();
                    $ultimoEvento = $eventi->first()['evento']['descrizione'] ?? '-';
                @endphp

                {{-- Stato --}}
                <div class="text-center mb-4">
                    @if(str_contains($ultimoEvento, 'CONSEGNATA'))
                        <span class="badge bg-success badge-stato">{{ $ultimoEvento }}</span>
                    @elseif(str_contains($ultimoEvento, 'CONSEGNA') || str_contains($ultimoEvento, 'PARTITA'))
                        <span class="badge bg-warning text-dark badge-stato">{{ $ultimoEvento }}</span>
                    @else
                        <span class="badge bg-info badge-stato">{{ $ultimoEvento }}</span>
                    @endif
                </div>

                {{-- Dettagli spedizione --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th>N. Spedizione</th><td>{{ $sped['spedizione_id'] }}</td></tr>
                            <tr><th>Data spedizione</th><td>{{ $sped['spedizione_data'] }}</td></tr>
                            <tr><th>Servizio</th><td>{{ $sped['servizio'] }} ({{ $sped['porto'] }})</td></tr>
                            <tr><th>Filiale arrivo</th><td>{{ $sped['filiale_arrivo'] }}</td></tr>
                            <tr><th>Rif. mittente</th><td>{{ $rif['riferimento_mittente_numerico'] }} / {{ $rif['riferimento_mittente_alfabetico'] }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th>Mittente</th><td>{{ $mitt['ragione_sociale'] ?: $mitt['localita'] }} ({{ $mitt['sigla_area'] }})</td></tr>
                            <tr><th>Destinatario</th><td>{{ $dest['ragione_sociale'] ?: $dest['localita'] }} ({{ $dest['sigla_provincia'] }})</td></tr>
                            <tr><th>Colli</th><td>{{ $merce['colli'] }}</td></tr>
                            <tr><th>Peso (kg)</th><td>{{ $merce['peso_kg'] }}</td></tr>
                            <tr><th>Data consegna</th><td>{{ $cons['data_consegna_merce'] ?: '-' }} {{ $cons['ora_consegna_merce'] }}</td></tr>
                        </table>
                    </div>
                </div>

                {{-- Eventi --}}
                <h5>Eventi ({{ $eventi->count() }})</h5>
                <table class="table table-bordered table-sm table-striped">
                    <thead class="table-dark">
                        <tr><th>Data</th><th>Ora</th><th>Descrizione</th><th>Filiale</th></tr>
                    </thead>
                    <tbody>
                        @foreach($eventi as $item)
                            <tr class="evento-row">
                                <td>{{ $item['evento']['data'] }}</td>
                                <td>{{ $item['evento']['ora'] }}</td>
                                <td><strong>{{ $item['evento']['descrizione'] }}</strong></td>
                                <td>{{ $item['evento']['filiale'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @elseif($segnacollo)
            <div class="alert alert-warning">Nessuna risposta ricevuta.</div>
        @endif
    </div>
</div>

</body>
</html>
