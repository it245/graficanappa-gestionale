"""Registra comandi bot via Telegram setMyCommands. Esegui una volta."""
import os, json
import urllib.request
from dotenv import load_dotenv

load_dotenv()
TOKEN = os.environ['TELEGRAM_BOT_TOKEN']

COMMANDS = [
    {"command": "commessa", "description": "Dettaglio commessa (numero)"},
    {"command": "fasi", "description": "Fasi in lavorazione (opzionale reparto)"},
    {"command": "pronte", "description": "Fasi pronte stato=1"},
    {"command": "terminate", "description": "Fasi terminate oggi"},
    {"command": "esterne", "description": "Lavorazioni esterne pendenti"},
    {"command": "reparti", "description": "Overview reparti"},
    {"command": "presenti", "description": "Operatori in azienda oggi"},
    {"command": "alert", "description": "Macchine ferme >30min"},
    {"command": "oggi", "description": "Riepilogo produzione oggi"},
    {"command": "top", "description": "Top commesse offset 7gg"},
    {"command": "sync_onda", "description": "Sincronizza Onda ERP"},
    {"command": "sync_prinect", "description": "Sincronizza Prinect"},
    {"command": "ritardo", "description": "Commesse in ritardo (data scaduta)"},
    {"command": "consegne_sett", "description": "Consegne ultimi 7 giorni"},
    {"command": "tracking", "description": "Tracking BRT per numero DDT"},
    {"command": "aiuto", "description": "Lista comandi"},
]

url = f"https://api.telegram.org/bot{TOKEN}/setMyCommands"
data = json.dumps({"commands": COMMANDS}).encode()
req = urllib.request.Request(url, data=data, headers={"Content-Type": "application/json"})
with urllib.request.urlopen(req, timeout=15) as r:
    print(r.read().decode())
