<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Operatore</title>
</head>
<body>

<h1>Dashboard Operatore</h1>

<p>Benvenuto {{ session('operatore_nome') }}</p>
<p>Reparto: {{ session('operatore_reparto') }}</p>

<hr>

<h2>Fasi visibili</h2>

@if($fasiVisibili->isEmpty())
    <p>Nessuna fase disponibile</p>
@else
    <ul>
        @foreach($fasiVisibili as $fase)
            <li>
                Ordine #{{ $fase->ordine->id ?? '-' }} –
                Fase: {{ $fase->fase_catalogo_id }}
            </li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('operatore.logout') }}">
    @csrf
    <button type="submit">Logout</button>
</form>

</body>
</html>