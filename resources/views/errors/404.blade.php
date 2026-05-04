<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Pagina non trovata | MES Grafica Nappa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --text-tertiary: #d1d5db;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            padding: 20px;
        }

        .error-card {
            background: #fff;
            padding: 48px 40px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,.04), 0 12px 32px rgba(0,0,0,.12);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }

        .brand { margin-bottom: 24px; }
        .brand img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 12px;
            border: 2px solid #f3f4f6;
        }
        .brand .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.3px;
        }
        .brand .tag {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 4px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .error-code {
            font-size: 96px;
            font-weight: 700;
            color: var(--text-tertiary);
            line-height: 1;
            margin: 8px 0 12px;
            letter-spacing: -2px;
        }
        .error-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        .error-msg {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .btn-home {
            display: inline-block;
            background: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            font-family: inherit;
            letter-spacing: 0.3px;
            text-decoration: none;
            cursor: pointer;
            transition: all .2s cubic-bezier(.4,0,.2,1);
            box-shadow: 0 2px 4px rgba(59,130,246,.15);
        }
        .btn-home:hover {
            background: var(--primary-hover);
            box-shadow: 0 8px 16px rgba(59,130,246,.25);
            transform: translateY(-1px);
        }

        @media (max-width: 480px) {
            .error-card { padding: 36px 22px; }
            .error-code { font-size: 72px; }
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="brand">
            <img src="{{ asset('images/logo_gn.png') }}" alt="Grafica Nappa">
            <div class="logo-text">GRAFICA NAPPA</div>
            <div class="tag">MES &mdash; Sistema Produzione</div>
        </div>

        <div class="error-code">404</div>
        <div class="error-title">Pagina non trovata</div>
        <p class="error-msg">La pagina che stai cercando non esiste o &egrave; stata spostata.</p>

        <a href="{{ url('/') }}" class="btn-home">Torna alla home</a>
    </div>
</body>
</html>
