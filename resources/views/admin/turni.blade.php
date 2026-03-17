@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
<style>
    .turni-table { border-collapse: collapse; font-size: 13px; }
    .turni-table th, .turni-table td { border: 1px solid #dee2e6; padding: 4px 6px; text-align: center; }
    .turni-table thead th { background: #212529; color: #fff; font-size: 12px; position: sticky; top: 0; z-index: 10; }
    .turni-table td.nome { text-align: left; font-weight: 600; white-space: nowrap; background: #f8f9fa; position: sticky; left: 0; z-index: 5; }
    .turni-table input.turno-input {
        width: 40px; text-align: center; border: 1px solid transparent; background: transparent;
        font-size: 14px; font-weight: 700; padding: 2px;
    }
    .turni-table input.turno-input:focus { border-color: #0d6efd; background: #f0f7ff; outline: none; }
    .turno-T { color: #333; }
    .turno-1 { color: #0d6efd; background: #e7f1ff !important; }
    .turno-2 { color: #d63384; background: #fce4ec !important; }
    .turno-3 { color: #6f42c1; background: #f3e8ff !important; }
    .turno-F { color: #198754; background: #d1e7dd !important; }
    .turno-R { color: #dc3545; background: #f8d7da !important; }
    .saved { border-color: #198754 !important; transition: border-color 0.3s; }
    .nav-week { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .nav-week a { text-decoration: none; font-size: 20px; padding: 4px 12px; }
    .legenda { display: flex; gap: 16px; font-size: 12px; margin-bottom: 10px; flex-wrap: wrap; }
    .legenda span { display: flex; align-items: center; gap: 4px; }
    .legenda .box { width: 24px; height: 20px; border-radius: 3px; font-weight: 700; text-align: center; line-height: 20px; font-size: 12px; }
</style>

<div class="d-flex justify-content-between align-items-center mb-2 mt-2">
    <h2>Turni Settimanali</h2>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">&larr; Admin</a>
</div>

{{-- Navigazione settimane --}}
<div class="nav-week">
    <a href="{{ route('admin.turni', ['settimana' => $lunedi->copy()->subWeek()->format('Y-m-d')]) }}">&larr;</a>
    <strong>{{ $lunedi->format('d/m/Y') }} — {{ $lunedi->copy()->addDays(6)->format('d/m/Y') }}</strong>
    <a href="{{ route('admin.turni', ['settimana' => $lunedi->copy()->addWeek()->format('Y-m-d')]) }}">&rarr;</a>
    @if(!$lunedi->isCurrentWeek())
    <a href="{{ route('admin.turni') }}" class="btn btn-outline-primary btn-sm">Questa settimana</a>
    @endif
</div>

{{-- Legenda --}}
<div class="legenda">
    <span><div class="box turno-T" style="background:#f8f9fa; border:1px solid #ccc;">T</div> Turno unico (08-17)</span>
    <span><div class="box turno-1" style="background:#e7f1ff;">1</div> Turno 1 (06-14)</span>
    <span><div class="box turno-2" style="background:#fce4ec;">2</div> Turno 2 (14-22)</span>
    <span><div class="box turno-3" style="background:#f3e8ff;">3</div> Turno 3 (22-06)</span>
    <span><div class="box turno-F" style="background:#d1e7dd;">F</div> Ferie</span>
    <span><div class="box turno-R" style="background:#f8d7da;">R</div> Riposo</span>
</div>

{{-- Tabella --}}
<div style="overflow-x:auto;">
    <table class="turni-table">
        <thead>
            <tr>
                <th style="min-width:200px; text-align:left;">Dipendente</th>
                @foreach($giorni as $g)
                    @php $carbon = \Carbon\Carbon::parse($g); @endphp
                    <th style="min-width:60px;">
                        {{ $carbon->isoFormat('ddd') }}<br>
                        <span style="font-weight:400;">{{ $carbon->format('d/m') }}</span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($dipendenti as $nome)
                <tr>
                    <td class="nome">{{ $nome }}</td>
                    @foreach($giorni as $g)
                        @php
                            $turnoRecord = ($turni[$nome] ?? collect())->firstWhere('data', $g);
                            $val = $turnoRecord->turno ?? '';
                            $cls = $val ? 'turno-' . $val : '';
                        @endphp
                        <td class="{{ $cls }}">
                            <input type="text" class="turno-input {{ $cls }}" maxlength="1"
                                   value="{{ $val }}"
                                   data-nome="{{ $nome }}" data-data="{{ $g }}"
                                   onchange="salvaTurno(this)"
                                   onfocus="this.select()">
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Aggiungi dipendente --}}
<div class="mt-3 mb-4">
    <form onsubmit="aggiungiDipendente(event)" class="d-flex gap-2" style="max-width:400px;">
        <input type="text" id="nuovoDipendente" class="form-control form-control-sm" placeholder="COGNOME NOME (tutto maiuscolo)">
        <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">+ Aggiungi</button>
    </form>
</div>
</div>

<script>
function salvaTurno(input) {
    var val = input.value.toUpperCase().trim();
    input.value = val;

    // Aggiorna colore cella
    var td = input.parentElement;
    td.className = val ? 'turno-' + val : '';
    input.className = 'turno-input ' + (val ? 'turno-' + val : '');

    fetch('{{ route("admin.salvaTurno") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            cognome_nome: input.getAttribute('data-nome'),
            data: input.getAttribute('data-data'),
            turno: val
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            input.classList.add('saved');
            setTimeout(function() { input.classList.remove('saved'); }, 1000);
        } else {
            alert('Errore: ' + (d.messaggio || ''));
        }
    })
    .catch(function() { alert('Errore di connessione'); });
}

function aggiungiDipendente(e) {
    e.preventDefault();
    var nome = document.getElementById('nuovoDipendente').value.toUpperCase().trim();
    if (!nome) return;

    // Aggiungi riga vuota alla tabella
    var tbody = document.querySelector('.turni-table tbody');
    var giorni = @json($giorni);
    var tr = document.createElement('tr');
    tr.innerHTML = '<td class="nome">' + nome + '</td>' +
        giorni.map(function(g) {
            return '<td><input type="text" class="turno-input" maxlength="1" value="" data-nome="' + nome + '" data-data="' + g + '" onchange="salvaTurno(this)" onfocus="this.select()"></td>';
        }).join('');
    tbody.appendChild(tr);
    document.getElementById('nuovoDipendente').value = '';
}
</script>
@endsection
