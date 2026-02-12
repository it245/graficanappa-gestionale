<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN | MES</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background: linear-gradient(to top, #d11317, #000); /* gradiente rosso -> nero verso l'alto */
        }

        .login-container { 
            background-color: #fff; 
            padding: 40px 50px; 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.4); 
            text-align: center; 
            width: 350px; 
        }

        .login-container img { 
            width: 100px; 
            height: 100px; 
            border-radius: 50%; 
            margin-bottom: 20px; 
            object-fit: cover; 
            border: 3px solid #d11317; 
        }

        h2 { 
            margin-bottom: 30px; 
            color: #d11317; 
            font-weight: 700; 
        }

        input[type="text"] { 
            width: 100%; 
            padding: 12px 15px; 
            margin: 10px 0 20px 0; 
            border-radius: 8px; 
            border: 1px solid #ccc; 
            font-size: 16px; 
            transition: 0.3s; 
        }

        input[type="text"]:focus { 
            border-color: #d11317; 
            box-shadow: 0 0 8px rgba(209,19,23,0.5); 
            outline: none; 
        }

        button { 
            width: 100%; 
            padding: 12px; 
            border: none; 
            border-radius: 8px; 
            background-color: #d11317; 
            color: #fff; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            transition: 0.3s; 
        }

        button:hover { 
            background-color: #a00f12; 
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
        <h2>LOGIN</h2>

        @if($errors->any())
            <p class="error-message">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('operatore.login.post') }}">
            @csrf
            <input type="text" name="codice_operatore" placeholder="Inserisci codice operatore" required autofocus>
            <button type="submit">Accedi</button>
        </form>

        <p class="info-message">Assistenza reparto informatico: <strong>it@graficanappa.com</strong></p>
    </div>
</body>
</html>