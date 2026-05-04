<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>LOGIN | MES Grafica Nappa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #d11317;
            --brand-hover: #a00f12;
            --brand-soft: rgba(209, 19, 23, .12);
            --bg-input: #fafbfc;
            --border: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d0608 50%, #d11317 100%);
            padding: 20px;
        }

        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,.04), 0 12px 32px rgba(0,0,0,.25);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .brand img {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 16px;
            border: 3px solid var(--brand);
            box-shadow: 0 4px 12px var(--brand-soft);
        }
        .brand .logo-text {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.3px;
        }
        .brand .tag {
            font-size: 11px;
            color: var(--text-secondary);
            margin: 4px 0 24px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        h2 {
            font-size: 18px;
            font-weight: 600;
            color: var(--brand);
            margin-bottom: 22px;
            letter-spacing: 0.4px;
        }

        .field-group { margin-bottom: 18px; text-align: left; }
        .field-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        input[type="text"] {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 16px;
            background: var(--bg-input);
            font-family: inherit;
            color: var(--text-primary);
            transition: border-color .2s cubic-bezier(.4,0,.2,1), background .2s, box-shadow .2s;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: var(--brand);
            background: #fff;
            box-shadow: 0 0 0 3px var(--brand-soft);
        }

        button[type="submit"] {
            width: 100%;
            background: var(--brand);
            border: none;
            border-radius: 8px;
            padding: 13px;
            color: #fff;
            font-weight: 600;
            font-size: 15px;
            font-family: inherit;
            letter-spacing: 0.4px;
            cursor: pointer;
            transition: all .2s cubic-bezier(.4,0,.2,1);
            box-shadow: 0 2px 4px var(--brand-soft);
            margin-top: 4px;
        }
        button[type="submit"]:hover:not(:disabled) {
            background: var(--brand-hover);
            box-shadow: 0 8px 18px rgba(209,19,23,.3);
            transform: translateY(-1px);
        }
        button[type="submit"]:disabled { opacity: .6; cursor: wait; }

        .error-message {
            background: #fef2f2;
            border-left: 3px solid var(--brand);
            color: var(--brand);
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 16px;
            text-align: left;
        }

        .info-message {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 22px;
        }
        .info-message strong { color: var(--text-primary); }

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

        <h2>LOGIN OPERATORE</h2>

        @if(session('warning'))
            <div class="error-message">{{ session('warning') }}</div>
        @endif
        @if($errors->any())
            <div class="error-message">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('operatore.login.post') }}" id="opLoginForm">
            @csrf
            <div class="field-group">
                <label for="codice_operatore">Codice operatore</label>
                <input id="codice_operatore" type="text" name="codice_operatore"
                       placeholder="es. 001" required autofocus
                       inputmode="numeric" autocomplete="off">
            </div>
            <button type="submit" id="opLoginBtn">Accedi</button>
        </form>

        <p class="info-message">Assistenza reparto informatico: <strong>it@graficanappa.com</strong></p>
    </div>

    <script>
        document.getElementById('opLoginForm').addEventListener('submit', function() {
            var b = document.getElementById('opLoginBtn');
            b.disabled = true;
            b.textContent = 'Accesso in corso...';
        });

        // Rinnova CSRF token ogni 10 minuti per evitare "Page Expired"
        setInterval(function() {
            fetch('{{ route("operatore.login") }}', { credentials: 'same-origin' })
                .then(function(r) { return r.text(); })
                .then(function(html) {
                    var match = html.match(/name="_token"\s+value="([^"]+)"/);
                    if (match) {
                        var input = document.querySelector('input[name="_token"]');
                        if (input) input.value = match[1];
                    }
                }).catch(function(){});
        }, 600000);
    </script>
</body>
</html>
