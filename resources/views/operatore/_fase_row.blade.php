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
    <td>{{ $fase->faseCatalogo->nome_display ?? '-' }}</td>
    <td id="stato-{{ $fase->id }}" style="background:{{ $statoBg[$fase->stato] ?? '#e9ecef' }};font-weight:bold;text-align:center;">{{ $fase->stato }}</td>

    {{-- COMMESSA CLICCABILE --}}
    <td>
        <a href="{{ route('commesse.show', $fase->ordine->commessa) }}?fase={{ $fase->id }}" class="commessa-link"
           style="font-weight:bold">
           {{ $fase->ordine->commessa }}
        </a>
    </td>

    <td>{{ $fase->ordine->data_registrazione ? \Carbon\Carbon::parse($fase->ordine->data_registrazione)->format('d/m/Y') : '-' }}</td>
    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
    @if($showColori ?? false)<td>{{ $fase->colori ?? '-' }}</td>@endif
    @if($showFustella ?? false)<td>{{ $fase->fustella_codice ?? '-' }}</td>@endif
    <td class="descrizione">{{ $fase->ordine->descrizione ?? '-' }}</td>
    <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
    <td>{{ $fase->ordine->um ?? '-' }}</td>
    <td>{{ $fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
    <td>{{ $fase->qta_prod ?? '-' }}</td>
    <td>{{ $fase->ordine->cod_carta ?? '-' }}</td>
    <td>{{ $fase->ordine->carta ?? '-' }}</td>
    <td>{{ $fase->ordine->qta_carta ?? '-' }}</td>
    <td>{{ $fase->ordine->UM_carta ?? '-' }}</td>
    <td>{{ $fase->note ?? '-' }}</td>
    <td id="timeout-{{ $fase->id }}">{{ $fase->timeout ?? '-' }}</td>
</tr>
