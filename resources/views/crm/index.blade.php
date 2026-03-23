@extends('layouts.app')
@section('title', 'CRM — Rete Contatti')

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">CRM — Rete Contatti</h4>
            <small class="text-muted">Gestisci la tua rete, tieni traccia delle interazioni e dei follow-up</small>
        </div>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalNuovoContatto">
            + Nuovo Contatto
        </button>
    </div>

    {{-- Statistiche rapide --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-2 fw-bold">{{ $totaleContatti }}</div>
                    <small class="text-muted">Contatti totali</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm {{ $followupScaduti > 0 ? 'border-start border-danger border-3' : '' }}">
                <div class="card-body text-center py-3">
                    <div class="fs-2 fw-bold text-danger">{{ $followupScaduti }}</div>
                    <small class="text-muted">Follow-up scaduti</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm {{ $followupSettimana > 0 ? 'border-start border-warning border-3' : '' }}">
                <div class="card-body text-center py-3">
                    <div class="fs-2 fw-bold text-warning">{{ $followupSettimana }}</div>
                    <small class="text-muted">Da contattare questa settimana</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="fs-2 fw-bold text-success">{{ $totaleContatti - $followupScaduti }}</div>
                    <small class="text-muted">In regola</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab e filtri --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <ul class="nav nav-pills nav-fill" style="min-width:340px">
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'tutti' ? 'active bg-dark' : 'text-dark' }}"
                           href="{{ route('crm.index', array_merge(request()->query(), ['tab' => 'tutti'])) }}">
                            Tutti
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'da_contattare' ? 'active bg-warning text-dark' : 'text-dark' }}"
                           href="{{ route('crm.index', array_merge(request()->query(), ['tab' => 'da_contattare'])) }}">
                            Da contattare
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === 'scaduti' ? 'active bg-danger' : 'text-dark' }}"
                           href="{{ route('crm.index', array_merge(request()->query(), ['tab' => 'scaduti'])) }}">
                            Scaduti
                        </a>
                    </li>
                </ul>
                <form class="d-flex gap-2 flex-wrap" method="GET" action="{{ route('crm.index') }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="text" name="cerca" class="form-control form-control-sm" placeholder="Cerca..."
                           value="{{ request('cerca') }}" style="width:160px">
                    <select name="categoria" class="form-select form-select-sm" style="width:130px">
                        <option value="">Categoria</option>
                        <option value="cliente" {{ request('categoria') === 'cliente' ? 'selected' : '' }}>Cliente</option>
                        <option value="fornitore" {{ request('categoria') === 'fornitore' ? 'selected' : '' }}>Fornitore</option>
                        <option value="partner" {{ request('categoria') === 'partner' ? 'selected' : '' }}>Partner</option>
                        <option value="altro" {{ request('categoria') === 'altro' ? 'selected' : '' }}>Altro</option>
                    </select>
                    <select name="priorita" class="form-select form-select-sm" style="width:120px">
                        <option value="">Priorità</option>
                        <option value="alta" {{ request('priorita') === 'alta' ? 'selected' : '' }}>Alta</option>
                        <option value="media" {{ request('priorita') === 'media' ? 'selected' : '' }}>Media</option>
                        <option value="bassa" {{ request('priorita') === 'bassa' ? 'selected' : '' }}>Bassa</option>
                    </select>
                    <button class="btn btn-sm btn-outline-dark" type="submit">Filtra</button>
                    @if(request()->hasAny(['cerca', 'categoria', 'priorita']))
                        <a href="{{ route('crm.index', ['tab' => $tab]) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="card-body p-0">
            @if($contatti->isEmpty())
                <div class="text-center py-5 text-muted">
                    <div class="fs-1 mb-2">📇</div>
                    <p>Nessun contatto trovato.</p>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalNuovoContatto">
                        Aggiungi il primo contatto
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>Azienda / Ruolo</th>
                                <th>Categoria</th>
                                <th>Priorità</th>
                                <th>Ultimo contatto</th>
                                <th>Prossimo follow-up</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contatti as $c)
                                @php
                                    $rowClass = '';
                                    if ($c->isFollowupScaduto()) $rowClass = 'scaduta';
                                    elseif ($c->isFollowupImminente()) $rowClass = 'warning-light';
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>
                                        <a href="{{ route('crm.dettaglio', $c) }}" class="text-decoration-none fw-semibold text-dark">
                                            {{ $c->nomeCompleto() }}
                                        </a>
                                        @if($c->email)
                                            <br><small class="text-muted">{{ $c->email }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($c->azienda)
                                            <span class="fw-semibold">{{ $c->azienda }}</span>
                                        @endif
                                        @if($c->ruolo)
                                            <br><small class="text-muted">{{ $c->ruolo }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $catBadge = match($c->categoria) {
                                                'cliente' => 'bg-primary',
                                                'fornitore' => 'bg-info text-dark',
                                                'partner' => 'bg-success',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $catBadge }}">{{ ucfirst($c->categoria) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $prioBadge = match($c->priorita) {
                                                'alta' => 'bg-danger',
                                                'media' => 'bg-warning text-dark',
                                                'bassa' => 'bg-secondary',
                                                default => 'bg-light text-dark',
                                            };
                                        @endphp
                                        <span class="badge {{ $prioBadge }}">{{ ucfirst($c->priorita) }}</span>
                                    </td>
                                    <td>
                                        @if($c->ultimo_contatto)
                                            {{ $c->ultimo_contatto->format('d/m/Y') }}
                                            <br><small class="text-muted">{{ $c->ultimo_contatto->diffForHumans() }}</small>
                                        @else
                                            <small class="text-muted">Mai</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($c->prossimo_followup)
                                            {{ $c->prossimo_followup->format('d/m/Y') }}
                                            @if($c->isFollowupScaduto())
                                                <br><small class="text-danger fw-bold">Scaduto!</small>
                                            @elseif($c->isFollowupImminente())
                                                <br><small class="text-warning fw-bold">Imminente</small>
                                            @endif
                                        @else
                                            <small class="text-muted">—</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('crm.dettaglio', $c) }}" class="btn btn-sm btn-outline-dark">Dettaglio</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-3 py-2">
                    {{ $contatti->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal Nuovo Contatto --}}
<div class="modal fade" id="modalNuovoContatto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('crm.salva') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Nuovo Contatto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cognome</label>
                        <input type="text" name="cognome" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Azienda</label>
                        <input type="text" name="azienda" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ruolo</label>
                        <input type="text" name="ruolo" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefono</label>
                        <input type="text" name="telefono" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Categoria</label>
                        <select name="categoria" class="form-select">
                            <option value="cliente">Cliente</option>
                            <option value="fornitore">Fornitore</option>
                            <option value="partner">Partner</option>
                            <option value="altro" selected>Altro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Priorità</label>
                        <select name="priorita" class="form-select">
                            <option value="alta">Alta</option>
                            <option value="media" selected>Media</option>
                            <option value="bassa">Bassa</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Follow-up ogni (giorni)</label>
                        <input type="number" name="frequenza_followup_giorni" class="form-control" value="30" min="1" max="365">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="submit" class="btn btn-danger">Salva contatto</button>
            </div>
        </form>
    </div>
</div>
@endsection
