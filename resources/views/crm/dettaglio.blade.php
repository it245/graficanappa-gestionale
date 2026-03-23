@extends('layouts.app')
@section('title', 'CRM — ' . $contatto->nomeCompleto())

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Breadcrumb --}}
    <nav class="mb-3">
        <a href="{{ route('crm.index') }}" class="text-decoration-none">&larr; Torna alla lista</a>
    </nav>

    <div class="row g-4">
        {{-- Colonna sinistra: dati contatto --}}
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">{{ $contatto->nomeCompleto() }}</h5>
                    @php
                        $prioBadge = match($contatto->priorita) {
                            'alta' => 'bg-danger',
                            'media' => 'bg-warning text-dark',
                            'bassa' => 'bg-secondary',
                            default => 'bg-light text-dark',
                        };
                        $catBadge = match($contatto->categoria) {
                            'cliente' => 'bg-primary',
                            'fornitore' => 'bg-info text-dark',
                            'partner' => 'bg-success',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <div>
                        <span class="badge {{ $catBadge }}">{{ ucfirst($contatto->categoria) }}</span>
                        <span class="badge {{ $prioBadge }}">{{ ucfirst($contatto->priorita) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Stato follow-up --}}
                    @if($contatto->isFollowupScaduto())
                        <div class="alert alert-danger py-2 mb-3">
                            <strong>Follow-up scaduto!</strong> Era previsto per il {{ $contatto->prossimo_followup->format('d/m/Y') }}.
                        </div>
                    @elseif($contatto->isFollowupImminente())
                        <div class="alert alert-warning py-2 mb-3">
                            <strong>Follow-up imminente</strong> — {{ $contatto->prossimo_followup->format('d/m/Y') }}
                        </div>
                    @elseif($contatto->prossimo_followup)
                        <div class="alert alert-light py-2 mb-3">
                            Prossimo follow-up: <strong>{{ $contatto->prossimo_followup->format('d/m/Y') }}</strong>
                            (tra {{ now()->diffInDays($contatto->prossimo_followup) }} giorni)
                        </div>
                    @endif

                    {{-- Modifica contatto --}}
                    <form method="POST" action="{{ route('crm.aggiorna', $contatto) }}">
                        @csrf
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-0">Nome *</label>
                                <input type="text" name="nome" class="form-control form-control-sm" value="{{ $contatto->nome }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-0">Cognome</label>
                                <input type="text" name="cognome" class="form-control form-control-sm" value="{{ $contatto->cognome }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-0">Azienda</label>
                                <input type="text" name="azienda" class="form-control form-control-sm" value="{{ $contatto->azienda }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-0">Ruolo</label>
                                <input type="text" name="ruolo" class="form-control form-control-sm" value="{{ $contatto->ruolo }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-0">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm" value="{{ $contatto->email }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-0">Telefono</label>
                                <input type="text" name="telefono" class="form-control form-control-sm" value="{{ $contatto->telefono }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-4">
                                <label class="form-label small text-muted mb-0">Categoria</label>
                                <select name="categoria" class="form-select form-select-sm">
                                    @foreach(['cliente','fornitore','partner','altro'] as $cat)
                                        <option value="{{ $cat }}" {{ $contatto->categoria === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small text-muted mb-0">Priorità</label>
                                <select name="priorita" class="form-select form-select-sm">
                                    @foreach(['alta','media','bassa'] as $prio)
                                        <option value="{{ $prio }}" {{ $contatto->priorita === $prio ? 'selected' : '' }}>{{ ucfirst($prio) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small text-muted mb-0">Follow-up (gg)</label>
                                <input type="number" name="frequenza_followup_giorni" class="form-control form-control-sm"
                                       value="{{ $contatto->frequenza_followup_giorni }}" min="1" max="365">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-0">Note</label>
                            <textarea name="note" class="form-control form-control-sm" rows="2">{{ $contatto->note }}</textarea>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-sm btn-dark">Salva modifiche</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalElimina">
                                Elimina contatto
                            </button>
                        </div>
                    </form>

                    {{-- Info rapide --}}
                    <hr>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="fw-bold">{{ $contatto->interazioni->count() }}</div>
                            <small class="text-muted">Interazioni</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold">{{ $contatto->ultimo_contatto ? $contatto->ultimo_contatto->format('d/m/Y') : '—' }}</div>
                            <small class="text-muted">Ultimo contatto</small>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold">{{ $contatto->created_at->format('d/m/Y') }}</div>
                            <small class="text-muted">Creato il</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonna destra: interazioni --}}
        <div class="col-lg-7">
            {{-- Nuova interazione --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">Registra interazione</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('crm.salvaInterazione', $contatto) }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select name="tipo" class="form-select form-select-sm" required>
                                    <option value="telefonata">Telefonata</option>
                                    <option value="email">Email</option>
                                    <option value="incontro">Incontro</option>
                                    <option value="messaggio">Messaggio</option>
                                    <option value="altro">Altro</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="data_interazione" class="form-control form-control-sm"
                                       value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="note" class="form-control form-control-sm" placeholder="Note (opzionale)">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-danger w-100">Registra</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Timeline interazioni --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">Storico interazioni</h6>
                </div>
                <div class="card-body p-0">
                    @if($contatto->interazioni->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <p class="mb-0">Nessuna interazione registrata.</p>
                            <small>Registra la prima interazione usando il modulo sopra.</small>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($contatto->interazioni as $int)
                                <div class="list-group-item d-flex justify-content-between align-items-start py-3">
                                    <div>
                                        <div class="fw-semibold">
                                            {{ $int->tipoIcona() }} {{ $int->tipoLabel() }}
                                            <small class="text-muted ms-2">{{ $int->data_interazione->format('d/m/Y H:i') }}</small>
                                        </div>
                                        @if($int->note)
                                            <div class="text-muted small mt-1">{{ $int->note }}</div>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('crm.eliminaInterazione', [$contatto, $int]) }}"
                                          onsubmit="return confirm('Eliminare questa interazione?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary border-0">&times;</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal conferma eliminazione --}}
<div class="modal fade" id="modalElimina" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Conferma eliminazione</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Eliminare <strong>{{ $contatto->nomeCompleto() }}</strong> e tutte le sue interazioni?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                <form method="POST" action="{{ route('crm.elimina', $contatto) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
