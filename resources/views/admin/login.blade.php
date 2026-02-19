<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN LOGIN | MES</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(to top, #1a1a2e, #000);
        }

        .login-container {
            background-color: #fff;
            padding: 40px 50px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
            text-align: center;
            width: 380px;
        }

        .login-container img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 20px;
            object-fit: cover;
            border: 3px solid #1a1a2e;
        }

        h2 {
            margin-bottom: 30px;
            color: #1a1a2e;
            font-weight: 700;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
            transition: 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #1a1a2e;
            box-shadow: 0 0 8px rgba(26,26,46,0.4);
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            border: none;
            border-radius: 8px;
            background-color: #1a1a2e;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background-color: #16213e;
        }

        .error-message {
            color: #d11317;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .info-message {
            color: #555;
            font-size: 13px;
            margin-top: 20px;
        }

        @media (max-width: 400px) {
            .login-container { width: 90%; padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="{{ asset('images/logo_gn.png') }}" alt="Logo Azienda">
        <h2>ADMIN</h2>

        @if($errors->any())
            <p class="error-message">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <input type="text" name="codice_operatore" placeholder="Codice operatore" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Accedi</button>
        </form>

        <p class="info-message">Accesso riservato all'amministratore</p>
    </div>
</body>
</html>
