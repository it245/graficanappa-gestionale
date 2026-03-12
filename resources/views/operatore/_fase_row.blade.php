@php
    $rowClass = '';
    if ($fase->ordine && $fase->ordine->data_prevista_consegna) {
        $oggi = \Carbon\Carbon::today();
        $dataPrevista = \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna);
        $diff = $oggi->diffInDays($dataPrevista, false);
        if ($diff < -5) $rowClass = 'scaduta';
        elseif ($diff <= 3) $rowClass = 'warning-strong';
        elseif ($diff <= 5) $rowClass = 'warning-light';
    }
    $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd'];
@endphp

<tr id="fase-{{ $fase->id }}" class="{{ $rowClass }}">
    <td>{{ $fase->priorita !== null ? number_format($fase->priorita, 2) : '-' }}</td>
    <td id="operatore-{{ $fase->id }}">
        @foreach($fase->operatori as $op)
            {{ $op->nome }} ({{ $op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-' }})<br>
        @endforeach
    </td>
    <td class="td-fase">{{ $fase->faseCatalogo->nome_display ?? '-' }}</td>
    <td class="td-stato" id="stato-{{ $fase->id }}" style="background:{{ $statoBg[$fase->stato] ?? '#e9ecef' }};font-weight:bold;text-align:center;">
        {{ $fase->stato }}
        @if($fase->stato == 3 && $fase->data_fine && \Carbon\Carbon::parse($fase->data_fine)->gt(now()->subHour()))
            <br><small style="font-weight:normal; color:#dc3545;">Inserisci scarti</small>
        @endif
    </td>

    {{-- COMMESSA CLICCABILE --}}
    <td>
        <a href="{{ route('commesse.show', $fase->ordine->commessa) }}?fase={{ $fase->id }}" class="commessa-link"
           style="font-weight:bold">
           {{ $fase->ordine->commessa }}
        </a>
        @php $repNomeEtichetta = strtolower(optional(optional($fase->faseCatalogo)->reparto)->nome ?? ''); @endphp
        @if(!in_array($repNomeEtichetta, ['digitale', 'finitura digitale']))
        <a href="{{ route('operatore.etichetta', $fase->ordine->id) }}" class="ms-1"
           title="Stampa etichetta" style="text-decoration:none;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#6c757d" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg></a>
        @endif
    </td>

    <td>{{ $fase->ordine->data_registrazione ? \Carbon\Carbon::parse($fase->ordine->data_registrazione)->format('d/m/Y') : '-' }}</td>
    <td class="td-cliente">{{ $fase->ordine->cliente_nome ?? '-' }}</td>
    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
    @if($showColori ?? false)<td>{{ $fase->colori ?? '-' }}</td>@endif
    @if($showFustella ?? false)<td>{{ $fase->fustella_codice ?? '-' }}</td>@endif
    @if($showEsterno ?? false)<td>{{ $fase->fornitore_esterno ?? '-' }}</td>@endif
    <td class="descrizione td-descrizione">{{ $fase->ordine->descrizione ?? '-' }}</td>
    <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
    <td>{{ $fase->ordine->um ?? '-' }}</td>
    <td>{{ $fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
    <td>
        <input type="number" min="0" style="width:70px; padding:2px 4px; font-size:13px; border:1px solid #ced4da; border-radius:3px;"
               value="{{ $fase->qta_prod ?? '' }}"
               onchange="salvaQtaProd({{ $fase->id }}, this.value)"
               onkeydown="if(event.key==='Enter'){this.blur();}">
    </td>
    @if($showScarti ?? false)
    <td>
        <input type="number" min="0" style="width:70px; padding:2px 4px; font-size:13px; border:1px solid #ced4da; border-radius:3px;"
               value="{{ $fase->scarti ?? '' }}"
               onchange="salvaScarti({{ $fase->id }}, this.value)"
               onkeydown="if(event.key==='Enter'){this.blur();}">
    </td>
    <td style="text-align:center; font-weight:bold; color:#6c757d;">
        {{ $fase->fogli_scarto ?? '-' }}
    </td>
    @endif
    <td>{{ $fase->ordine->cod_carta ?? '-' }}</td>
    <td>{{ $fase->ordine->carta ?? '-' }}</td>
    <td>{{ $fase->ordine->qta_carta ?? '-' }}</td>
    <td>{{ $fase->ordine->UM_carta ?? '-' }}</td>
    <td>
        @php
            $nfs = $fase->ordine->note_fasi_successive ?? '';
            $righeNfs = $nfs ? json_decode($nfs, true) : [];
            $noteBase = $fase->note_pulita ?? $fase->note ?? '';
        @endphp
        @if(!empty($righeNfs) && is_array($righeNfs))
            @foreach($righeNfs as $r)
                <strong>{{ $r['nome'] ?? '' }}</strong>: {{ $r['testo'] ?? '' }}@if(!$loop->last) — @endif
            @endforeach
            @if($noteBase)<br>{{ $noteBase }}@endif
        @else
            {{ $noteBase ?: '-' }}
        @endif
    </td>
    <td id="timeout-{{ $fase->id }}">{{ $fase->timeout ?? '-' }}</td>
</tr>
