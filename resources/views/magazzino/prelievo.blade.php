@extends('layouts.mes')

@section('page-title', 'Prelievo Carta')
@section('topbar-title', 'Prelievo per Produzione')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="background:var(--bg-card);">
            <div class="card-header" style="background:transparent; border-bottom:1px solid var(--border-color);">
                <strong>Prelievo carta per commessa</strong>
                <div class="form-text">Lo scarico avviene solo per fase STAMPA</div>
            </div>
            <div class="card-body">
                <form action="{{ route('magazzino.prelievo.store', ['op_token' => request('op_token')]) }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        {{-- Articolo/Giacenza --}}
                        <div class="col-12">
                            <label class="form-label">Articolo (seleziona dalla giacenza disponibile)</label>
                            <select name="articolo_id" class="form-select" required id="selGiacenza">
                                <option value="">-- Seleziona --</option>
                                @foreach($giacenze as $g)
                                    <option value="{{ $g->articolo_id }}"
                                        data-ubicazione="{{ $g->ubicazione_id }}"
                                        data-lotto="{{ $g->lotto }}"
                                        data-giacenza="{{ $g->quantita }}"
                                        {{ request('articolo_id') == $g->articolo_id ? 'selected' : '' }}>
                                        {{ $g->articolo->codice }} — {{ $g->articolo->descrizione }}
                                        ({{ number_format($g->quantita, 2, ',', '.') }} {{ $g->articolo->um }})
                                        @if($g->ubicazione) [{{ $g->ubicazione->codice }}] @endif
                                        @if($g->lotto) Lotto: {{ $g->lotto }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="ubicazione_id" id="inp-ubicazione">
                        <input type="hidden" name="lotto" id="inp-lotto">

                        {{-- Giacenza disponibile --}}
                        <div class="col-md-4">
                            <label class="form-label">Giacenza disponibile</label>
                            <div class="form-control bg-light fw-bold" id="disp-giacenza">-</div>
                        </div>

                        {{-- Quantita --}}
                        <div class="col-md-4">
                            <label class="form-label">Quantita da prelevare</label>
                            <input type="number" name="quantita" class="form-control" min="0.01" step="0.01" required>
                        </div>

                        {{-- Commessa --}}
                        <div class="col-md-4">
                            <label class="form-label">Commessa</label>
                            <input type="text" name="commessa" class="form-control" required placeholder="es. 0067007-26">
                        </div>

                        {{-- Fase --}}
                        <div class="col-md-6">
                            <label class="form-label">Fase</label>
                            <input type="text" name="fase" class="form-control" value="STAMPA" readonly>
                            <div class="form-text">Scarico carta solo per fase STAMPA</div>
                        </div>

                        {{-- Note --}}
                        <div class="col-md-6">
                            <label class="form-label">Note</label>
                            <input type="text" name="note" class="form-control">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 mt-3">Registra prelievo</button>
                </form>
            </div>
        </div>

        {{-- Link rapido scanner --}}
        <div class="text-center mt-3">
            <a href="{{ route('magazzino.scanner', ['op_token' => request('op_token')]) }}" class="btn btn-outline-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px;"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                Oppure scansiona QR bancale
            </a>
        </div>
    </div>
</div>

<script>
document.getElementById('selGiacenza')?.addEventListener('change', function() {
    const opt = this.selectedOptions[0];
    document.getElementById('inp-ubicazione').value = opt.dataset.ubicazione || '';
    document.getElementById('inp-lotto').value = opt.dataset.lotto || '';
    document.getElementById('disp-giacenza').textContent =
        opt.dataset.giacenza ? new Intl.NumberFormat('it-IT').format(opt.dataset.giacenza) : '-';
});
// Trigger on load if pre-selected
document.getElementById('selGiacenza')?.dispatchEvent(new Event('change'));
</script>
@endsection
