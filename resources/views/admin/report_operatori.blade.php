<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Report Operatori - Reparti - Fasi</title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; }
            .operatore-card { page-break-inside: avoid; }
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px 30px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
        }
        .header h1 { margin: 0; font-size: 22px; }
        .header p { margin: 5px 0 0; color: #666; font-size: 13px; }
        .btn-print {
            background: #0d6efd; color: #fff; border: none; padding: 10px 25px;
            border-radius: 5px; font-size: 14px; cursor: pointer; margin-bottom: 20px;
        }
        .btn-print:hover { background: #0b5ed7; }
        .btn-back {
            background: #6c757d; color: #fff; border: none; padding: 10px 25px;
            border-radius: 5px; font-size: 14px; cursor: pointer; margin-bottom: 20px;
            text-decoration: none; display: inline-block;
        }
        .operatore-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .operatore-header {
            background: #343a40;
            color: #fff;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .operatore-header .nome { font-size: 16px; font-weight: bold; }
        .operatore-header .codice {
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 13px;
        }
        .reparto-section {
            border-top: 1px solid #dee2e6;
            padding: 10px 15px;
        }
        .reparto-nome {
            font-weight: bold;
            font-size: 14px;
            color: #0d6efd;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .fasi-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .fase-badge {
            background: #e9ecef;
            border: 1px solid #ced4da;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        .no-fasi { color: #999; font-style: italic; font-size: 12px; }
        .riepilogo {
            margin-top: 30px;
            border-top: 3px solid #333;
            padding-top: 15px;
        }
        .riepilogo h2 { font-size: 18px; margin-bottom: 15px; }
        table.riepilogo-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table.riepilogo-table th, table.riepilogo-table td {
            border: 1px solid #dee2e6;
            padding: 6px 10px;
            text-align: left;
        }
        table.riepilogo-table th {
            background: #343a40;
            color: #fff;
        }
        table.riepilogo-table tr:nth-child(even) { background: #f8f9fa; }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom:15px;">
    <a href="{{ route('admin.dashboard') }}" class="btn-back">&larr; Dashboard Admin</a>
    <button class="btn-print" onclick="window.print()">Stampa / Salva PDF</button>
</div>

<div class="header">
    <h1>Grafica Nappa - Report Operatori</h1>
    <p>Operatori attivi con reparti e fasi associate &mdash; {{ now()->format('d/m/Y H:i') }}</p>
</div>

@foreach($operatori as $op)
<div class="operatore-card">
    <div class="operatore-header">
        <span class="nome">{{ $op->cognome }} {{ $op->nome }}</span>
        <span class="codice">{{ $op->codice_operatore }}</span>
    </div>
    @if($op->reparti->isEmpty())
        <div class="reparto-section">
            <span class="no-fasi">Nessun reparto assegnato</span>
        </div>
    @else
        @foreach($op->reparti->sortBy('nome') as $reparto)
        <div class="reparto-section">
            <div class="reparto-nome">{{ $reparto->nome }}</div>
            @php
                $fasi = $fasiPerReparto->get($reparto->id, collect());
            @endphp
            @if($fasi->isEmpty())
                <span class="no-fasi">Nessuna fase in catalogo</span>
            @else
                <ul class="fasi-list">
                    @foreach($fasi as $fase)
                        <li class="fase-badge">{{ $fase->nome }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
        @endforeach
    @endif
</div>
@endforeach

<div class="riepilogo">
    <h2>Riepilogo per Reparto</h2>
    <table class="riepilogo-table">
        <thead>
            <tr>
                <th>Reparto</th>
                <th>Operatori</th>
                <th>Fasi</th>
            </tr>
        </thead>
        <tbody>
            @php
                $repartiTutti = \App\Models\Reparto::orderBy('nome')->get();
            @endphp
            @foreach($repartiTutti as $rep)
            <tr>
                <td><strong>{{ strtoupper($rep->nome) }}</strong></td>
                <td>
                    @php
                        $opsReparto = $operatori->filter(fn($o) => $o->reparti->contains('id', $rep->id));
                    @endphp
                    @if($opsReparto->isEmpty())
                        <em style="color:#999;">-</em>
                    @else
                        {{ $opsReparto->map(fn($o) => $o->cognome . ' ' . $o->nome)->implode(', ') }}
                    @endif
                </td>
                <td>
                    @php $fasiRep = $fasiPerReparto->get($rep->id, collect()); @endphp
                    @if($fasiRep->isEmpty())
                        <em style="color:#999;">-</em>
                    @else
                        {{ $fasiRep->pluck('nome')->implode(', ') }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top:20px; text-align:center; color:#999; font-size:11px;">
    Generato il {{ now()->format('d/m/Y') }} alle {{ now()->format('H:i') }} &mdash; Grafica Nappa Gestionale
</div>

</body>
</html>
