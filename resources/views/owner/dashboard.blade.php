@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Dashboard Owner</h2>

    <p>
        Benvenuto: {{ auth()->user()->nome ?? session('operatore_nome') }}
        | Ruolo: {{ auth()->user()->ruolo ?? session('operatore_ruolo') }}
    </p>

    {{-- PULSANTI --}}
    <div class="mb-3 d-flex gap-2 align-items-center">
        <form action="{{ route('owner.importOrdini') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" required>
            <button class="btn btn-primary">Importa Ordini</button>
        </form>

        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#aggiungiOperatoreModal">
            Aggiungi Operatore
        </button>
        <div class="modal fade" id="aggiungiOperatoreModal" tabindex="-1" aria-labelledby="aggiungiOperatoreModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('owner.aggiungiOperatore') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="aggiungiOperatoreModalLabel">Aggiungi Operatore</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" name="nome" id="nome" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Cognome</label>
                <input type="text" name="cognome" id="cognome" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Ruolo</label>
                <select name="ruolo" class="form-select" required>
                    <option value="operatore">Operatore</option>
                    <option value="owner">Owner</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Codice operatore</label>
                <input type="text" id="codice_operatore" class="form-control" value="{{ $prossimoCodice }}" data-numero="{{ $prossimoNumero}}" disabled>
                <small class="text-muted">Il codice sarà confermato alla creazione</small>
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
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Aggiungi</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
        </div>
      </div>
    </form>
  </div>
</div>

        <a href="{{ route('owner.fasiTerminate') }}"
           class="btn btn-primary"
           style="min-width:180px;">
            Visualizza fasi terminate
        </a>
    </div>

@php
    /* Helper date italiane */
    function formatItalianDate($date, $withTime = false) {
        if (!$date) return '-';
        try {
            return \Carbon\Carbon::parse($date)
                ->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
        } catch (\Exception $e) {
            return $date;
        }
    }
@endphp

    {{-- TABELLA --}}
    <table class="table table-bordered table-sm table-striped">
        <thead class="table-dark">
            <tr>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Codice Articolo</th>
                <th>Descrizione</th>
                <th>Qta</th>
                <th>UM</th>
                <th>Priorità</th>
                <th>Data Registrazione</th>
                <th>Data Prevista Consegna</th>
                <th>Cod Carta</th>
                <th>Carta</th>
                <th>Qta Carta</th>
                <th>UM Carta</th>
                <th>Fase</th>
                <th>Reparto</th>
                <th>Operatori</th>
                <th>Qta Prod.</th>
                <th>Note</th>
                <th>Data Inizio</th>
                <th>Data Fine</th>
                <th>Stato</th>
            </tr>
        </thead>

        <tbody>
        @foreach($fasi as $fase)

            @php
                $rowClass = '';

                if ($fase->ordine->data_prevista_consegna) {
                    $dataPrevista = \Carbon\Carbon::parse(
                        $fase->ordine->data_prevista_consegna
                    )->startOfDay();

                    $oggi = \Carbon\Carbon::today();
                    $diffGiorni = $oggi->diffInDays($dataPrevista, false);

                    if ($diffGiorni <= -5) {
                        $rowClass = 'scaduta';           // 🔴 passati 5 giorni
                    } elseif ($diffGiorni <= 3) {
                        $rowClass = 'warning-strong';   // 🟠 mancano 3 giorni
                    } elseif ($diffGiorni <= 5) {
                        $rowClass = 'warning-light';    // 🟡 mancano 5 giorni
                    }
                }
            @endphp

            <tr class="{{ $rowClass }}" data-id="{{ $fase->id }}">
                <td>{{ $fase->ordine->commessa ?? '-' }}</td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cliente_nome', this.innerText)">
                    {{ $fase->ordine->cliente_nome ?? '-' }}
                </td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cod_art', this.innerText)">
                    {{ $fase->ordine->cod_art ?? '-' }}
                </td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'descrizione', this.innerText)">
                    {{ $fase->ordine->descrizione ?? '-' }}
                </td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_richiesta', this.innerText)">
                    {{ $fase->ordine->qta_richiesta ?? '-' }}
                </td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'um', this.innerText)">
                    {{ $fase->ordine->um ?? '-' }}
                </td>

                <td>{{ $fase->priorita ?? '-' }}</td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_registrazione', this.innerText)">
                    {{ formatItalianDate($fase->ordine->data_registrazione) }}
                </td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_prevista_consegna', this.innerText)">
                    {{ formatItalianDate($fase->ordine->data_prevista_consegna) }}
                </td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cod_carta', this.innerText)">
                    {{ $fase->ordine->cod_carta ?? '-' }}
                </td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'carta', this.innerText)">
                    {{ $fase->ordine->carta ?? '-' }}
                </td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_carta', this.innerText)">
                    {{ $fase->ordine->qta_carta ?? '-' }}
                </td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'UM_carta', this.innerText)">
                    {{ $fase->ordine->UM_carta ?? '-' }}
                </td>

                <td>{{ $fase->faseCatalogo->nome ?? '-' }}</td>
                <td>{{ $fase->faseCatalogo->reparto->nome ?? '-' }}</td>

                <td>
                    @forelse($fase->operatori as $op)
                        {{ $op->nome }}
                        ({{ formatItalianDate($op->pivot->data_inizio, true) }})<br>
                    @empty
                        -
                    @endforelse
                </td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_prod', this.innerText)">
                    {{ $fase->qta_prod ?? '-' }}
                </td>

                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'note', this.innerText)">
                    {{ $fase->note ?? '-' }}
                </td>

                <td>{{ formatItalianDate($fase->data_inizio, true) }}</td>
                <td>{{ formatItalianDate($fase->data_fine, true) }}</td>
                <td>{{ $fase->stato ?? '-' }}</td>
            </tr>

        @endforeach
        </tbody>
    </table>
</div>

{{-- JS --}}
<script>
function aggiornaCampo(faseId, campo, valore){
    fetch('{{ route("produzione.aggiornaCampo") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId, campo: campo, valore: valore })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) alert('Errore salvataggio');
    });
}
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const nomeInput = document.querySelector('input[name="nome"]');
    const cognomeInput = document.querySelector('input[name="cognome"]');
    const codiceInput = document.getElementById('codice_operatore');

    if (!nomeInput || !cognomeInput || !codiceInput) return;

    const numero = codiceInput.dataset.numero;

    function aggiornaCodice() {
        const nome = nomeInput.value.trim();
        const cognome = cognomeInput.value.trim();

        if (nome && cognome) {
            const iniziali =
                nome.charAt(0).toUpperCase() +
                cognome.charAt(0).toUpperCase();

            codiceInput.value =
                iniziali + numero.toString().padStart(3, '0');
        } else {
            codiceInput.value =
                '__' + numero.toString().padStart(3, '0');
        }
    }

    nomeInput.addEventListener('input', aggiornaCodice);
    cognomeInput.addEventListener('input', aggiornaCodice);
});
</script>
@endsection