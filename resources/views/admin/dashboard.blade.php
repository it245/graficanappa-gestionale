@extends('layouts.app')

@section('content')
<div class="container mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2>Dashboard Admin</h2>
            <p class="text-muted mb-0">Benvenuto, {{ request()->attributes->get('operatore_nome') ?? session('operatore_nome') }}</p>
        </div>
        <div>
            <a href="#" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#aggiungiOperatoreModal">+ Nuovo operatore</a>
            <a href="{{ route('admin.statistiche') }}" class="btn btn-info me-2">Statistiche</a>
            <a href="{{ route('admin.commesse') }}" class="btn btn-primary me-2">Storico Commesse</a>
            <a href="{{ route('admin.reportProduzione') }}" class="btn btn-warning me-2">Report Settimanale</a>
            <a href="{{ route('admin.cruscotto') }}" class="btn btn-danger me-2">Cruscotto Direzionale</a>
            <a href="{{ route('admin.reportDirezione') }}" class="btn btn-dark me-2">Report Direzione</a>
            <a href="{{ route('admin.reportPrinect') }}" class="btn btn-secondary me-2">Report Prinect</a>
            <a href="{{ route('admin.costi.report') }}" class="btn btn-outline-success me-2">Costi & Margini</a>
            <a href="{{ route('mes.fiery') }}" class="btn btn-outline-warning me-2">Fiery V900</a>
            <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Logout</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header bg-dark text-white">
            <strong>Operatori ({{ $operatori->count() }})</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm table-striped mb-0" style="font-size:13px;">
                <thead class="table-dark">
                    <tr>
                        <th>Codice</th>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Ruolo</th>
                        <th>Reparti</th>
                        <th>Attivo</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($operatori as $op)
                    <tr class="{{ !$op->attivo ? 'table-secondary' : '' }}">
                        <td><strong>{{ $op->codice_operatore }}</strong></td>
                        <td>{{ $op->nome }}</td>
                        <td>{{ $op->cognome }}</td>
                        <td>
                            @if($op->ruolo === 'admin')
                                <span class="badge bg-dark">admin</span>
                            @elseif($op->ruolo === 'owner')
                                <span class="badge bg-primary">owner</span>
                            @else
                                <span class="badge bg-secondary">operatore</span>
                            @endif
                        </td>
                        <td>{{ $op->reparti->pluck('nome')->join(', ') ?: '-' }}</td>
                        <td class="text-center">
                            @if($op->attivo)
                                <span class="badge bg-success">Si</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.operatore.modifica', $op->id) }}" class="btn btn-sm btn-outline-primary">Modifica</a>
                            <form method="POST" action="{{ route('admin.operatore.toggleAttivo', $op->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $op->attivo ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                    {{ $op->attivo ? 'Disattiva' : 'Attiva' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- CARD CODICI EAN PRODOTTI --}}
    <div class="card mt-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong>Codici EAN Prodotti ({{ $eanProdotti->count() }})</strong>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#aggiungiEanModal">+ Nuovo EAN</button>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm table-striped mb-0" style="font-size:13px;">
                <thead class="table-dark">
                    <tr>
                        <th>Articolo</th>
                        <th>Codice EAN</th>
                        <th style="width:180px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($eanProdotti as $ean)
                    <tr id="ean-row-{{ $ean->id }}">
                        <td class="ean-display">{{ $ean->articolo }}</td>
                        <td class="ean-display">{{ $ean->codice_ean }}</td>
                        <td class="ean-display">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-ean-edit" data-id="{{ $ean->id }}">Modifica</button>
                            <form method="POST" action="{{ route('admin.ean.elimina', $ean->id) }}" style="display:inline;" onsubmit="return confirm('Eliminare questo codice EAN?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Elimina</button>
                            </form>
                        </td>
                        {{-- Campi editing inline (nascosti) --}}
                        <td class="ean-edit" style="display:none;" colspan="3">
                            <form method="POST" action="{{ route('admin.ean.aggiorna', $ean->id) }}" class="d-flex align-items-center gap-2">
                                @csrf
                                <input type="text" name="articolo" value="{{ $ean->articolo }}" class="form-control form-control-sm" required style="max-width:200px;">
                                <input type="text" name="codice_ean" value="{{ $ean->codice_ean }}" class="form-control form-control-sm" required style="max-width:200px;">
                                <button type="submit" class="btn btn-sm btn-success">Salva</button>
                                <button type="button" class="btn btn-sm btn-secondary btn-ean-cancel" data-id="{{ $ean->id }}">Annulla</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if($eanProdotti->isEmpty())
                    <tr><td colspan="3" class="text-center text-muted">Nessun codice EAN configurato</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODALE AGGIUNGI EAN --}}
<div class="modal fade" id="aggiungiEanModal" tabindex="-1" aria-labelledby="aggiungiEanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.ean.salva') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aggiungiEanModalLabel">Nuovo Codice EAN</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Articolo</label>
                        <input type="text" name="articolo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Codice EAN</label>
                        <input type="text" name="codice_ean" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Aggiungi</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODALE AGGIUNGI OPERATORE --}}
<div class="modal fade" id="aggiungiOperatoreModal" tabindex="-1" aria-labelledby="aggiungiOperatoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.operatore.salva') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aggiungiOperatoreModalLabel">Aggiungi Operatore</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" id="adminNome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cognome</label>
                        <input type="text" name="cognome" id="adminCognome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ruolo</label>
                        <select name="ruolo" class="form-select" required>
                            <option value="operatore">Operatore</option>
                            <option value="owner">Owner</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Codice operatore</label>
                        <input type="text" id="adminCodice" class="form-control" value="{{ $prossimoCodice }}" data-numero="{{ $prossimoNumero }}" disabled>
                        <small class="text-muted">Il codice sara confermato alla creazione</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reparto Principale</label>
                        <select name="reparto_principale" class="form-select" required>
                            @foreach($reparti as $id => $rep)
                                <option value="{{ $id }}">{{ $rep }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reparto Secondario (facoltativo)</label>
                        <select name="reparto_secondario" class="form-select">
                            <option value="">-- Nessuno --</option>
                            @foreach($reparti as $id => $rep)
                                <option value="{{ $id }}">{{ $rep }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (facoltativa)</label>
                        <input type="password" name="password" class="form-control">
                        <small class="text-muted">Solo per ruoli admin</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Aggiungi</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var nomeInput = document.getElementById('adminNome');
    var cognomeInput = document.getElementById('adminCognome');
    var codiceInput = document.getElementById('adminCodice');
    var numero = codiceInput ? codiceInput.getAttribute('data-numero') : '001';
    numero = String(numero).padStart(3, '0');

    function aggiornaCodice() {
        var n = (nomeInput.value || '').trim().toUpperCase();
        var c = (cognomeInput.value || '').trim().toUpperCase();
        var iniziali = (n.charAt(0) || '_') + (c.charAt(0) || '_');
        codiceInput.value = iniziali + numero;
    }

    if (nomeInput && cognomeInput && codiceInput) {
        nomeInput.addEventListener('input', aggiornaCodice);
        cognomeInput.addEventListener('input', aggiornaCodice);
    }

    // EAN inline editing
    document.querySelectorAll('.btn-ean-edit').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var row = document.getElementById('ean-row-' + id);
            row.querySelectorAll('.ean-display').forEach(function(el) { el.style.display = 'none'; });
            row.querySelectorAll('.ean-edit').forEach(function(el) { el.style.display = ''; });
        });
    });

    document.querySelectorAll('.btn-ean-cancel').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var row = document.getElementById('ean-row-' + id);
            row.querySelectorAll('.ean-display').forEach(function(el) { el.style.display = ''; });
            row.querySelectorAll('.ean-edit').forEach(function(el) { el.style.display = 'none'; });
        });
    });
});
</script>
@endsection
