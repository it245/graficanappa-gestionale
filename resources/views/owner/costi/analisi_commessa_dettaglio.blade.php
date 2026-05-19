@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="m-0">Commessa {{ $commessa }}</h2>
            <div class="text-muted">{{ $cliente }} · {{ $descrizione }}</div>
            <div class="small text-muted mt-1">
                Qta richiesta: <strong>{{ number_format($qta_richiesta, 0, ',', '.') }}</strong>
                @if($data_consegna)
                · Consegna: <strong>{{ \Carbon\Carbon::parse($data_consegna)->format('d/m/Y') }}</strong>
                @endif
            </div>
        </div>
        <a href="{{ route('owner.costi.analisi.index') }}" class="btn btn-sm btn-outline-secondary">← Lista</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        {{-- Ore per Reparto --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light"><strong>Ore lavorate per reparto</strong></div>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Reparto</th><th style="width:120px;text-align:right;">Ore</th></tr></thead>
                    <tbody>
                        @forelse($oreReparto as $r)
                        <tr>
                            <td>{{ $r->reparto }}</td>
                            <td class="text-end font-monospace">{{ $r->ore_hm }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-muted small">Nessuna fase con ore registrate.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Produzione (editable) --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <strong>Produzione</strong>
                    @if($override)
                    <span class="badge bg-warning text-dark" title="Valori override manuale">override</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('owner.costi.analisi.updateOverride', $commessa) }}">
                    @csrf
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td>Fogli utilizzati ({{ $faseStampaNome }})</td>
                                <td style="width:140px;"><input type="number" min="0" step="1" name="fogli_utilizzati" value="{{ $fogliUtilizzati ?: '' }}" class="form-control form-control-sm text-end font-monospace" placeholder="auto"></td>
                            </tr>
                            <tr>
                                <td>Tiri (cm foil)</td>
                                <td><input type="number" min="0" step="0.01" name="tiri_cm_foil" value="{{ $tiriTotali ?: '' }}" class="form-control form-control-sm text-end font-monospace" placeholder="auto"></td>
                            </tr>
                            <tr>
                                <td>Inchiostro (g)</td>
                                <td><input type="number" min="0" step="0.01" name="inchiostro_g" value="{{ $inchiostroTotale ?: '' }}" class="form-control form-control-sm text-end font-monospace" placeholder="auto"></td>
                            </tr>
                            <tr>
                                <td>Scarti totali (fogli)</td>
                                <td><input type="number" min="0" step="1" name="scarti_fogli" value="{{ $scartiTotali ?: '' }}" class="form-control form-control-sm text-end font-monospace" placeholder="auto"></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-end p-2">
                                    <button class="btn btn-sm btn-primary">Salva override</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>

        {{-- Lavorazioni esterne --}}
        @if($lavorazioniEsterne->isNotEmpty())
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning bg-opacity-25"><strong>⚠️ Lavorazioni esterne</strong> <small class="text-muted ms-2">(costi extra da quantificare)</small></div>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Fase</th><th>Reparto</th><th>Fornitore esterno</th><th>Qta</th><th>Data inizio</th><th>Data fine</th></tr></thead>
                    <tbody>
                    @foreach($lavorazioniEsterne as $le)
                        <tr>
                            <td><strong>{{ $le->fase }}</strong></td>
                            <td class="small">{{ $le->reparto }}</td>
                            <td>{{ $le->fornitore }}</td>
                            <td class="font-monospace">{{ number_format($le->qta_prod, 0, ',', '.') }}</td>
                            <td class="small">{{ $le->data_inizio ? \Carbon\Carbon::parse($le->data_inizio)->format('d/m/Y') : '-' }}</td>
                            <td class="small">{{ $le->data_fine ? \Carbon\Carbon::parse($le->data_fine)->format('d/m/Y') : '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Altri Costi --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <strong>Altri costi</strong>
                    <button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#formAltroCosto">+ Aggiungi</button>
                </div>

                <div id="formAltroCosto" class="collapse">
                    <form method="POST" action="{{ route('owner.costi.analisi.storeAltro', $commessa) }}" class="p-3 border-bottom bg-light">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small mb-0">Categoria *</label>
                                <select name="categoria" class="form-select form-select-sm" required>
                                    @foreach($categorie as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-0">Descrizione</label>
                                <input type="text" name="descrizione" class="form-control form-control-sm" placeholder="(opzionale)" maxlength="500">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-0">Importo (€)</label>
                                <input type="number" step="0.01" min="0" name="importo" class="form-control form-control-sm" placeholder="(opzionale)">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-0">Data *</label>
                                <input type="date" name="data" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button class="btn btn-sm btn-primary w-100">Salva</button>
                            </div>
                        </div>
                    </form>
                </div>

                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th style="width:130px;">Data</th>
                            <th style="width:180px;">Categoria</th>
                            <th>Descrizione</th>
                            <th style="width:120px;">Autore</th>
                            <th style="width:120px;text-align:right;">Importo</th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($altriCosti as $c)
                        <tr>
                            <td>{{ $c->data->format('d/m/Y') }}</td>
                            <td><span class="badge bg-secondary">{{ $c->categoriaLabel() }}</span></td>
                            <td>{{ $c->descrizione ?? '—' }}</td>
                            <td class="small text-muted">{{ $c->autore }}</td>
                            <td class="text-end font-monospace">€ {{ number_format($c->importo, 2, ',', '.') }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('owner.costi.analisi.deleteAltro', $c->id) }}" onsubmit="return confirm('Eliminare questo costo?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger py-0">×</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted small py-3">Nessun costo registrato per questa commessa.</td></tr>
                        @endforelse
                    </tbody>
                    @if($altriCosti->isNotEmpty())
                    <tfoot>
                        <tr class="table-secondary">
                            <th colspan="4" class="text-end">TOTALE Altri Costi</th>
                            <th class="text-end font-monospace">€ {{ number_format($totaleAltriCosti, 2, ',', '.') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
