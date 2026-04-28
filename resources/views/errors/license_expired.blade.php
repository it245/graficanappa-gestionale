<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Licenza scaduta</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .box { background: #fff; border-radius: 14px; padding: 50px 60px; box-shadow: 0 10px 40px rgba(0,0,0,.08); max-width: 540px; }
        h1 { color: #dc2626; margin: 0 0 12px; font-size: 28px; }
        p { color: #475569; line-height: 1.6; }
        .icon { font-size: 64px; margin-bottom: 16px; }
        .footer { margin-top: 32px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 13px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">🔒</div>
        <h1>Licenza scaduta</h1>
        <p>La licenza del software MES per <strong>{{ $config->nome_azienda ?? 'questo tenant' }}</strong> è scaduta o non è attiva.</p>
        <p>Per ripristinare l'accesso, contatta il fornitore del software.</p>
        <div class="footer">
            Tenant: <code>{{ tenant_id() }}</code><br>
            Scadenza: {{ optional($config->license_expires_at)->format('d/m/Y H:i') ?? 'non impostata' }}
        </div>
    </div>
</body>
</html>
