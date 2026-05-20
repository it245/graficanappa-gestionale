@extends('layouts.costi')

@section('content')
<link rel="stylesheet" href="{{ asset('css/costi-ui.css') }}?v={{ filemtime(public_path('css/costi-ui.css')) }}">

<div class="gn-page">
    <a href="{{ route('owner.analisi.custom.index') }}?op_token={{ request('op_token') }}" style="font-size:13px;color:var(--gn-primary);text-decoration:none;">← Torna alla lista</a>

    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin:8px 0 24px 0;">
        <div>
            <h1>{{ $analisi->nome }}</h1>
            <div class="gn-subtitle" style="margin:0;">{{ $analisi->descrizione ?? 'Nessuna descrizione' }}</div>
        </div>
        <div style="text-align:right;font-size:12px;color:var(--gn-muted);">
            <div>Creato da</div>
            <div style="font-size:14px;font-weight:600;color:var(--gn-text);">{{ $analisi->autore }}</div>
            <div style="margin-top:6px;">Ultimo accesso</div>
            <div style="font-size:13px;color:var(--gn-text);">{{ $analisi->ultimo_accesso?->format('d/m/Y H:i') ?? '-' }}</div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('owner.analisi.custom.pdf', $analisi->id) }}?op_token={{ request('op_token') }}" target="_blank" class="gn-btn gn-btn-primary">📄 PDF</a>
            <a href="{{ route('owner.analisi.custom.excel', $analisi->id) }}?op_token={{ request('op_token') }}" class="gn-btn gn-btn-secondary">📊 CSV</a>
            <form method="POST" action="{{ route('owner.analisi.custom.duplica', $analisi->id) }}" style="display:inline;">
                @csrf
                <button class="gn-btn gn-btn-secondary" title="Duplica analisi con tutte le commesse">📋 Duplica</button>
            </form>
            <form method="POST" action="{{ route('owner.analisi.custom.destroy', $analisi->id) }}" onsubmit="return confirm('Eliminare analisi?')" style="display:inline;">
                @csrf @method('DELETE')
                <button class="gn-btn gn-btn-secondary">🗑 Elimina</button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:14px;">{{ session('success') }}</div>
    @endif

    {{-- KPI grandi --}}
    <div class="gn-kpi-grid">
        <div class="gn-kpi">
            <div class="gn-kpi-icon blue">📋</div>
            <div class="gn-kpi-body">
                <div class="gn-kpi-value">{{ count($datiCommesse) }}</div>
                <div class="gn-kpi-label">Commesse</div>
                <div class="gn-kpi-sub">incluse nell'analisi</div>
            </div>
        </div>
        <div class="gn-kpi">
            <div class="gn-kpi-icon green">💰</div>
            <div class="gn-kpi-body">
                <div class="gn-kpi-value">€ {{ number_format($totaleGenerale, 2, ',', '.') }}</div>
                <div class="gn-kpi-label">Totale costi</div>
                <div class="gn-kpi-sub">consuntivo totale</div>
            </div>
        </div>
        <div class="gn-kpi">
            <div class="gn-kpi-icon amber">📊</div>
            <div class="gn-kpi-body">
                <div class="gn-kpi-value">€ {{ count($datiCommesse) > 0 ? number_format($totaleGenerale / count($datiCommesse), 2, ',', '.') : '0,00' }}</div>
                <div class="gn-kpi-label">Costo medio</div>
                <div class="gn-kpi-sub">per commessa</div>
            </div>
        </div>
        <div class="gn-kpi">
            <div class="gn-kpi-icon purple">🏷️</div>
            <div class="gn-kpi-body">
                <div class="gn-kpi-value">{{ count($categorieTot) }}</div>
                <div class="gn-kpi-label">Voci categoria</div>
                <div class="gn-kpi-sub">categorie analizzate</div>
            </div>
        </div>
    </div>

    {{-- Row: Aggiungi + Distribuzione --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">

        <div class="gn-card">
            <div class="gn-card-header"><h3>Aggiungi commessa all'analisi</h3></div>
            <div class="gn-card-body">
                <form method="POST" action="{{ route('owner.analisi.custom.aggiungi', $analisi->id) }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;">
                        <div style="position:relative;">
                            <label style="font-size:12px;color:var(--gn-muted);">Commessa</label>
                            <input type="text" name="commessa" id="inpCommessa" autocomplete="off" placeholder="Es. 0067200-26" required style="width:100%;padding:8px 12px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
                            <div id="autocompleteCommesse" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--gn-border);border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.1);max-height:280px;overflow-y:auto;z-index:100;margin-top:2px;"></div>
                        </div>
                        <div>
                            <label style="font-size:12px;color:var(--gn-muted);">Etichetta custom (opzionale)</label>
                            <input type="text" name="etichetta" placeholder="Es. Scatola Luxury" style="width:100%;padding:8px 12px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
                        </div>
                        <div style="display:flex;align-items:flex-end;">
                            <button class="gn-btn gn-btn-primary">+ Aggiungi</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="gn-card">
            <div class="gn-card-header"><h3>Distribuzione costi per categoria</h3></div>
            <div class="gn-card-body">
                @forelse($categorieTot as $cat => $val)
                @php $pct = $totaleGenerale > 0 ? $val / $totaleGenerale * 100 : 0; @endphp
                <div class="gn-dist-bar">
                    <div class="gn-dist-label"><span class="gn-badge gn-badge-{{ $cat }}">{{ $cat }}</span></div>
                    <div class="gn-dist-value">€ {{ number_format($val, 2, ',', '.') }}</div>
                    <div class="gn-dist-track"><div class="gn-dist-fill" style="width:{{ $pct }}%;"></div></div>
                    <div class="gn-dist-pct">{{ number_format($pct, 1, ',', '.') }}%</div>
                </div>
                @empty
                <div style="color:var(--gn-muted);text-align:center;padding:24px;font-size:13px;">Nessuna commessa per calcolare distribuzione.</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- #10 Voci custom ad-hoc analisi --}}
    <div class="gn-card" style="border-color:#a78bfa;">
        <div class="gn-card-header" style="background:#f5f3ff;">
            <h3>🏷️ Voci custom (ad-hoc analisi)</h3>
            <span style="font-size:12px;color:var(--gn-muted);">Aggiunte direttamente all'analisi, non a una commessa specifica</span>
        </div>
        <div class="gn-card-body">
            <form method="POST" action="{{ route('owner.analisi.custom.voceCustom', $analisi->id) }}" style="display:grid;grid-template-columns:2fr 1fr auto;gap:10px;margin-bottom:14px;">
                @csrf
                <input type="text" name="descrizione" placeholder="Es. Sconto cliente, Bonus puntualità, Spese commerciali" required style="padding:8px 12px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
                <input type="number" step="0.01" name="importo" placeholder="Importo € (negativo per sconti)" required style="padding:8px 12px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;text-align:right;font-family:monospace;">
                <button class="gn-btn gn-btn-primary">+ Aggiungi voce</button>
            </form>

            @if(!empty($vociCustom))
            <table class="gn-table">
                <thead><tr><th>Descrizione</th><th>Autore</th><th>Data</th><th class="num">Importo €</th><th></th></tr></thead>
                <tbody>
                @foreach($vociCustom as $v)
                <tr>
                    <td><strong>{{ $v['descrizione'] }}</strong></td>
                    <td>{{ $v['autore'] ?? '-' }}</td>
                    <td>{{ $v['data'] ?? '-' }}</td>
                    <td class="num" style="color:{{ $v['importo'] < 0 ? '#dc2626' : '#065f46' }};font-weight:600;">€ {{ number_format($v['importo'], 2, ',', '.') }}</td>
                    <td>
                        <form method="POST" action="{{ route('owner.analisi.custom.rimuoviVoceCustom', [$analisi->id, $v['id']]) }}" onsubmit="return confirm('Rimuovere voce?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button class="gn-btn gn-btn-secondary gn-btn-icon">🗑</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <div style="color:var(--gn-muted);font-size:13px;text-align:center;padding:8px;">Nessuna voce custom. Usa form sopra per aggiungere voci ad-hoc all'analisi.</div>
            @endif
        </div>
    </div>

    {{-- Tabella commesse incluse --}}
    <div class="gn-card">
        <div class="gn-card-header"><h3>Commesse incluse nell'analisi</h3></div>
        <table class="gn-table">
            <thead>
                <tr>
                    <th>Commessa</th>
                    <th>Cliente / Descrizione</th>
                    <th>Etichetta custom</th>
                    <th class="num">Costo totale €</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
            @forelse($datiCommesse as $c)
                <tr class="{{ ($c['override_attivo'] ?? false) ? 'gn-override' : '' }}">
                    <form method="POST" action="{{ route('owner.analisi.custom.aggiornaRiga', [$analisi->id, $c['pivot_id']]) }}" id="frmRiga{{ $c['pivot_id'] }}">
                        @csrf
                        <td><a href="{{ route('owner.costi.analisi.show', $c['commessa']) }}?op_token={{ request('op_token') }}" class="gn-commessa-link" target="_blank">{{ $c['commessa'] }}</a></td>
                        <td>
                            <div>{{ $c['cliente'] }}</div>
                            <small style="color:var(--gn-muted);">{{ \Illuminate\Support\Str::limit($c['descrizione'], 80) }}</small>
                        </td>
                        <td><input type="text" name="etichetta" value="{{ $c['etichetta'] ?? '' }}" placeholder="etichetta..." style="width:130px;padding:5px 8px;border:1px solid var(--gn-border);border-radius:6px;font-size:12px;"></td>
                        <td class="num">
                            <input type="number" step="0.01" min="0" name="totale_override" value="{{ ($c['override_attivo'] ?? false) ? number_format($c['totale'], 2, '.', '') : '' }}"
                                placeholder="{{ number_format($c['totale_calc'] ?? $c['totale'], 2, '.', '') }}"
                                style="width:120px;text-align:right;font-family:monospace;padding:5px 8px;border:1px solid var(--gn-border);border-radius:6px;font-size:12px;font-weight:600;">
                            @if($c['override_attivo'] ?? false)
                                <div style="font-size:10px;color:#9a3412;">override (calc: {{ number_format($c['totale_calc'], 2, ',', '.') }})</div>
                            @endif
                        </td>
                        <td>
                            <button class="gn-btn gn-btn-primary gn-btn-icon" title="Salva">💾</button>
                            <a href="{{ route('owner.costi.analisi.show', $c['commessa']) }}?op_token={{ request('op_token') }}" target="_blank" class="gn-btn gn-btn-secondary gn-btn-icon" title="Apri dettaglio">↗</a>
                    </form>
                            <form method="POST" action="{{ route('owner.analisi.custom.rimuovi', [$analisi->id, $c['pivot_id']]) }}" onsubmit="return confirm('Rimuovere?')" style="display:inline;">@csrf @method('DELETE')<button class="gn-btn gn-btn-secondary gn-btn-icon">🗑</button></form>
                        </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:var(--gn-muted);padding:32px;">Nessuna commessa aggiunta. Usa il form sopra.</td></tr>
            @endforelse
            </tbody>
            @if(!empty($datiCommesse))
            <tfoot><tr><td colspan="3" style="text-align:right;">TOTALE ANALISI</td><td class="num">€ {{ number_format($totaleGenerale, 2, ',', '.') }}</td><td></td></tr></tfoot>
            @endif
        </table>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inp = document.getElementById('inpCommessa');
    const box = document.getElementById('autocompleteCommesse');
    if (!inp || !box) return;
    const url = "{{ route('owner.analisi.custom.searchCommesse') }}";
    let timer = null;

    inp.addEventListener('input', function() {
        clearTimeout(timer);
        const q = inp.value.trim();
        if (q.length < 2) { box.style.display = 'none'; return; }
        timer = setTimeout(function() {
            fetch(url + '?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(rows => {
                    if (!rows.length) { box.innerHTML = '<div style="padding:8px 12px;color:#9ca3af;font-size:12px;">Nessun risultato</div>'; box.style.display = 'block'; return; }
                    box.innerHTML = rows.map(r =>
                        '<div class="ac-item" data-commessa="' + r.commessa + '" style="padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #f3f4f6;">' +
                        '<strong>' + r.commessa + '</strong> · ' + (r.cliente || '-') +
                        '<br><small style="color:#6b7280;">' + (r.descrizione || '').substring(0, 80) + '</small></div>'
                    ).join('');
                    box.style.display = 'block';
                    box.querySelectorAll('.ac-item').forEach(it => {
                        it.addEventListener('mouseenter', () => it.style.background = '#f3f4f6');
                        it.addEventListener('mouseleave', () => it.style.background = '');
                        it.addEventListener('click', () => { inp.value = it.dataset.commessa; box.style.display = 'none'; });
                    });
                });
        }, 250);
    });
    document.addEventListener('click', function(e) {
        if (!box.contains(e.target) && e.target !== inp) box.style.display = 'none';
    });
});
</script>
@endsection
