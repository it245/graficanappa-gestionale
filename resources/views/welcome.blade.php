<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MES - Grafica Nappa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            font-family: 'Segoe UI', sans-serif;
        }
        .welcome-card {
            background: white;
            border-radius: 16px;
            padding: 3rem 2.5rem;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 420px;
            width: 100%;
        }
        .logo-text {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 0.25rem;
        }
        .subtitle {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .btn-enter {
            display: block;
            width: 100%;
            padding: 0.85rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-enter:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .btn-operatore {
            background: #0f3460;
            color: white;
            border: none;
        }
        .btn-operatore:hover {
            background: #1a1a2e;
            color: white;
        }
        .footer-text {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: #adb5bd;
        }
    </style>
</head>
<body>
    <div class="welcome-card">
        <div class="logo-text">Grafica Nappa</div>
        <div class="subtitle">Sistema Gestionale di Produzione</div>

        <a href="{{ route('operatore.login') }}" class="btn btn-enter btn-operatore">
            Accedi
        </a>

        <div class="footer-text">
            &copy; {{ date('Y') }} Grafica Nappa srl &mdash; Aversa (CE)
        </div>
    </div>
</body>
</html>
