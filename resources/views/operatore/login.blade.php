<!DOCTYPE html>
<html>
<head>
    <title>Accesso Operatore</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Evita cache della pagina -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, max-age=0, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="Sat, 01 Jan 1990 00:00:00 GMT">
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        input { padding: 5px; font-size: 1rem; }
        button { padding: 5px 15px; font-size: 1rem; }
        .error { color: red; margin-bottom: 15px; }
        .container { max-width: 400px; margin: auto; }
    </style>
</head>
<body>
<form id="loginForm">
    @csrf
    <label>Codice Operatore</label><br>
    <input type="text" name="codice_operatore" required autofocus>
    <br><br>
    <button type="submit">Login</button>
</form>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e){
    e.preventDefault();

    const codice = this.codice_operatore.value;
    const csrf = document.querySelector('input[name="_token"]').value;

    const res = await fetch('{{ route("operatore.login.post") }}', {
        method: 'POST',
        headers: {
            'Content-Type':'application/json',
            'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({ codice_operatore: codice })
    });

    if (!res.ok) {
        alert('Errore server');
        return;
    }

    const data = await res.json();

    if (!data.success) {
        sessionStorage.setItem('operatore_token',data.token);
        window.location.href = data.redirect
    }

    // ✅ sessionStorage = UNA SCHEDA = UN OPERATORE
    sessionStorage.setItem('operatore_token', data.token);
    sessionStorage.setItem('operatore_id', data.operatore.id);
    sessionStorage.setItem('operatore_nome', data.operatore.nome);
    sessionStorage.setItem('operatore_ruolo', data.operatore.ruolo);
    sessionStorage.setItem('operatore_reparto', data.operatore.reparto);

    // redirect deciso dal frontend
    window.location.href = data.redirect;
});
</script>

</body>
</html>