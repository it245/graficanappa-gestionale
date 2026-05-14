# Bot Telegram MES Grafica Nappa — Query

Bot Telegram per interrogare il MES. Comandi fissi + LLM Claude (tool use).

## Setup sul .60

```powershell
# 1. Python 3.11+
python --version

# 2. Crea venv
cd C:\bots\mes-query-bot
python -m venv .venv
.\.venv\Scripts\Activate.ps1

# 3. Install deps
pip install -r requirements.txt

# 4. Configura .env (copia .env.example)
copy .env.example .env
notepad .env
# Compila:
#   TELEGRAM_BOT_TOKEN
#   ANTHROPIC_API_KEY
#   ALLOWED_USER_IDS (Giovanni + capo)
#   DB_HOST/PORT/NAME/USER/PASS (read-only consigliato)

# 5. Test manuale
python bot.py
# Scrivi al bot Telegram → verifica risposta

# 6. Service Windows (nssm)
nssm install MesQueryBot "C:\bots\mes-query-bot\.venv\Scripts\python.exe" "C:\bots\mes-query-bot\bot.py"
nssm set MesQueryBot AppDirectory "C:\bots\mes-query-bot"
nssm set MesQueryBot AppStdout "C:\bots\mes-query-bot\logs\stdout.log"
nssm set MesQueryBot AppStderr "C:\bots\mes-query-bot\logs\stderr.log"
nssm set MesQueryBot Start SERVICE_AUTO_START
nssm start MesQueryBot
```

## Comandi

- `/start` — benvenuto
- `/commessa 67386` — dettaglio commessa
- `/alert` — macchine ferme >30min
- `/oggi` — riepilogo giornaliero
- `/top` — top commesse offset 7gg

Oltre ai comandi, scrivi liberamente:
- "come va la 67386?"
- "chi sta stampando ora?"
- "fasi ferme da più di un'ora?"
- "quante consegne oggi?"

## Sicurezza

- Whitelist `ALLOWED_USER_IDS` blocca utenti non autorizzati.
- DB user `mes_readonly` consigliato (no UPDATE/INSERT/DELETE).
- Tool functions sono SOLO SELECT. Nessuna scrittura.
- Token Telegram + chiave Anthropic SOLO in `.env` locale, mai in git.

## Costi

Claude Sonnet 4.5 ≈ $3/M input + $15/M output tokens.
Stima 50 query/giorno × 2000 tokens = ~$0.05/giorno ≈ $1.50/mese.
