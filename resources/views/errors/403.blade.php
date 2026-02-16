<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accesso negato - MES Grafica Nappa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .error-card {
            text-align: center;
            padding: 3rem;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: #dee2e6;
            line-height: 1;
        }
        .error-msg {
            font-size: 1.2rem;
            color: #6c757d;
            margin: 1rem 0 2rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">403</div>
        <div class="error-msg">Non hai i permessi per accedere a questa pagina</div>
        <a href="{{ url('/') }}" class="btn btn-dark btn-lg">Torna alla home</a>
    </div>
</body>
</html>
