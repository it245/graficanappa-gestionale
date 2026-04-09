@extends('layouts.mes')

@section('page-title', 'Registra Bolla')
@section('topbar-title', 'Registra Bolla (Carico)')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('content')
<div class="row g-4">
    {{-- Step 1: Upload foto bolla per OCR --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" style="background:var(--bg-card);">
            <div class="card-header" style="background:transparent; border-bottom:1px solid var(--border-color);">
                <strong>1. Scansiona bolla</strong>
            </div>
            <div class="card-body">
                <form action="{{ route('magazzino.ocr', ['op_token' => request('op_token')]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Foto bolla fornitore</label>
                        <input type="file" name="foto_bolla" class="form-control" accept="image/*" capture="environment" required>
                        <div class="form-text">Scatta foto o carica immagine della bolla</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px; margin-right:4px;"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        Analizza con OCR
                    </button>
                </form>

                @if(!empty($ocrDati['ocr_raw']))
                <div class="mt-3 p-2 rounded" style="background:var(--bg-page); border:1px solid var(--border-color);">
                    <small class="text-muted d-block mb-1">Testo OCR estratto:</small>
                    <pre class="mb-0" style="font-size:11px; max-height:200px; overflow-y:auto; white-space:pre-wrap;">{{ $ocrDati['ocr_raw'] }}</pre>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Step 2: Form carico --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="background:var(--bg-card);">
            <div class="card-header" style="background:transparent; border-bottom:1px solid var(--border-color);">
                <strong>2. Conferma dati carico</strong>
            </div>
            <div class="card-body">
                <form action="{{ route('magazzino.carico.store', ['op_token' => request('op_token')]) }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        {{-- Articolo esistente O nuovo --}}
                        <div class="col-12">
                            <label class="form-label">Articolo esistente</label>
                            <select name="articolo_id" class="form-select" id="selArticolo">
                                <option value="">-- Nuovo articolo (compila sotto) --</option>
                                @foreach($articoli as $art)
                                    <option value="{{ $art->id }}">
                                        {{ $art->codice }} — {{ $art->descrizione }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Se l'articolo esiste gia, selezionalo. Altrimenti lascia vuoto e compila i campi sotto.</div>
                        </div>

                        {{-- Campi nuovo articolo (pre-compilati da OCR) --}}
                        <div id="nuovoArticoloFields">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tipo carta</label>
                                    <input type="text" name="tipo_carta" class="form-control"
                                        value="{{ old('tipo_carta', $ocrDati['tipo_carta'] ?? '') }}" placeholder="es. ALASKA PLUS GC2 FSC">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Formato</label>
                                    <input type="text" name="formato" class="form-control"
                                        value="{{ old('formato', $ocrDati['formato'] ?? '') }}" placeholder="es. 58x80">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Grammatura</label>
                                    <input type="number" name="grammatura" class="form-control"
                                        value="{{ old('grammatura', $ocrDati['grammatura'] ?? '') }}" placeholder="es. 270">
                                </div>
                            </div>
                        </div>

                        {{-- Quantita --}}
                        <div class="col-md-4">
                            <label class="form-label">Quantita</label>
                            <input type="number" name="quantita" class="form-control" min="1" required
                                value="{{ old('quantita', $ocrDati['quantita'] ?? '') }}">
                        </div>

                        {{-- Fornitore --}}
                        <div class="col-md-4">
                            <label class="form-label">Fornitore</label>
                            <input type="text" name="fornitore" class="form-control"
                                value="{{ old('fornitore', $ocrDati['fornitore'] ?? '') }}">
                        </div>

                        {{-- Lotto --}}
                        <div class="col-md-4">
                            <label class="form-label">Lotto</label>
                            <input type="text" name="lotto" class="form-control"
                                value="{{ old('lotto', $ocrDati['lotto'] ?? '') }}">
                        </div>

                        {{-- Note --}}
                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <textarea name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 mt-3">
                        Registra carico e stampa etichetta QR
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('selArticolo')?.addEventListener('change', function() {
    const fields = document.getElementById('nuovoArticoloFields');
    fields.style.display = this.value ? 'none' : 'block';
});
</script>
@endsection
