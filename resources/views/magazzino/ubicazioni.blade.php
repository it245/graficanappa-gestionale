@extends('layouts.mes')

@section('page-title', 'Ubicazioni Magazzino')
@section('topbar-title', 'Gestione Ubicazioni')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('content')
<div class="row g-4">
    {{-- Form nuova ubicazione --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="background:var(--bg-card);">
            <div class="card-header" style="background:transparent; border-bottom:1px solid var(--border-color);">
                <strong>Nuova ubicazione</strong>
            </div>
            <div class="card-body">
                <form action="{{ route('magazzino.ubicazioni.store', ['op_token' => request('op_token')]) }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small">Codice (es. A3-02)</label>
                        <input type="text" name="codice" class="form-control form-control-sm" required placeholder="A3-02">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <label class="form-label small">Corridoio</label>
                            <input type="text" name="corridoio" class="form-control form-control-sm" required placeholder="A">
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Scaffale</label>
                            <input type="text" name="scaffale" class="form-control form-control-sm" required placeholder="3">
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Piano</label>
                            <input type="text" name="piano" class="form-control form-control-sm" placeholder="02">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Note</label>
                        <input type="text" name="note" class="form-control form-control-sm">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">Salva ubicazione</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Lista ubicazioni --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="background:var(--bg-card);">
            <div class="card-header" style="background:transparent; border-bottom:1px solid var(--border-color);">
                <strong>Ubicazioni ({{ $ubicazioni->count() }})</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="color:var(--text-primary);">
                        <thead><tr>
                            <th>Codice</th><th>Corridoio</th><th>Scaffale</th><th>Piano</th><th>Note</th><th class="text-end">Giacenze</th>
                        </tr></thead>
                        <tbody>
                        @foreach($ubicazioni as $ub)
                            <tr>
                                <td><code>{{ $ub->codice }}</code></td>
                                <td>{{ $ub->corridoio }}</td>
                                <td>{{ $ub->scaffale }}</td>
                                <td>{{ $ub->piano ?? '-' }}</td>
                                <td>{{ $ub->note ?? '-' }}</td>
                                <td class="text-end">{{ $ub->giacenze->count() }}</td>
                            </tr>
                        @endforeach
                        @if($ubicazioni->isEmpty())
                            <tr><td colspan="6" class="text-center text-muted py-3">Nessuna ubicazione definita</td></tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
