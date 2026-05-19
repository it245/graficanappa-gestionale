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
        <a href="{{ route('admin.costi.analisi.index') }}" class="btn btn-sm btn-outline-secondary">← Lista</a>
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

        {{-- Produzione --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light"><strong>Produzione</strong></div>
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td>Fogli utilizzati ({{ $faseStampaNome }})</td>
                            <td class="text-end font-monospace"><strong>{{ number_format($fogliUtilizzati, 0, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <td>Tiri (cm foil) — stampa a caldo</td>
                            <td class="text-end font-monospace">{{ $tiriTotali > 0 ? number_format($tiriTotali, 2, ',', '.') : '—' }}</td>
                        </tr>
                        <tr>
                            <td>Inchiostro (g) — Prinect</td>
                            <td class="text-end font-monospace">{{ $inchiostroTotale > 0 ? number_format($inchiostroTotale, 2, ',', '.') : '—' }}</td>
                        </tr>
                        <tr>
                            <td>Scarti totali (fogli)</td>
                            <td class="text-end font-monospace text-danger">{{ number_format($scartiTotali, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Altri Costi --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <strong>Altri costi</strong>
                    <button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#formAltroCosto">+ Aggiungi</button>
                </div>

                <div id="formAltroCosto" class="collapse">
                    <form method="POST" action="{{ route('admin.costi.analisi.storeAltro', $commessa) }}" class="p-3 border-bottom bg-light">
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
                                <label class="form-label small mb-0">Importo (€) *</label>
                                <input type="number" step="0.01" min="0" name="importo" class="form-control form-control-sm" required>
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
                                <form method="POST" action="{{ route('admin.costi.analisi.deleteAltro', $c->id) }}" onsubmit="return confirm('Eliminare questo costo?')" class="d-inline">
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
