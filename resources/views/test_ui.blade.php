@extends('layouts.mes')

@section('viewport')@endsection

@section('topbar-title', 'Dashboard Produzione')

@section('sidebar-items')
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Produzione</div>
    <a href="#" class="mes-sidebar-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="#" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 20h20"/><rect x="4" y="8" width="4" height="12"/><rect x="10" y="4" width="4" height="16"/><rect x="16" y="11" width="4" height="9"/></svg>
        Panoramica Reparti
    </a>
    <a href="#" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Scheduling
    </a>
    <a href="#" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Lav. Esterne
    </a>
    <a href="#" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        Fustelle
    </a>
</div>

<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Analisi</div>
    <a href="#" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Report Ore
    </a>
    <a href="#" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Fasi Terminate
        <span class="badge bg-success ms-auto" style="font-size:10px">12</span>
    </a>
    <button type="button" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Storico Consegne
    </button>
</div>

<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Strumenti</div>
    <button type="button" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        Consegnati Oggi
        <span class="badge bg-info ms-auto" style="font-size:10px">3</span>
    </button>
    <button type="button" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        BRT Tracking
        <span class="badge bg-warning text-dark ms-auto" style="font-size:10px">5</span>
    </button>
    <button type="button" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Note Consegne
        <span class="badge bg-danger ms-auto" style="font-size:10px">!</span>
    </button>
    <button type="button" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Presenti
        <span class="badge bg-success ms-auto" style="font-size:10px">8</span>
    </button>
    <button type="button" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6"/><path d="M2.5 22v-6h6"/><path d="M2.5 11.5a10 10 0 0 1 18.8-4.3"/><path d="M21.5 12.5a10 10 0 0 1-18.8 4.2"/></svg>
        Sincronizza Onda
    </button>
    <button type="button" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        Aggiungi Riga
    </button>
</div>
@endsection

@section('styles')
<style>
/* Table styles (from owner dashboard) */
#tabellaOrdini {
    width: 2970px;
    max-width: 2970px;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 12px;
}
#tabellaOrdini th, #tabellaOrdini td {
    border: 1px solid var(--border-color);
    padding: 3px 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
    white-space: normal;
    max-height: 3.9em;
}
#tabellaOrdini thead th {
    background: var(--bg-sidebar);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border: none;
    border-bottom: 2px solid var(--accent);
    padding: 8px 6px;
}
#tabellaOrdini tbody tr:hover td { background: rgba(37,99,235,0.04); }
#tabellaOrdini tbody tr:nth-child(even) td { background: rgba(0,0,0,0.015); }
tr.scaduta td { background-color: #e8747a !important; color: #000 !important; font-weight: 700; }
tr.warning-strong td { background-color: #f96f2a !important; color: #000 !important; font-weight: 700; }
tr.warning-light td { background-color: #ffd07a !important; color: #000 !important; font-weight: 700; }

/* Column widths */
#tabellaOrdini th:nth-child(1), #tabellaOrdini td:nth-child(1) { width: 100px; }
#tabellaOrdini th:nth-child(2), #tabellaOrdini td:nth-child(2) { width: 50px; text-align: center; }
#tabellaOrdini th:nth-child(3), #tabellaOrdini td:nth-child(3) { width: 170px; }
#tabellaOrdini th:nth-child(4), #tabellaOrdini td:nth-child(4) { width: 95px; }
#tabellaOrdini th:nth-child(5), #tabellaOrdini td:nth-child(5) { width: 180px; }
#tabellaOrdini th:nth-child(6), #tabellaOrdini td:nth-child(6) { width: 75px; }
#tabellaOrdini th:nth-child(7), #tabellaOrdini td:nth-child(7) { width: 250px; }
#tabellaOrdini th:nth-child(8), #tabellaOrdini td:nth-child(8) { width: 55px; text-align: center; }
#tabellaOrdini th:nth-child(9), #tabellaOrdini td:nth-child(9) { width: 40px; text-align: center; }
#tabellaOrdini th:nth-child(10), #tabellaOrdini td:nth-child(10) { width: 65px; text-align: center; }
#tabellaOrdini th:nth-child(11), #tabellaOrdini td:nth-child(11) { width: 125px; }
#tabellaOrdini th:nth-child(12), #tabellaOrdini td:nth-child(12) { width: 110px; }
#tabellaOrdini th:nth-child(13), #tabellaOrdini td:nth-child(13) { width: 190px; }
#tabellaOrdini th:nth-child(14), #tabellaOrdini td:nth-child(14) { width: 50px; text-align: center; }
#tabellaOrdini th:nth-child(15), #tabellaOrdini td:nth-child(15) { width: 100px; }
#tabellaOrdini th:nth-child(16), #tabellaOrdini td:nth-child(16) { width: 170px; }
#tabellaOrdini th:nth-child(17), #tabellaOrdini td:nth-child(17) { width: 30px; text-align: center; }
#tabellaOrdini th:nth-child(18), #tabellaOrdini td:nth-child(18) { width: 110px; }
#tabellaOrdini th:nth-child(19), #tabellaOrdini td:nth-child(19) { width: 60px; text-align: center; }
#tabellaOrdini th:nth-child(20), #tabellaOrdini td:nth-child(20) { width: 90px; }
#tabellaOrdini th:nth-child(21), #tabellaOrdini td:nth-child(21) { width: 170px; }
#tabellaOrdini th:nth-child(22), #tabellaOrdini td:nth-child(22) { width: 110px; }
#tabellaOrdini th:nth-child(23), #tabellaOrdini td:nth-child(23) { width: 110px; }
#tabellaOrdini th:nth-child(24), #tabellaOrdini td:nth-child(24) { width: 70px; text-align: center; }
#tabellaOrdini th:nth-child(25), #tabellaOrdini td:nth-child(25) { width: 70px; text-align: center; }
#tabellaOrdini th:nth-child(26), #tabellaOrdini td:nth-child(26) { width: 100px; }
#tabellaOrdini th:nth-child(27), #tabellaOrdini td:nth-child(27) { width: 100px; }

/* Filter */
#filterBox {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-start;
    margin-bottom: 12px;
}
#filterBox input { height: 38px; padding: 0.25rem 0.5rem; font-size: 0.875rem; flex: 1 1 200px; max-width: 250px; }
.btn-reset-filters {
    background: var(--danger); color: #fff; border: none; padding: 6px 14px;
    border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer; height: 38px;
}
</style>
@endsection

@section('content')

{{-- KPI Cards --}}
<div class="d-flex gap-3 mb-4 flex-wrap">
    <x-mes.kpi-card value="12" label="Fasi completate oggi" color="success" />
    <x-mes.kpi-card value="8.5h" label="Ore lavorate oggi" color="accent" subtitle="click per dettaglio" />
    <x-mes.kpi-card value="3" label="Spedite oggi" color="info" />
    <x-mes.kpi-card value="7" label="Fasi in lavorazione" color="warning" />
</div>

{{-- Filtri --}}
<div id="filterBox">
    <input type="text" class="form-control form-control-sm" placeholder="Filtra Commessa">
    <input type="text" class="form-control form-control-sm" placeholder="Filtra Cliente">
    <input type="text" class="form-control form-control-sm" placeholder="Filtra Descrizione" style="max-width:300px;">
    <button class="btn-reset-filters">Rimuovi filtri</button>
</div>

{{-- Tabella --}}
<div style="width:100%; overflow-x:auto;">
    <table id="tabellaOrdini" style="white-space:nowrap;">
        <thead>
            <tr>
                <th>Commessa</th><th>Stato</th><th>Cliente</th><th>Codice Articolo</th>
                <th>Colori</th><th>Fustella</th><th>Descrizione</th><th>Qta</th>
                <th>UM</th><th>Priorita</th><th>Fase</th><th>Reparto</th>
                <th>Carta</th><th>Qta Carta</th><th>Data Consegna</th><th>Cod Carta</th>
                <th>UM</th><th>Operatori</th><th>Qta Prod.</th><th>Esterno</th>
                <th>Note</th><th>Data Inizio</th><th>Data Fine</th><th>Ore Prev.</th>
                <th>Ore Lav.</th><th>Data Reg.</th><th>Progresso</th>
            </tr>
        </thead>
        <tbody>
            @php
            $demoData = [
                ['0066792-26', 2, 'CAPOBIANCO SRL', 'ART-001', '4/0 CMYK', 'FS-4521', 'Scatola pieghevole 350g', '5.000', 'FG', '12.50', 'STAMPA XL 106', 'stampa offset', 'Carta Patinata Opaca 350g', '5.200', '22/03/2026', 'CP350OPA', 'fg', 'Marco R.', '5.100', '-', 'Urgente, cliente importante', '19/03/2026 08:30', '-', '2h 30m', '1h 45m P', '15/03/2026', 75],
                ['0066785-26', 3, 'VILLA RAIANO', 'ART-002', '5/0 CMYK+Pantone', 'FS-3201', 'Etichetta vino Fiano 2025', '12.000', 'FG', '8.30', 'PLASTIFICA OPACA', 'plastica', 'Carta Adesiva Wine 80g', '12.500', '20/03/2026', 'CA80WIN', 'fg', 'Antonio B.', '12.400', '-', '', '18/03/2026 14:00', '19/03/2026 10:30', '1h 15m', '1h 10m', '12/03/2026', 100],
                ['0066788-26', 1, 'FERRARELLE SPA', 'ART-003', '6/0', '-', 'Espositore da banco 4mm', '500', 'PZ', '15.20', 'FUST BOBST 75X106', 'fustella piana', 'Cartone Teso 4mm', '520', '25/03/2026', 'CT4MM', 'fg', '-', '-', 'Plastisud SRL', 'Fustella nuova da ordinare', '-', '-', '3h 00m', '-', '10/03/2026', 30],
                ['0066801-26', 0, 'KIMBO SPA', 'ART-004', '4/4 CMYK', 'FS-1122', 'Astuccio caffe 250g', '20.000', 'FG', '5.10', 'STAMPA XL 106', 'stampa offset', 'Carta Patinata Lucida 300g', '20.800', '28/03/2026', 'CP300LUC', 'fg', '-', '-', '-', '', '-', '-', '4h 30m', '-', '18/03/2026', 0],
                ['0066650-26', 2, 'GAROFALO PASTA', 'ART-005', '4/0 CMYK', 'FS-8877', 'Scatola pasta premium 500g', '8.000', 'FG', '3.70', 'PIEGA INCOLLA', 'piegaincolla', 'Carta Patinata Opaca 300g', '8.200', '15/03/2026', 'CP300OPA', 'fg', 'Luigi V.', '8.100', '-', 'Attendere plastica', '17/03/2026 06:00', '-', '1h 45m', '0h 55m', '05/03/2026', 60],
                ['0066810-26', 2, 'HARIBO ITALIA', 'ART-006', '5/0 CMYK+Gold', '-', 'Display stand Goldbaren', '200', 'PZ', '18.90', 'STAMPA A CALDO JOH', 'stampa a caldo', 'Cartoncino Extra 400g', '250', '21/03/2026', 'CE400EX', 'fg', 'Benito M.', '190', '-', '', '19/03/2026 07:00', '-', '5h 00m', '2h 30m P', '14/03/2026', 45],
                ['0066555-26', 2, 'RUMMO SPA', 'ART-007', '4/0', 'FS-6655', 'Scatola Linguine Bio 500g', '15.000', 'FG', '2.20', 'ALLESTIMENTO', 'allestimento', 'Cartoncino GC2 280g', '15.500', '12/03/2026', 'GC2-280', 'fg', 'Rosa C., Maria T.', '14.800', '-', '', '16/03/2026 08:00', '-', '2h 00m', '1h 20m', '01/03/2026', 85],
            ];
            @endphp
            @foreach($demoData as $row)
            @php
                $rowClass = '';
                if ($row[26] === 85) $rowClass = 'scaduta';
                elseif ($row[26] === 60) $rowClass = 'warning-strong';
                elseif ($row[26] === 45) $rowClass = 'warning-light';

                $statoBg = [0 => '#e9ecef', 1 => '#dbeafe', 2 => '#fef3c7', 3 => '#d1fae5', 4 => '#d1d5db'];
            @endphp
            <tr class="{{ $rowClass }}">
                <td><a href="#" style="color:var(--text-primary);font-weight:bold;text-decoration:underline;">{{ $row[0] }}</a></td>
                <td style="background:{{ $statoBg[$row[1]] ?? '#e9ecef' }} !important;font-weight:bold;text-align:center;">{{ $row[1] }}</td>
                <td>{{ $row[2] }}</td>
                <td>{{ $row[3] }}</td>
                <td>{{ $row[4] }}</td>
                <td>{{ $row[5] }}</td>
                <td>{{ $row[6] }}</td>
                <td style="text-align:center">{{ $row[7] }}</td>
                <td style="text-align:center">{{ $row[8] }}</td>
                <td style="text-align:center">{{ $row[9] }}</td>
                <td>{{ $row[10] }}</td>
                <td>{{ $row[11] }}</td>
                <td>{{ $row[12] }}</td>
                <td style="text-align:center">{{ $row[13] }}</td>
                <td>{{ $row[14] }}</td>
                <td>{{ $row[15] }}</td>
                <td style="text-align:center;font-weight:600;color:#059669">{{ $row[16] }}</td>
                <td>{{ $row[17] }}</td>
                <td style="text-align:center">{{ $row[18] }}</td>
                <td>{{ $row[19] }}</td>
                <td>{{ $row[20] }}</td>
                <td>{{ $row[21] }}</td>
                <td>{{ $row[22] }}</td>
                <td style="text-align:center">{{ $row[23] }}</td>
                <td style="text-align:center">{{ $row[24] }}</td>
                <td>{{ $row[25] }}</td>
                <td style="padding:2px 4px; vertical-align:middle;">
                    <x-mes.progress-bar :percentuale="$row[26]" :avviate="10" :totale="8" :terminate="round($row[26]*8/100)" />
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
