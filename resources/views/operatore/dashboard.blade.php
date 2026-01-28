@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Dashboard Operatore</h2>

    <p>Operatore:{{$operatore->nome}}</p>
    <p>Reparto: {{$operatore->reparto}}</p>

    <h4>Fasi Visibili</h4>
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
                <th>Note</th>
                <th>Ore</th>
                <th>Timeout</th>
            </tr>
        </thead>
        <tbody id="fasi-body">
            <!-- Righe caricate via JS -->
        </tbody>
    </table>

    <button class="btn btn-secondary" onclick="logout()">Logout</button>
</div>

@push('scripts')
<script>
console.log('JS Dashboard caricato');

// Recupera token
const token = sessionStorage.getItem('operatore_token');
if(!token){
    alert('Devi effettuare il login.');
    window.location.href = '/operatore/login';
}

// Mostra info operatore
document.getElementById('operatore-nome').innerText = sessionStorage.getItem('operatore_nome') || '-';
document.getElementById('operatore-reparto').innerText = sessionStorage.getItem('operatore_reparto') || '-';

// Funzione fetch con token
async function fetchWithToken(url, options = {}) {
    options.headers = options.headers || {};
    options.headers['Content-Type'] = 'application/json';
    options.headers['X-Operatore-Token'] = token;

    if(options.body && typeof options.body !== 'string'){
        options.body = JSON.stringify(options.body);
    }

    const res = await fetch(url, options);

    if(res.status === 403){
        alert('Token scaduto o non valido. Effettua nuovamente il login.');
        sessionStorage.clear();
        window.location.href = '/operatore/login';
        return null;
    }

    return await res.json();
}

// Carica fasi
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
                <td>${fase.operatori.map(op => `${op.nome} (${op.data_inizio ?? '-'}` + (op.data_fine ? ' - ' + op.data_fine : '') + `)`).join('<br>')}</td>
                <td>${fase.faseCatalogo?.nome ?? '-'}</td>
                <td>
                    <input type="checkbox" id="avvia-${fase.id}" onchange="aggiornaStato(${fase.id}, 'avvia', this)"> Avvia
                    <input type="checkbox" id="pausa-${fase.id}" onchange="gestisciPausa(${fase.id}, this)"> Pausa
                    <input type="checkbox" id="termina-${fase.id}" onchange="aggiornaStato(${fase.id}, 'termina', this)"> Termina
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
                <td><textarea onblur="aggiornaCampo(${fase.id}, 'note', this.value)">${fase.note ?? ''}</textarea></td>
                <td>${fase.ore ?? '-'}</td>
                <td id="timeout-${fase.id}">${fase.timeout ?? '-'}</td>
            `;
            tbody.appendChild(tr);
        });
    }
}

// Aggiorna stato Avvia/Termina
async function aggiornaStato(faseId, azione, checkbox){
    if(!checkbox.checked) return;
    checkbox.disabled = true;

    if(azione === 'termina' && !confirm('Sei sicuro di terminare questa fase?')){
        checkbox.checked = false;
        checkbox.disabled = false;
        return;
    }

    const route = azione === 'avvia' ? '{{ route("produzione.avvia") }}' : '{{ route("produzione.termina") }}';
    const data = await fetchWithToken(route, { method: 'POST', body: { fase_id: faseId } });

    if(data && data.success){
        await caricaFasi();
    } else {
        alert('Errore: '+(data?.messaggio || ''));
        checkbox.checked = false;
    }
    checkbox.disabled = false;
}

// Gestione pausa
async function gestisciPausa(faseId, checkbox){
    if(!checkbox.checked) return;
    checkbox.disabled = true;

    const motivi = ["Attesa materiale","Problema macchina","Pranzo","Altro"];
    let scelta = prompt("Seleziona motivo pausa:\n1) Attesa materiale\n2) Problema macchina\n3) Pranzo\n4) Altro");
    if(!["1","2","3","4"].includes(scelta)){
        alert('Selezione non valida');
        checkbox.checked = false;
        checkbox.disabled = false;
        return;
    }

    const motivo = motivi[parseInt(scelta)-1];
    const data = await fetchWithToken('{{ route("produzione.pausa") }}', { method: 'POST', body: { fase_id: faseId, motivo } });

    if(data && data.success){
        document.getElementById('stato-'+faseId).innerText = motivo;
        ['avvia','termina'].forEach(a => {
            const el = document.getElementById(a+'-'+faseId);
            if(el) el.checked = false;
        });
    } else {
        alert('Errore pausa: '+(data?.messaggio || ''));
        checkbox.checked = false;
    }
    checkbox.disabled = false;
}

// Aggiorna campo
async function aggiornaCampo(faseId, campo, valore){
    const data = await fetchWithToken('{{ route("produzione.aggiornaCampo") }}', { method: 'POST', body: { fase_id: faseId, campo, valore } });
    if(!data || !data.success) alert('Errore: '+(data?.messaggio || ''));
}

// Logout
async function logout(){
    const data = await fetchWithToken('{{ route("operatore.logout") }}', { method: 'POST' });
    if(data && data.success){
        sessionStorage.clear();
        window.location.href = '/operatore/login';
    }
}

// Carica inizialmente
caricaFasi();

// Aggiornamento automatico ogni 100 secondi
setInterval(caricaFasi, 100000);

</script>
@endpush
@endsection