@extends('layouts.costi')

@section('content')
<link rel="stylesheet" href="{{ asset('css/costi-ui.css') }}?v={{ filemtime(public_path('css/costi-ui.css')) }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="gn-page">
    <div style="margin-bottom:14px;">
        <a href="{{ route('owner.costi.analisi.index') }}?op_token={{ request('op_token') }}" class="gn-btn gn-btn-secondary gn-btn-sm">← Torna alla lista</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr auto auto;gap:24px;align-items:flex-start;margin-bottom:20px;">
        <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <h1 style="margin:0;">Commessa {{ $commessa }}</h1>
                <span class="gn-stato-completata">Completata</span>
            </div>
            <div style="margin-top:6px;font-size:13px;color:var(--gn-muted);">
                Cliente: <strong style="color:var(--gn-text);">{{ $cliente }}</strong> · {{ $descrizione }}
            </div>
        </div>
        <div style="text-align:right;font-size:12px;color:var(--gn-muted);white-space:nowrap;">
            <div>Q.tà prodotta</div>
            <div style="font-size:18px;font-weight:700;color:var(--gn-text);">{{ number_format($qta_richiesta, 0, ',', '.') }} pz</div>
            <div style="margin-top:6px;">Consegna</div>
            <div style="font-size:14px;font-weight:600;color:var(--gn-text);">{{ $data_consegna ? \Carbon\Carbon::parse($data_consegna)->format('d/m/Y') : '-' }}</div>
        </div>
        <div>
            <a href="{{ route('owner.costi.analisi.pdf', $commessa) }}?op_token={{ request('op_token') }}" target="_blank" class="gn-btn gn-btn-primary" style="white-space:nowrap;">📄 PDF</a>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:14px;">{{ session('success') }}</div>
    @endif

    {{-- Riga 1: Ore reparto + Produzione + Lavorazioni esterne --}}
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">

        {{-- Ore lavorate --}}
        <div class="gn-card">
            <div class="gn-card-header"><h3>Ore lavorate per reparto</h3></div>
            <table class="gn-table">
                <thead><tr><th>Reparto</th><th class="num">Ore (h:m)</th></tr></thead>
                <tbody>
                @forelse($oreReparto as $r)
                    <tr><td>{{ $r->reparto }}</td><td class="num">{{ $r->ore_hm }}</td></tr>
                @empty
                    <tr><td colspan="2" style="color:var(--gn-muted);">Nessuna ora registrata.</td></tr>
                @endforelse
                @if($spedizioneStato)
                    <tr>
                        <td>spedizione</td>
                        <td class="num">
                            @if($spedizioneStato === 'totale')
                                <span class="gn-badge gn-badge-success">Totale</span>
                            @else
                                <span class="gn-badge gn-badge-warning">Parziale</span>
                            @endif
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>

        {{-- Produzione --}}
        <div class="gn-card">
            <div class="gn-card-header"><h3>Produzione</h3>
                @if($override)<span class="gn-badge gn-badge-warning">override</span>@endif
            </div>
            <form method="POST" action="{{ route('owner.costi.analisi.updateOverride', $commessa) }}" class="gn-card-body">
                @csrf
                <div style="display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center;">
                    <label style="font-size:13px;">Fogli prodotti ({{ $faseStampaNome }})</label>
                    <input type="number" min="0" step="1" name="fogli_utilizzati" value="{{ $fogliUtilizzati ?: '' }}" placeholder="auto" style="width:110px;padding:6px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;text-align:right;">

                    <label style="font-size:13px;">Tiri eseguiti</label>
                    <input type="number" min="0" step="0.01" name="tiri_cm_foil" value="{{ $tiriTotali ?: '' }}" placeholder="auto" style="width:110px;padding:6px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;text-align:right;">

                    <label style="font-size:13px;">Inchiostro utilizzato (g)</label>
                    <input type="number" min="0" step="0.01" name="inchiostro_g" value="{{ $inchiostroTotale ?: '' }}" placeholder="auto" style="width:110px;padding:6px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;text-align:right;">

                    <label style="font-size:13px;">Scarti (%)</label>
                    <input type="number" min="0" step="1" name="scarti_fogli" value="{{ $scartiTotali ?: '' }}" placeholder="auto" style="width:110px;padding:6px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;text-align:right;">
                </div>
                <div style="display:flex;gap:8px;margin-top:14px;">
                    <button class="gn-btn gn-btn-primary gn-btn-sm">💾 Salva override</button>
                    @if($override)
                    <button type="button" class="gn-btn gn-btn-secondary gn-btn-sm" onclick="document.getElementById('formDelOverride').submit();">↺ Rimuovi override</button>
                    @endif
                </div>
            </form>
            @if($override)
            <form id="formDelOverride" method="POST" action="{{ route('owner.costi.analisi.deleteOverride', $commessa) }}" style="display:none;">@csrf @method('DELETE')</form>
            @endif
        </div>

        {{-- Lavorazioni esterne --}}
        <div class="gn-card gn-card-warning">
            <div class="gn-card-header"><h3>⚠️ Lavorazioni esterne</h3></div>
            @if($lavorazioniEsterne->isNotEmpty())
            <table class="gn-table" style="font-size:12px;">
                <thead><tr><th>Fase</th><th>Fornitore</th><th class="num">Costo €</th></tr></thead>
                <tbody>
                @foreach($lavorazioniEsterne as $le)
                    <tr>
                        <td><strong>{{ $le->fase }}</strong></td>
                        <td>{{ $le->fornitore }}<br><small class="gn-badge gn-badge-info">Inviato</small></td>
                        <td class="num">—</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <div class="gn-card-body" style="color:var(--gn-muted);font-size:13px;">Nessuna lavorazione esterna.</div>
            @endif
        </div>

    </div>

    {{-- Riga 2: Costi consuntivo (full width) --}}
    <div class="gn-card gn-card-primary">
        <div class="gn-card-header">
            <h3 style="color:#fff;">Costi consuntivo dettagliato</h3>
            <div class="gn-card-actions">
                <a href="{{ route('owner.costi.analisi.pdf', $commessa) }}?op_token={{ request('op_token') }}" target="_blank" class="gn-btn gn-btn-secondary gn-btn-sm">📄 PDF</a>
                <span style="color:#fff;font-size:14px;margin-left:10px;">Totale: <strong>€ {{ number_format($totaleConsuntivo, 2, ',', '.') }}</strong></span>
            </div>
        </div>
        @if(!empty($vociCosto))
        <table class="gn-table">
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Descrizione</th>
                    <th class="num">Q.tà</th>
                    <th>U.M.</th>
                    <th class="num">€/UNIT</th>
                    <th class="num">Importo €</th>
                    <th style="width:110px;">Azioni</th>
                </tr>
            </thead>
            <tbody>
            @foreach($vociCosto as $v)
                <tr class="voce-riga {{ $v['override_manuale'] ? 'gn-override' : '' }}"
                    data-voce-chiave="{{ $v['voce_chiave'] }}"
                    data-categoria="{{ $v['categoria'] }}"
                    data-descrizione="{{ $v['descrizione'] }}"
                    data-udm="{{ $v['udm'] ?? '' }}">
                    <td><span class="gn-badge gn-badge-{{ $v['categoria'] }}">{{ $v['categoria'] }}</span></td>
                    <td style="font-size:13px;">{{ $v['descrizione'] }}@if($v['override_manuale'])<span class="gn-badge gn-badge-warning" style="margin-left:6px;">M</span>@endif</td>
                    <td><input type="number" step="0.01" min="0" value="{{ $v['qta'] !== null ? number_format($v['qta'], 2, '.', '') : '' }}" class="voce-qta"></td>
                    <td style="font-size:12px;color:var(--gn-muted);">{{ $v['udm'] ?? '' }}</td>
                    <td><input type="number" step="0.0001" min="0" value="{{ $v['prezzo_unit'] !== null ? number_format($v['prezzo_unit'], 4, '.', '') : '' }}" class="voce-prezzo"></td>
                    <td><input type="number" step="0.01" min="0" value="{{ number_format($v['importo'], 2, '.', '') }}" class="voce-importo" style="font-weight:600;color:var(--gn-text);"></td>
                    <td>
                        <button class="gn-btn gn-btn-primary gn-btn-icon btn-salva-voce" title="Salva">💾</button>
                        @if($v['override_manuale'])
                        <form method="POST" action="{{ route('owner.costi.analisi.deleteVoce', $commessa) }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="voce_chiave" value="{{ $v['voce_chiave'] }}">
                            <button class="gn-btn gn-btn-secondary gn-btn-icon" title="Reset auto" onclick="return confirm('Ripristinare valore automatico?')">↺</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="5" style="text-align:right;">TOTALE CONSUNTIVO COMMESSA</td><td class="num">€ {{ number_format($totaleConsuntivo, 2, ',', '.') }}</td><td></td></tr>
            </tfoot>
        </table>
        @else
        <div class="gn-card-body" style="background:#fff;color:var(--gn-muted);">Nessuna voce calcolata.</div>
        @endif
    </div>

    {{-- Altri costi --}}
    <div class="gn-card">
        <div class="gn-card-header">
            <h3>Altri costi</h3>
            <button type="button" class="gn-btn gn-btn-success gn-btn-sm" onclick="document.getElementById('formAltroCosto').style.display='block';">+ Aggiungi costo</button>
        </div>

        <div id="formAltroCosto" style="display:none;padding:14px;background:#f9fafb;border-bottom:1px solid var(--gn-border);">
            <form method="POST" action="{{ route('owner.costi.analisi.storeAltro', $commessa) }}">
                @csrf
                <div style="display:grid;grid-template-columns:140px 200px 1fr 140px 100px;gap:10px;">
                    <input type="date" name="data" value="{{ now()->toDateString() }}" required style="padding:7px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
                    <select name="categoria" required style="padding:7px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
                        @foreach($categorie as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="descrizione" placeholder="Descrizione" maxlength="500" style="padding:7px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
                    <input type="number" step="0.01" min="0" name="importo" placeholder="Importo €" style="padding:7px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;text-align:right;">
                    <button class="gn-btn gn-btn-primary gn-btn-sm">+ Aggiungi costo</button>
                </div>
            </form>
        </div>

        <table class="gn-table">
            <thead><tr><th>Data</th><th>Categoria</th><th>Descrizione</th><th class="num">Importo €</th><th></th></tr></thead>
            <tbody>
            @forelse($altriCosti as $c)
                <tr>
                    <td>{{ $c->data->format('d/m/Y') }}</td>
                    <td><span class="gn-badge gn-badge-{{ $c->categoria }}">{{ $c->categoriaLabel() }}</span></td>
                    <td>{{ $c->descrizione ?? '—' }}</td>
                    <td class="num">€ {{ number_format($c->importo, 2, ',', '.') }}</td>
                    <td>
                        <form method="POST" action="{{ route('owner.costi.analisi.deleteAltro', $c->id) }}" onsubmit="return confirm('Eliminare?')" style="display:inline;">@csrf @method('DELETE')<button class="gn-btn gn-btn-secondary gn-btn-icon">×</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:var(--gn-muted);padding:24px;">Nessun costo registrato.</td></tr>
            @endforelse
            </tbody>
            @if($altriCosti->isNotEmpty())
            <tfoot><tr><td colspan="3" style="text-align:right;">TOTALE ALTRI COSTI</td><td class="num">€ {{ number_format($totaleAltriCosti, 2, ',', '.') }}</td><td></td></tr></tfoot>
            @endif
        </table>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlSalva = "{{ route('owner.costi.analisi.updateVoce', $commessa) }}";
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    document.querySelectorAll('.voce-riga').forEach(function(tr) {
        const qta = tr.querySelector('.voce-qta');
        const prezzo = tr.querySelector('.voce-prezzo');
        const importo = tr.querySelector('.voce-importo');
        const btn = tr.querySelector('.btn-salva-voce');
        const recalc = function() {
            const q = parseFloat((qta.value || '0').replace(',', '.'));
            const p = parseFloat((prezzo.value || '0').replace(',', '.'));
            if (!isNaN(q) && !isNaN(p) && q > 0 && p > 0) importo.value = (q * p).toFixed(2);
        };
        if (qta) qta.addEventListener('input', recalc);
        if (prezzo) prezzo.addEventListener('input', recalc);
        if (btn) btn.addEventListener('click', function() {
            const fd = new FormData();
            fd.append('_token', csrf);
            fd.append('voce_chiave', tr.dataset.voceChiave);
            fd.append('categoria', tr.dataset.categoria);
            fd.append('descrizione', tr.dataset.descrizione);
            fd.append('udm', tr.dataset.udm || '');
            fd.append('qta', qta.value || '');
            fd.append('prezzo_unit', prezzo.value || '');
            fd.append('importo', importo.value || '0');
            btn.disabled = true; btn.textContent = '⏳';
            fetch(urlSalva, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function() { location.reload(); })
                .catch(function() { btn.disabled = false; btn.textContent = '💾'; });
        });
    });
});
</script>
@endsection
