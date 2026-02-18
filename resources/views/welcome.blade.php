<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MES - Grafica Nappa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to top, #d11317, #000);
        }

        .welcome-card {
            background: #fff;
            border-radius: 15px;
            padding: 40px 50px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
            max-width: 400px;
            width: 100%;
        }

        .welcome-card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 20px;
            object-fit: cover;
            border: 3px solid #d11317;
        }

        .logo-text {
            font-size: 2rem;
            font-weight: 700;
            color: #d11317;
            margin-bottom: 4px;
        }

        .subtitle {
            color: #555;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .btn-enter {
            display: block;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            border: none;
            background-color: #d11317;
            color: #fff;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-enter:hover {
            background-color: #a00f12;
            color: #fff;
        }

        .footer-text {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: #999;
        }

        @media (max-width: 400px) {
            .welcome-card { width: 90%; padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="welcome-card">
        <img src="{{ asset('images/logo_gn.png') }}" alt="Logo Grafica Nappa">
        <div class="logo-text">Grafica Nappa</div>
        <div class="subtitle">Sistema Gestionale di Produzione</div>

        <a href="{{ route('operatore.login') }}" class="btn-enter">
            Accedi
        </a>

        <div class="footer-text">
            &copy; {{ date('Y') }} Grafica Nappa srl &mdash; Aversa (CE)
        </div>
    </div>
</body>
</html>
