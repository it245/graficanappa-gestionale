@extends('layouts.app')

@section('content')
<style>
    .chat-container {
        max-width: 900px;
        margin: 0 auto;
        height: calc(100vh - 120px);
        display: flex;
        flex-direction: column;
    }
    .chat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #1a73e8;
        color: #fff;
        border-radius: 12px 12px 0 0;
    }
    .chat-header h5 { margin: 0; font-weight: 600; }
    .chat-canali {
        display: flex;
        gap: 6px;
        padding: 10px 16px;
        background: #f0f2f5;
        border-bottom: 1px solid #ddd;
        overflow-x: auto;
    }
    .chat-canali .btn-canale {
        padding: 5px 14px;
        border-radius: 20px;
        border: 1px solid #ccc;
        background: #fff;
        font-size: 13px;
        cursor: pointer;
        white-space: nowrap;
        text-decoration: none;
        color: #333;
    }
    .chat-canali .btn-canale.active {
        background: #1a73e8;
        color: #fff;
        border-color: #1a73e8;
    }
    .chat-messaggi {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: #e5ddd5;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .chat-msg {
        max-width: 75%;
        padding: 8px 12px;
        border-radius: 10px;
        font-size: 14px;
        line-height: 1.4;
        position: relative;
        word-wrap: break-word;
    }
    .chat-msg.mio {
        align-self: flex-end;
        background: #dcf8c6;
        border-bottom-right-radius: 2px;
    }
    .chat-msg.altro {
        align-self: flex-start;
        background: #fff;
        border-bottom-left-radius: 2px;
    }
    .chat-msg .msg-utente {
        font-size: 11px;
        font-weight: 700;
        color: #1a73e8;
        margin-bottom: 2px;
    }
    .chat-msg.mio .msg-utente { color: #075e54; }
    .chat-msg .msg-ora {
        font-size: 10px;
        color: #999;
        text-align: right;
        margin-top: 2px;
    }
    .chat-input-area {
        display: flex;
        gap: 8px;
        padding: 12px 16px;
        background: #f0f2f5;
        border-radius: 0 0 12px 12px;
        border-top: 1px solid #ddd;
    }
    .chat-input-area input {
        flex: 1;
        border-radius: 20px;
        border: 1px solid #ccc;
        padding: 10px 16px;
        font-size: 14px;
        outline: none;
    }
    .chat-input-area input:focus { border-color: #1a73e8; }
    .chat-input-area button {
        border-radius: 50%;
        width: 44px;
        height: 44px;
        border: none;
        background: #1a73e8;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .chat-input-area button:hover { background: #1557b0; }
    .chat-vuota {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 15px;
    }
</style>

<div class="chat-container">
    <div class="chat-header">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('operatore.dashboard') }}" style="color:#fff; text-decoration:none; font-size:20px;">&larr;</a>
            <h5>Chat MES</h5>
        </div>
        <span style="font-size:12px; opacity:0.8;">{{ request()->attributes->get('operatore_nome', session('operatore_nome', 'Utente')) }}</span>
    </div>

    <div class="chat-canali">
        @foreach($canali as $c)
            <a href="{{ route('chat.index', ['canale' => $c]) }}"
               class="btn-canale {{ $canale === $c ? 'active' : '' }}">
                #{{ $c }}
            </a>
        @endforeach
    </div>

    <div class="chat-messaggi" id="chat-messaggi">
        @if($messaggi->isEmpty())
            <div class="chat-vuota">Nessun messaggio in #{{ $canale }}</div>
        @else
            @foreach($messaggi as $msg)
                <div class="chat-msg {{ $msg->operatore_id === $operatoreId ? 'mio' : 'altro' }}">
                    @if($msg->operatore_id !== $operatoreId)
                        <div class="msg-utente">{{ $msg->operatore->nome ?? 'Utente' }}</div>
                    @endif
                    <div>{{ $msg->messaggio }}</div>
                    <div class="msg-ora">{{ $msg->created_at->format('H:i') }}</div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="chat-input-area">
        <input type="text" id="chat-input" placeholder="Scrivi un messaggio..." autocomplete="off"
               onkeydown="if(event.key==='Enter')inviaMessaggio()">
        <button onclick="inviaMessaggio()" title="Invia">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
        </button>
    </div>
</div>

<script>
var canale = @json($canale);
var ultimoId = {{ $messaggi->isNotEmpty() ? $messaggi->last()->id : 0 }};
var operatoreId = {{ $operatoreId }};

function inviaMessaggio() {
    var input = document.getElementById('chat-input');
    var testo = input.value.trim();
    if (!testo) return;

    input.value = '';
    input.focus();

    // Mostra subito il messaggio (ottimistico)
    appendMessaggio({
        messaggio: testo,
        utente: '{{ request()->attributes->get("operatore_nome", session("operatore_nome", "Utente")) }}',
        timestamp: new Date().toLocaleTimeString('it-IT', {hour:'2-digit', minute:'2-digit'}),
        mio: true
    });

    fetch("{{ route('chat.invia') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ messaggio: testo, canale: canale })
    }).then(r => r.json()).then(data => {
        if (data.ok) ultimoId = Math.max(ultimoId, data.id || ultimoId);
    }).catch(err => console.error('Errore invio:', err));
}

function appendMessaggio(msg) {
    var container = document.getElementById('chat-messaggi');
    // Rimuovi placeholder "nessun messaggio"
    var vuota = container.querySelector('.chat-vuota');
    if (vuota) vuota.remove();

    var div = document.createElement('div');
    div.className = 'chat-msg ' + (msg.mio ? 'mio' : 'altro');
    var html = '';
    if (!msg.mio) html += '<div class="msg-utente">' + escHtml(msg.utente) + '</div>';
    html += '<div>' + escHtml(msg.messaggio) + '</div>';
    html += '<div class="msg-ora">' + escHtml(msg.timestamp) + '</div>';
    div.innerHTML = html;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

function escHtml(t) {
    var d = document.createElement('div');
    d.textContent = t;
    return d.innerHTML;
}

// Polling per nuovi messaggi (ogni 3 secondi) — verrà sostituito da WebSocket con Reverb
function pollMessaggi() {
    fetch("{{ route('chat.messaggi') }}?canale=" + canale + "&after=" + ultimoId)
        .then(r => r.json())
        .then(messaggi => {
            messaggi.forEach(function(msg) {
                if (msg.id > ultimoId) {
                    if (!msg.mio) appendMessaggio(msg);
                    ultimoId = msg.id;
                }
            });
        })
        .catch(() => {});
}

setInterval(pollMessaggi, 3000);

// Scroll iniziale in fondo
document.getElementById('chat-messaggi').scrollTop = document.getElementById('chat-messaggi').scrollHeight;
</script>
@endsection
