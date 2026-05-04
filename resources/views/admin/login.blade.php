<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>ADMIN LOGIN | MES Grafica Nappa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --bg-input: #fafbfc;
            --border: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --danger: #dc2626;
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

        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,.04), 0 12px 32px rgba(0,0,0,.12);
            width: 100%;
            max-width: 420px;
        }

        .brand {
            text-align: center;
            margin-bottom: 28px;
        }
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

        h2 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 24px;
            text-align: center;
        }

        .field-group { margin-bottom: 18px; position: relative; }
        .field-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 15px;
            background: var(--bg-input);
            font-family: inherit;
            transition: border-color .2s cubic-bezier(.4,0,.2,1), background .2s, box-shadow .2s;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(59,130,246,.1);
        }

        button[type="submit"] {
            width: 100%;
            background: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 12px;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            font-family: inherit;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: all .2s cubic-bezier(.4,0,.2,1);
            box-shadow: 0 2px 4px rgba(59,130,246,.15);
            margin-top: 4px;
        }
        button[type="submit"]:hover:not(:disabled) {
            background: var(--primary-hover);
            box-shadow: 0 8px 16px rgba(59,130,246,.25);
            transform: translateY(-1px);
        }
        button[type="submit"]:disabled { opacity: .6; cursor: wait; }

        .error-message {
            background: #fef2f2;
            border-left: 3px solid var(--danger);
            color: var(--danger);
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .info-message {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 22px;
            text-align: center;
        }

        @media (max-width: 480px) {
            .login-card { padding: 28px 22px; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <img src="{{ asset('images/logo_gn.png') }}" alt="Grafica Nappa">
            <div class="logo-text">GRAFICA NAPPA</div>
            <div class="tag">MES — Sistema Produzione</div>
        </div>

        <h2>Area Amministratore</h2>

        @if(session('warning'))
            <div class="error-message">{{ session('warning') }}</div>
        @endif
        @if($errors->any())
            <div class="error-message">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}" id="adminLoginForm">
            @csrf
            <div class="field-group">
                <label for="codice_operatore">Codice operatore</label>
                <input id="codice_operatore" type="text" name="codice_operatore"
                       placeholder="es. 001" required autofocus autocomplete="username">
            </div>
            <div class="field-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password"
                       placeholder="••••••••" required autocomplete="current-password">
            </div>
            <button type="submit" id="adminLoginBtn">Accedi</button>
        </form>

        <p class="info-message">Accesso riservato all'amministratore</p>
    </div>

    <script>
        document.getElementById('adminLoginForm').addEventListener('submit', function() {
            var b = document.getElementById('adminLoginBtn');
            b.disabled = true; b.textContent = 'Accesso in corso...';
        });
    </script>
</body>
</html>
