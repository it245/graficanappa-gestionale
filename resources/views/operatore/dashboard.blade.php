@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Dashboard Operatore</h2>

    <p>Operatore: {{ $operatore->nome }}</p>
    <p>Reparto: {{ $operatore->reparto }}</p>

    <h4>Fasi visibili</h4>

    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Priorità</th>
                <th>Operatore</th>
                <th>Fase</th>
                <th>Azioni</th>
                <th>Stato</th>
                <th>Commessa</th>
                <th>Data Registrazione</th>
                <th>Cliente</th>
                <th>Codice Articolo</th>
                <th>Descrizione Articolo</th>
                <th>Quantità Richiesta</th>
                <th>UM</th>
                <th>Data Prevista Consegna</th>
                <th>Qta Prodotta</th>
                <th>Codice Carta</th>
                <th>Carta</th>
                <th>Quantità Carta</th>
                <th>Note</th>
                <th>Ore</th>
                <th>Timeout</th>
            </tr>
        </thead>
        <tbody id="fasi-body">
            <!-- Le righe saranno generate via JS -->
        </tbody>
    </table>

    <button class="btn btn-secondary" onclick="logout()">Logout</button>
</div>

<script>
const motiviPausa = ["Attesa materiale", "Problema macchina", "Pranzo", "Altro"];

// Funzione fetch generale con token
async function fetchWithToken(url, options = {}) {
    options.headers = options.headers || {};
    options.headers['X-Operatore-Token'] = sessionStorage.getItem('operatore_token');
    options.headers['Content-Type'] = 'application/json';
    
    if (options.body && typeof options.body !== 'string') {
        options.body = JSON.stringify(options.body);
    }

    const res = await fetch(url, options);

    if (!res.ok) {
        if (res.status === 401) {
            alert('Sessione scaduta, rifai login.');
            window.location.href = '{{ route("operatore.login") }}';
        }
        return null;
    }

    return await res.json();
}

// Carica tutte le fasi
async function caricaFasi() {
    const data = await fetchWithToken('{{ route("produzione.datiDashboardOperatore") }}');
    if(data && data.success){
        const tbody = document.getElementById('fasi-body');
        tbody.innerHTML = '';
        data.fasi.forEach(fase => {
            const tr = document.createElement('tr');
            tr.id = 'fase-'+fase.id;
            tr.innerHTML = `
                <td>${fase.priorita ?? '-'}</td>
                <td id="operatore-${fase.id}">
                    ${fase.operatori.map(op => `${op.nome} (${op.data_inizio}${op.data_fine ? ' - ' + op.data_fine : ''})`).join('<br>')}
                </td>
                <td>${fase.faseCatalogo?.nome ?? '-'}</td>
                <td>
                    <input type="checkbox" id="avvia-${fase.id}" onchange="aggiornaStato(${fase.id}, 'avvia', this.checked)"> Avvia
                    <input type="checkbox" id="pausa-${fase.id}" onchange="gestisciPausa(${fase.id}, this.checked)"> Pausa
                    <input type="checkbox" id="termina-${fase.id}" onchange="aggiornaStato(${fase.id}, 'termina', this.checked)"> Termina
                </td>
                <td id="stato-${fase.id}">${fase.stato ?? '-'}</td>
                <td>${fase.ordine?.commessa ?? '-'}</td>
                <td>${fase.ordine?.data_registrazione ?? '-'}</td>
                <td>${fase.ordine?.cliente_nome ?? '-'}</td>
                <td>${fase.ordine?.cod_art ?? '-'}</td>
                <td>${fase.ordine?.descrizione ?? '-'}</td>
                <td>${fase.ordine?.qta_richiesta ?? '-'}</td>
                <td>${fase.ordine?.um ?? '-'}</td>
                <td>${fase.ordine?.data_prevista_consegna ?? '-'}</td>
                <td><input type="text" value="${fase.qta_prod ?? ''}" onblur="aggiornaCampo(${fase.id}, 'qta_prod', this.value)"></td>
                <td>${fase.ordine?.cod_carta ?? '-'}</td>
                <td>${fase.ordine?.carta ?? '-'}</td>
                <td>${fase.ordine?.qta_carta ?? '-'}</td>
                <td><textarea onblur="aggiornaCampo(${fase.id}, 'note', this.value)">${fase.note ?? ''}</textarea></td>
                <td>${fase.ore ?? '-'}</td>
                <td id="timeout-${fase.id}">${fase.timeout ?? '-'}</td>
            `;
            tbody.appendChild(tr);
        });
    }
}

// Avvia/Termina fase
async function aggiornaStato(faseId, azione, checked){
    if(!checked) return;
    if(azione==='termina' && !confirm('Sei sicuro di voler terminare questa fase?')){
        document.getElementById('termina-'+faseId).checked=false;
        return;
    }
    const route = azione==='avvia' ? '{{ route("produzione.avvia") }}' : '{{ route("produzione.termina") }}';
    const data = await fetchWithToken(route, {method:'POST', body:{fase_id:faseId}});
    if(data.success) await caricaFasi();
    else alert('Errore: ' + (data.messaggio||''));
}

// Gestione pausa
async function gestisciPausa(faseId, checked){
    if(!checked) return;
    const scelta = prompt("Seleziona motivo pausa:\n1) Attesa materiale\n2) Problema macchina\n3) Pranzo\n4) Altro");
    if(!["1","2","3","4"].includes(scelta)){
        document.getElementById('pausa-'+faseId).checked=false;
        return alert('Selezione non valida!');
    }
    const motivo = motiviPausa[parseInt(scelta)-1];
    const data = await fetchWithToken('{{ route("produzione.pausa") }}',{method:'POST', body:{fase_id:faseId, motivo}});
    if(data.success){
        document.getElementById('stato-'+faseId).innerText=motivo;
        document.getElementById('timeout-'+faseId).innerText=data.timeout;
        ['avvia','termina'].forEach(a=>document.getElementById(a+'-'+faseId).checked=false);
    }
}

// Aggiorna campo
async function aggiornaCampo(faseId, campo, valore){
    const data = await fetchWithToken('{{ route("produzione.aggiornaCampo") }}',{method:'POST', body:{fase_id:faseId, campo, valore}});
    if(!data.success) alert('Errore: '+(data.messaggio||''));
}

// Aggiorna singola riga tabella
function aggiornaTabellaSingola(faseId, data){
    document.getElementById('stato-'+faseId).innerText = data.nuovo_stato ?? data.stato;
    const opCell = document.getElementById('operatore-'+faseId);
    opCell.innerHTML = (data.operatori||[]).map(op => `${op.nome} (${op.data_inizio}${op.data_fine ? ' - ' + op.data_fine : ''})`).join('<br>');
    const row = document.getElementById('fase-'+faseId);
    const qtaInput = row.querySelector('input[type="text"]');
    if(qtaInput && data.qta_prod!==undefined) qtaInput.value = data.qta_prod;
    const noteTextarea = row.querySelector('textarea');
    if(noteTextarea && data.note!==undefined) noteTextarea.value = data.note;
    document.getElementById('timeout-'+faseId).innerText = data.timeout ?? '-';
    if(data.nuovo_stato==='2' || data.stato==='2') row.style.display='none';
}

// Logout
async function logout(){
    const data = await fetchWithToken('{{ route("operatore.logout") }}', {method:'POST'});
    if(data.success){
        sessionStorage.clear();
        window.location.href = '{{route("operatore.login")}}';
    }
}

// Aggiornamento automatico ogni 10s
setInterval(caricaFasi, 10000);

// Carica inizialmente
caricaFasi();
</script>
@endsection