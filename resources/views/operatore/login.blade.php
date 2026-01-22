<!DOCTYPE html>
<html>
<head>
    <title>Accesso Operatore</title>
</head>
<body>
    <h2>Accesso Operatore</h2>

    @if($errors->any())
        <p style="color:red">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('operatore.login.post') }}">
        @csrf

        <label>Codice Operatore</label><br>
        <input type="text" name="codice_operatore" required autofocus>
        <br><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>
