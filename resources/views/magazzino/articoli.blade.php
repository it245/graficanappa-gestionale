@extends('layouts.mes')

@section('page-title', 'Articoli Magazzino')
@section('topbar-title', 'Anagrafica Carta')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('content')
<div class="row g-4">
    {{-- Form nuovo articolo --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="background:var(--bg-card);">
            <div class="card-header" style="background:transparent; border-bottom:1px solid var(--border-color);">
                <strong>Nuovo articolo</strong>
            </div>
            <div class="card-body">
                <form action="{{ route('magazzino.articoli.store', ['op_token' => request('op_token')]) }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small">Codice carta</label>
                        <input type="text" name="codice" class="form-control form-control-sm" required placeholder="02W.SE.PW.300.0007">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Descrizione</label>
                        <input type="text" name="descrizione" class="form-control form-control-sm" required placeholder="GC1 PERFORMA WHITE 56x102 300g">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small">Categoria</label>
                            <select name="categoria" class="form-select form-select-sm" onchange="autoUM(this.value)">
                                <option value="">-- Seleziona --</option>
                                @foreach(\App\Models\MagazzinoArticolo::CATEGORIE as $cat)
                                    <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Formato</label>
                            <input type="text" name="formato" class="form-control form-control-sm" placeholder="56x102">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <label class="form-label small">Gramm.</label>
                            <input type="number" name="grammatura" class="form-control form-control-sm" placeholder="300">
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Spessore</label>
                            <input type="text" name="spessore" class="form-control form-control-sm" placeholder="0.475">
                        </div>
                        <div class="col-4">
                            <label class="form-label small">UM</label>
                            <select name="um" class="form-select form-select-sm">
                                <option value="fg">fg</option>
                                <option value="mq">mq</option>
                                <option value="kg">kg</option>
                                <option value="lt">lt</option>
                                <option value="mt">mt</option>
                                <option value="pz">pz</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small">Soglia minima</label>
                            <input type="number" name="soglia_minima" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Fornitore</label>
                            <input type="text" name="fornitore" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Certificazioni</label>
                        <input type="text" name="certificazioni" class="form-control form-control-sm" placeholder="FSC, alimentare">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">Salva articolo</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Lista articoli --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="background:var(--bg-card);">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:transparent; border-bottom:1px solid var(--border-color);">
                <strong>Articoli ({{ $articoli->total() }})</strong>
                <form class="d-flex gap-2" method="GET">
                    <input type="hidden" name="op_token" value="{{ request('op_token') }}">
                    <input type="text" name="cerca" class="form-control form-control-sm" placeholder="Cerca..." value="{{ request('cerca') }}" style="width:200px;">
                    <button class="btn btn-sm btn-outline-primary">Cerca</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="color:var(--text-primary);">
                        <thead><tr>
                            <th>Codice</th><th>Descrizione</th><th>Tipo</th><th>Formato</th><th>g</th><th>UM</th><th>Soglia</th><th>Fornitore</th>
                        </tr></thead>
                        <tbody>
                        @foreach($articoli as $art)
                            <tr>
                                <td><code style="font-size:11px;">{{ $art->codice }}</code></td>
                                <td>{{ Str::limit($art->descrizione, 35) }}</td>
                                <td>{{ $art->categoria ?? '-' }}</td>
                                <td>{{ $art->formato ?? '-' }}</td>
                                <td>{{ $art->grammatura ?? '-' }}</td>
                                <td>{{ $art->um }}</td>
                                <td>{{ $art->soglia_minima > 0 ? number_format($art->soglia_minima, 0, ',', '.') : '-' }}</td>
                                <td>{{ $art->fornitore ?? '-' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-2">{{ $articoli->appends(request()->query())->links() }}</div>
            </div>
        </div>
    </div>
</div>
<script>
function autoUM(categoria) {
    var umSelect = document.querySelector('select[name="um"]');
    if (!umSelect) return;
    var map = {carta:'fg', foil:'mt', scatoloni:'pz', inchiostro:'kg', vernici:'kg'};
    var um = map[categoria] || 'fg';
    // Aggiungi opzione se non esiste
    var exists = Array.from(umSelect.options).some(o => o.value === um);
    if (!exists) {
        var opt = document.createElement('option');
        opt.value = um; opt.text = um;
        umSelect.add(opt);
    }
    umSelect.value = um;
}
</script>
@endsection
