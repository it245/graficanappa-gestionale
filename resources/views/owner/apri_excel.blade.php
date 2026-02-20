@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
<style>
.excel-page {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    text-align: center;
}
.excel-page .icon {
    font-size: 64px;
    margin-bottom: 20px;
}
.excel-page h2 {
    margin-bottom: 10px;
    color: #198754;
}
.excel-page p {
    color: #555;
    margin-bottom: 24px;
    max-width: 500px;
}
.excel-page .btn-excel {
    background: #198754;
    color: #fff;
    font-size: 18px;
    padding: 14px 40px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 12px;
    transition: background 0.2s;
}
.excel-page .btn-excel:hover {
    background: #146c43;
    color: #fff;
}
.excel-page .btn-back {
    color: #666;
    text-decoration: none;
    font-size: 14px;
}
.excel-page .btn-back:hover {
    color: #333;
}
</style>

<div class="excel-page">
    <div class="icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/><polyline points="10 9 9 9 8 9"/>
        </svg>
    </div>
    <h2>Dashboard MES - Excel</h2>
    <p>Clicca il pulsante per aprire il file Excel aggiornato con tutti i dati della dashboard. Modifica e salva: le modifiche si sincronizzano automaticamente.</p>

    <a href="ms-excel:ofe|u|{{ $fileUrl }}" class="btn-excel">Apri in Excel</a>

    <br>
    <a href="{{ $fileUrl }}" class="btn-back" download>Oppure scarica il file</a>
    <br><br>
    <a href="{{ route('owner.dashboard') }}" class="btn-back">&larr; Torna alla Dashboard</a>
</div>
</div>
@endsection
