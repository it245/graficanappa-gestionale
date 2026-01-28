<!DOCTYPE html>
<html>
<head>
    <title>Accesso Operatore</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        input { padding: 5px; font-size: 1rem; }
        button { padding: 5px 15px; font-size: 1rem; }
        .error { color: red; margin-bottom: 15px; }
        .container { max-width: 400px; margin: auto; }
    </style>
</head>
<body>
<div class="container">
    <h2>Accesso Operatore</h2>
   <form id="loginForm">
    @csrf
    <label>Codice Operatore</label><br>
    <input type="text" name="codice_operatore" required autofocus>
    <br><br>
    <button type="submit">Login</button>
</form>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const codice = this.codice_operatore.value.trim().toLowerCase();

    const res = await fetch('{{ url("/api/operatore/login") }}', {
        method: 'POST',
        headers: { 'Content-Type':'application/json' },
        body: JSON.stringify({ codice_operatore: codice })
    });

    const data = await res.json();

    if(data.success){
        sessionStorage.setItem('operatore_token', data.token);
        sessionStorage.setItem('operatore_id', data.operatore.id);
        sessionStorage.setItem('operatore_nome', data.operatore.nome);
        sessionStorage.setItem('operatore_ruolo', data.operatore.ruolo);
        sessionStorage.setItem('operatore_reparto', data.operatore.reparto);

        window.location.href = '/dashboard';
    } else {
        alert(data.messaggio || 'Codice operatore non valido');
    }
});
</script>
</body>
</html>