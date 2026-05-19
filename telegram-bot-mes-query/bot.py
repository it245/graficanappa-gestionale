"""
Bot Telegram MES Grafica Nappa — interrogazione gestionale.
Comandi fissi + LLM Claude per query naturali.
"""
import json
import logging
import os
import time
from datetime import datetime, timedelta
from logging.handlers import TimedRotatingFileHandler
from pathlib import Path

from anthropic import Anthropic
from dotenv import load_dotenv
from telegram import Update
from telegram.constants import ParseMode
from telegram.ext import (
    Application,
    CommandHandler,
    ContextTypes,
    MessageHandler,
    filters,
)

import tools
from pseudonymizer import Pseudonymizer

# === Config ===
load_dotenv()
TELEGRAM_TOKEN = os.environ['TELEGRAM_BOT_TOKEN']
ANTHROPIC_API_KEY = os.environ['ANTHROPIC_API_KEY']
ANTHROPIC_MODEL = os.environ.get('ANTHROPIC_MODEL', 'claude-sonnet-4-5-20250929')
ALLOWED_USER_IDS = {
    int(x.strip()) for x in os.environ.get('ALLOWED_USER_IDS', '').split(',') if x.strip()
}

logging.basicConfig(
    format='%(asctime)s [%(levelname)s] %(name)s: %(message)s',
    level=logging.INFO,
)
logger = logging.getLogger('mes-bot')

# === Audit log GDPR art. 30 + Statuto Lavoratori art. 4 ===
# JSONL append-only, 1 riga per query/comando. Rotation giornaliera, 90gg retention.
# File: logs/audit_telegram_query.jsonl
AUDIT_LOG_DIR = Path(os.environ.get('AUDIT_LOG_DIR', 'logs'))
AUDIT_LOG_DIR.mkdir(parents=True, exist_ok=True)
AUDIT_LOG_FILE = AUDIT_LOG_DIR / 'audit_telegram_query.jsonl'

_audit_handler = TimedRotatingFileHandler(
    AUDIT_LOG_FILE, when='midnight', backupCount=90, encoding='utf-8'
)
_audit_handler.setFormatter(logging.Formatter('%(message)s'))
audit_logger = logging.getLogger('mes-bot-audit')
audit_logger.setLevel(logging.INFO)
audit_logger.addHandler(_audit_handler)
audit_logger.propagate = False  # non duplicare su root logger


def audit_query(uid: int, username: str | None, event: str, **fields) -> None:
    """Scrive 1 record JSONL audit. event = 'query' | 'reject' | 'error' | 'command'."""
    record = {
        'ts': datetime.now().isoformat(timespec='seconds'),
        'event': event,
        'user_id': uid,
        'username': username,
        **fields,
    }
    audit_logger.info(json.dumps(record, ensure_ascii=False, default=str))

anthropic_client = Anthropic(api_key=ANTHROPIC_API_KEY)

# Conversation memory per user_id: {uid: {'messages': [...], 'updated': datetime}}
# TTL 30 min, max 12 turni (24 msg) per evitare overflow context.
CONV_HISTORY: dict[int, dict] = {}
CONV_TTL_MIN = 30
CONV_MAX_MSGS = 24


def get_history(uid: int) -> list:
    entry = CONV_HISTORY.get(uid)
    if not entry:
        return []
    if datetime.now() - entry['updated'] > timedelta(minutes=CONV_TTL_MIN):
        CONV_HISTORY.pop(uid, None)
        return []
    return entry['messages']


def compact_history(messages: list, final_text: str) -> list:
    """Riduce history: scarta tool_use/tool_result, tieni solo string user msg
    + string assistant final per ogni turno. Drasticamente meno token."""
    compact = []
    for m in messages:
        if m['role'] == 'user' and isinstance(m['content'], str):
            compact.append({'role': 'user', 'content': m['content']})
        # Assistant turn intermedi e tool_results scartati.
    # Ultimo turno: aggiungi final text assistant
    if compact and compact[-1]['role'] == 'user':
        compact.append({'role': 'assistant', 'content': final_text})
    return compact


async def send_long(update: Update, text: str, chunk: int = 3900) -> None:
    """Invia messaggi >4096 char splittando su newline."""
    if len(text) <= chunk:
        await update.message.reply_text(text)
        return
    buf = []
    cur_len = 0
    for line in text.split('\n'):
        if cur_len + len(line) + 1 > chunk and buf:
            await update.message.reply_text('\n'.join(buf))
            buf = []
            cur_len = 0
        buf.append(line)
        cur_len += len(line) + 1
    if buf:
        await update.message.reply_text('\n'.join(buf))


def save_history(uid: int, messages: list) -> None:
    """Salva history. Trim preservando coppie tool_use/tool_result.
    Taglia solo a confini user-message per non rompere sequenze tool."""
    if len(messages) > CONV_MAX_MSGS:
        # Trova primo user msg "pulito" (string content, no tool_result) >= cutoff
        cutoff = len(messages) - CONV_MAX_MSGS
        for i in range(cutoff, len(messages)):
            m = messages[i]
            if m['role'] == 'user' and isinstance(m['content'], str):
                messages = messages[i:]
                break
    CONV_HISTORY[uid] = {'messages': messages, 'updated': datetime.now()}

SYSTEM_PROMPT = """Assistente MES Grafica Nappa (tipografia). Accesso completo: read + write.

REGOLE RISPOSTA:
- ULTRA-CONCISA: rispondi SOLO all'esatta domanda.
- 1-3 frasi max per query semplici. Tabelle compatte per liste.
- VIETATO mostrare colonna "ID" o id interno. MAI in nessuna risposta. Usa "Priorita" o nessuna colonna identificativa.
- VIETATO chiedere "fase_id" all'utente. L'utente non lo conosce a memoria. Quando serve modificare una fase, accetta sempre COMMESSA + NOME FASE (es. "67375 SFUST.IML.FUSTELLATO"). Tu fai lookup interno con cerca_fasi(commessa=X, fase=Y) per trovare l'id, poi esegui l'operazione mostrando: "Confermi: commessa 67375 fase SFUST.IML.FUSTELLATO → priorita 1?". Se più di un risultato (multi-modello), elenca le varianti per nome descrizione e chiedi quale.
- VARIANTI multi-modello: ogni RIGA di cerca_fasi è un ordine distinto (variante). Conta righe RAW dal tool result, NON dedup per commessa.
- Per "varianti per commessa" → estrai 'descrizione' di ogni riga, raggruppa per commessa, mostra descrizioni distinte.

FORMATTAZIONE TELEGRAM (importante):
- VIETATO tabelle Markdown con `|` (Telegram non le renderizza, escono illeggibili).
- VIETATO Markdown (`**bold**`, `###`, tabelle). Risposta in TESTO SEMPLICE.
- Usa formato lista plain: titolo in maiuscolo, righe con `•` o `-`.
- Per note consegne / dati multi-data → 1 sezione per data:
  ```
  14/05
  • AM: ...
  • PM: ...

  13/05
  • AM: ...
  ```
- Una riga vuota tra sezioni. Niente asterischi.
- Ordina liste fasi per priorita (asc=più urgente).
- Per "presenti in azienda" mostra SEMPRE colonna `cognome_nome` (es. "BARBATO RAFFAELE"), MAI matricola.
- Per operatori in ritardo idem: nome cognome, mai matricola.
- Per "totale/parziale consegna" → usa get_stato_consegna, rispondi 1 parola.
- Per "fasi in corso/lavorazione" → usa get_fasi_attive (stato=2).
- Per "fasi terminate oggi" → usa get_fasi_terminate_oggi.
- Per "fasi pronte/da fare" → usa get_fasi_pronte.
- Per "cerca/trova" → usa cerca_fasi con filtri.
- LAVORAZIONI ESTERNE INVIATE: USA SEMPRE get_esterne_summary() (pre-aggregato). Tool restituisce {totale_fasi, totale_fornitori, per_fornitore:[{fornitore, n_fasi, fasi:[...]}]}. Mostra ESATTAMENTE quei valori. NON ricontare, NON inventare. Totale header = totale_fasi dal tool. Per fornitore mostra n_fasi dal tool.

QUERY QUANTITATIVE — USA TOOL SUMMARY:
Per conteggi, totali, raggruppamenti, ore lavorate: chiama SEMPRE il tool *_summary disponibile. NON contare righe manualmente. Usa campo aggregato fornito.
Mapping domanda → tool:
• "esterne inviate" / "fornitori" → get_esterne_summary
• "reparti" / "fasi per reparto" / "overview reparti" → get_reparti_summary
  NOTA: per reparto "spedizione" il campo in_lavorazione (stato=2) significa "PARZIALI" (consegne parziali). Etichetta come "parziali" nell'output, non "in lavorazione".
• "presenti" / "ore lavorate" / "ritardo entrata" → get_presenti_summary
• "commesse ritardo" / "scadute" / "in ritardo" → get_ritardi_summary
• "consegne settimana" / "spedizioni 7gg" → get_consegne_summary
Mostra esattamente i campi aggregati (totale_*, per_*). NON inventare somme.

SCRITTURE DB (modifiche):
- PRIMA di scrivere chiedi conferma esplicita mostrando: campo, vecchio valore, nuovo valore.
- Esempio: utente dice "termina fase 21138" → tu: "Confermi: fase 21138 → stato 3 (terminato)?"
- Se utente conferma "sì/ok/procedi" → esegui aggiorna_stato_fase.
- Se utente esplicito "fai subito X" senza ambiguità → esegui diretto.

CONTESTO:
Stati: 0=caricato, 1=pronto, 2=avviato, 3=terminato, 4=consegnato.
Reparti: stampa offset (XL106), digitale, fustella piana, piegaincolla, finestratura, legatoria, stampa a caldo (JOH), spedizione.

Italiano. Mai inventare dati. Mai scrivere senza conferma."""


# === Auth ===
def is_authorized(user_id: int) -> bool:
    if not ALLOWED_USER_IDS:
        return False
    return user_id in ALLOWED_USER_IDS


async def reject(update: Update) -> None:
    await update.message.reply_text(
        "⛔ Non autorizzato. ID utente: " + str(update.effective_user.id)
    )


# === Comandi fissi ===
async def cmd_start(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    uid = update.effective_user.id
    if not is_authorized(uid):
        await reject(update)
        return
    await update.message.reply_text(
        "👋 *Bot MES Grafica Nappa*\n\n"
        "*Read rapido:*\n"
        "/commessa <num> — dettaglio commessa\n"
        "/fasi [reparto] — in lavorazione (stato=2)\n"
        "/pronte [reparto] — fasi pronte (stato=1)\n"
        "/terminate — terminate oggi\n"
        "/esterne — lav. esterne\n"
        "/reparti — overview reparti\n"
        "/presenti — chi è in azienda\n"
        "/alert — macchine ferme\n"
        "/oggi — riepilogo giornaliero\n"
        "/top — top commesse offset 7gg\n\n"
        "*Sync:*\n"
        "/sync_onda /sync_prinect\n\n"
        "*Linguaggio libero:*\n"
        "\"come va 67386?\", \"termina fase 21138\", "
        "\"chi sta stampando?\", \"nota TV: consegna urgente 15h\"",
        parse_mode=ParseMode.MARKDOWN
    )


async def cmd_aiuto(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    await cmd_start(update, ctx)


async def cmd_commessa(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    if not is_authorized(update.effective_user.id):
        await reject(update)
        return
    if not ctx.args:
        await update.message.reply_text("Uso: /commessa 67386")
        return
    result = tools.get_commessa_info(ctx.args[0])
    if 'errore' in result:
        await update.message.reply_text(result['errore'])
        return

    text = f"📦 *{result['commessa']}*\n"
    o = result['ordini'][0]
    text += f"Cliente: {o['cliente_nome']}\n"
    text += f"Descrizione: {(o['descrizione'] or '')[:120]}\n"
    text += f"Qta: {o['qta_richiesta']} {o['um']}\n"
    text += f"Consegna: {o['data_prevista_consegna']}\n"
    text += f"\nFasi: {result['n_fasi_terminate']}/{result['n_fasi']} terminate\n"
    for f in result['fasi']:
        stato_emoji = {0: '⚪', 1: '🟡', 2: '🟢', 3: '✅', 4: '📦'}.get(f['stato'], '❓')
        text += f"{stato_emoji} {f['fase']}: {f['qta_prod'] or 0}\n"
    await update.message.reply_text(text, parse_mode=ParseMode.MARKDOWN)


async def cmd_alert(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    if not is_authorized(update.effective_user.id):
        await reject(update)
        return
    ferme = tools.get_macchine_ferme(soglia_minuti=30)
    if not ferme:
        await update.message.reply_text("✅ Nessuna macchina ferma >30min.")
        return
    text = f"⚠️ {len(ferme)} macchine ferme:\n\n"
    for r in ferme:
        text += f"• {r['fase']} — {r['commessa']} ({r['cliente_nome'][:25]})\n"
        text += f"  Ferma da {r['minuti_ferma']}min\n"
    await update.message.reply_text(text)


async def cmd_oggi(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    if not is_authorized(update.effective_user.id):
        await reject(update)
        return
    r = tools.get_riepilogo_giornaliero()
    text = (
        f"📊 *Oggi {r['oggi']}*\n\n"
        f"✅ Fasi terminate: {r['fasi_terminate']}\n"
        f"🟢 In lavorazione: {r['fasi_in_lavorazione']}\n"
        f"📦 Consegnate: {r['consegnate']}\n"
    )
    await update.message.reply_text(text, parse_mode=ParseMode.MARKDOWN)


async def cmd_fasi(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    """Fasi in lavorazione (stato=2). Opzionale reparto."""
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    reparto = ' '.join(ctx.args) if ctx.args else None
    fasi = tools.get_fasi_attive(reparto)
    if not fasi:
        await update.message.reply_text("Nessuna fase in lavorazione.")
        return
    text = f"🟢 {len(fasi)} fasi attive" + (f" ({reparto})" if reparto else '') + ":\n\n"
    for r in fasi[:20]:
        text += f"• {r['commessa']} {r['fase']} — qta {r['qta_prod'] or 0}\n  {r['cliente_nome'][:30]}\n"
    await update.message.reply_text(text[:4000])


async def cmd_pronte(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    """Fasi pronte (stato=1)."""
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    reparto = ' '.join(ctx.args) if ctx.args else None
    fasi = tools.get_fasi_pronte(reparto)
    if not fasi:
        await update.message.reply_text("Nessuna fase pronta.")
        return
    text = f"🟡 {len(fasi)} fasi pronte" + (f" ({reparto})" if reparto else '') + ":\n\n"
    for r in fasi[:20]:
        text += f"• {r['commessa']} {r['fase']} priorita={r['priorita']}\n"
    await update.message.reply_text(text[:4000])


async def cmd_terminate(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    """Fasi terminate oggi."""
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    fasi = tools.get_fasi_terminate_oggi()
    text = f"✅ {len(fasi)} fasi terminate oggi:\n\n"
    for r in fasi[:25]:
        text += f"• {r['commessa']} {r['fase']} qta={r['qta_prod'] or 0}\n"
    await update.message.reply_text(text[:4000] or "Nessuna fase terminata oggi.")


async def cmd_presenti(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    r = tools.get_presenti_oggi()
    text = f"👥 Presenti oggi: {r['totale']}\n\n"
    for p in r['presenti']:
        text += f"• {p['cognome_nome']} (entrata {str(p['entrata'])[11:16]})\n"
    await update.message.reply_text(text[:4000])


async def cmd_esterne(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    fasi = tools.get_lav_esterne()
    text = f"🚚 {len(fasi)} lav. esterne:\n\n"
    for r in fasi[:25]:
        text += f"• {r['commessa']} {r['fase']} stato={r['stato']} ({r['cliente_nome'][:25]})\n"
    await update.message.reply_text(text[:4000] or "Nessuna lav. esterna.")


async def cmd_reparti(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    rows = tools.get_reparti_overview()
    by_rep = {}
    for r in rows:
        by_rep.setdefault(r['reparto'] or '?', {})[r['stato']] = r['n']
    text = "📊 *Reparti overview*\n\n"
    for rep, stati in by_rep.items():
        text += f"*{rep}*: "
        text += ' '.join(f"{s}={n}" for s, n in sorted(stati.items()))
        text += '\n'
    await update.message.reply_text(text[:4000], parse_mode=ParseMode.MARKDOWN)


async def cmd_ritardo(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    """Top commesse in ritardo (summary pre-aggregato con severità)."""
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    r = tools.get_ritardi_summary()
    if r['totale_commesse_ritardo'] == 0:
        await update.message.reply_text("✅ Nessuna commessa in ritardo.")
        return
    sev = r['per_severita']
    text = f"⚠️ {r['totale_commesse_ritardo']} commesse / {r['totale_fasi_ritardo']} fasi in ritardo\n"
    text += f"Critico (>14gg): {sev['critico_>14gg']} | Alto (8-14): {sev['alto_8_14gg']} | Medio (4-7): {sev['medio_4_7gg']} | Basso (1-3): {sev['basso_1_3gg']}\n\n"
    text += "Top urgenti:\n"
    for c in r.get('commesse_top20', r.get('commesse', []))[:15]:
        text += f"• {c['commessa']} — {(c['cliente'] or '')[:25]} (+{c['giorni_ritardo']}gg) {c['n_fasi_pending']}f\n"
    await update.message.reply_text(text[:4000])


async def cmd_note(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    """Note consegne giornaliere. /note (oggi) o /note YYYY-MM-DD."""
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    data = ctx.args[0] if ctx.args else None
    rows = tools.get_note_consegne(data=data)
    if not rows:
        await update.message.reply_text(f"📝 Nessuna nota{' per ' + data if data else ' oggi'}.")
        return
    text = ""
    for r in rows:
        text += f"📝 {r['data']}\n{r['contenuto']}\n\n"
    await update.message.reply_text(text[:4000])


async def cmd_varianti(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    """Elenca varianti multi-modello per commessa. /varianti 67375"""
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    if not ctx.args:
        await update.message.reply_text("Uso: /varianti 67375")
        return
    result = tools.get_commessa_info(ctx.args[0])
    if 'errore' in result:
        await update.message.reply_text(result['errore'])
        return
    ordini = result.get('ordini', [])
    text = f"📦 {result['commessa']} — {len(ordini)} variant{'e' if len(ordini)==1 else 'i'}\n\n"
    for o in ordini:
        desc = (o.get('descrizione') or '')[:100]
        text += f"• {desc}\n"
    await update.message.reply_text(text[:4000])


async def cmd_consegne_sett(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    rows = tools.get_consegne_settimana()
    text = f"📦 {len(rows)} consegne ultimi 7gg:\n\n"
    for r in rows[:25]:
        d = str(r['data_fine'])[:10]
        text += f"• {d} {r['commessa']} — {(r['cliente_nome'] or '')[:25]}\n"
    await update.message.reply_text(text[:4000] or "Nessuna consegna 7gg.")


async def cmd_tracking(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    if not ctx.args:
        await update.message.reply_text("Uso: /tracking <numero_ddt>")
        return
    r = tools.tracking_brt(ctx.args[0])
    if 'errore' in r:
        await update.message.reply_text("❌ " + r['errore']); return
    res = r.get('risultati', [])
    if not res:
        await update.message.reply_text(f"Nessun match per DDT {ctx.args[0]}"); return
    text = ""
    for x in res:
        text += f"📦 {x['commessa']} — {x['cliente_nome']}\nDDT: {x['numero_ddt_vendita']} | Vettore: {x['vettore_ddt']}\nStato BRT: {x['stato']} ({x['fase']})\n\n"
    await update.message.reply_text(text[:4000])


async def cmd_sync_onda(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    r = tools.sync_onda()
    await update.message.reply_text("✓ " + r.get('msg', 'OK') if 'ok' in r else "❌ " + r.get('errore', ''))


async def cmd_sync_prinect(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    if not is_authorized(update.effective_user.id):
        await reject(update); return
    r = tools.sync_prinect()
    await update.message.reply_text("✓ " + r.get('msg', 'OK') if 'ok' in r else "❌ " + r.get('errore', ''))


async def cmd_top(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    if not is_authorized(update.effective_user.id):
        await reject(update)
        return
    top = tools.get_top_commesse_offset(7)
    if not top:
        await update.message.reply_text("Nessun dato.")
        return
    text = "🏆 *Top commesse offset 7gg:*\n\n"
    for i, r in enumerate(top, 1):
        scarto_pct = (r['scarto'] / (r['buoni'] + r['scarto']) * 100) if (r['buoni'] + r['scarto']) > 0 else 0
        text += f"{i}. {r['commessa_gestionale']} — {r['buoni']} fogli ({scarto_pct:.1f}% scarto)\n"
    await update.message.reply_text(text, parse_mode=ParseMode.MARKDOWN)


# === LLM handler (messaggi liberi) ===
async def handle_message(update: Update, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    uid = update.effective_user.id
    username = update.effective_user.username
    if not is_authorized(uid):
        audit_query(uid, username, 'reject', reason='not_authorized', query=update.message.text[:200])
        await reject(update)
        return

    user_msg = update.message.text
    logger.info(f"[{uid}] {user_msg}")

    # Reset history se comandi espliciti
    low = user_msg.strip().lower()
    if low in ('reset', 'nuova chat', 'clear', '/reset'):
        CONV_HISTORY.pop(uid, None)
        audit_query(uid, username, 'command', cmd='reset')
        await update.message.reply_text("🔄 Memoria conversazione resettata.")
        return

    # Carica history precedente + append nuovo messaggio user
    messages = list(get_history(uid))
    messages.append({"role": "user", "content": user_msg})
    max_iterations = 5

    # PII Pseudonimizer per-request: maschera cliente_nome/operatore_* nei tool_result
    # prima di inviarli a Claude. Restore dei token reali nel testo finale.
    pseudo = Pseudonymizer()
    tools_used: list[str] = []
    model_used = ANTHROPIC_MODEL
    fallback_used = False
    total_input_tokens = 0
    total_output_tokens = 0
    t_start = time.monotonic()

    for _ in range(max_iterations):
        # Retry su 529 Overloaded con modello fallback (Sonnet se Haiku scarico)
        try:
            resp = anthropic_client.messages.create(
                model=ANTHROPIC_MODEL,
                max_tokens=8192,
                system=SYSTEM_PROMPT,
                tools=tools.TOOLS_SCHEMA,
                messages=messages,
            )
        except Exception as e:
            if '529' in str(e) or 'overloaded' in str(e).lower():
                logger.warning(f"Anthropic 529 su {ANTHROPIC_MODEL}, retry con sonnet")
                fallback_model = 'claude-sonnet-4-5-20250929' if 'haiku' in ANTHROPIC_MODEL else 'claude-haiku-4-5-20251001'
                model_used = fallback_model
                fallback_used = True
                resp = anthropic_client.messages.create(
                    model=fallback_model,
                    max_tokens=8192,
                    system=SYSTEM_PROMPT,
                    tools=tools.TOOLS_SCHEMA,
                    messages=messages,
                )
            else:
                audit_query(uid, username, 'error',
                            query=user_msg[:500], error=str(e)[:300],
                            model=model_used, tools_used=tools_used,
                            duration_ms=int((time.monotonic() - t_start) * 1000))
                raise

        # Track token usage cumulativo (per turno tool_use può iterare più volte)
        if hasattr(resp, 'usage') and resp.usage:
            total_input_tokens += getattr(resp.usage, 'input_tokens', 0) or 0
            total_output_tokens += getattr(resp.usage, 'output_tokens', 0) or 0

        if resp.stop_reason == "tool_use":
            tool_uses = [b for b in resp.content if b.type == "tool_use"]
            messages.append({"role": "assistant", "content": resp.content})

            tool_results = []
            for tu in tool_uses:
                logger.info(f"Tool: {tu.name} {tu.input}")
                tools_used.append(tu.name)
                result = tools.dispatch_tool(tu.name, tu.input)
                # PII masking: nomi clienti/operatori → token prima del LLM
                masked = pseudo.mask(result)
                tool_results.append({
                    "type": "tool_result",
                    "tool_use_id": tu.id,
                    "content": json.dumps(masked, default=str, ensure_ascii=False),
                })
            messages.append({"role": "user", "content": tool_results})
            continue

        # Risposta finale
        text_blocks = [b.text for b in resp.content if b.type == "text"]
        final = "\n".join(text_blocks).strip() or "(nessuna risposta)"
        # Restore: token → valori reali per l'utente Telegram
        final = pseudo.restore(final)
        if pseudo.stats()['totale_token']:
            logger.info(f"PII masked: {pseudo.stats()}")
        # Strip tool_use/tool_result da history per ridurre token (rate limit Haiku 10K/min).
        clean = compact_history(messages, final)
        save_history(uid, clean)
        # GDPR art. 30 audit: 1 record per query completata
        audit_query(uid, username, 'query',
                    query=user_msg[:500],
                    response_len=len(final),
                    tools_used=tools_used,
                    pii_stats=pseudo.stats(),
                    model=model_used,
                    fallback_used=fallback_used,
                    input_tokens=total_input_tokens,
                    output_tokens=total_output_tokens,
                    duration_ms=int((time.monotonic() - t_start) * 1000))
        # Telegram limit 4096 char/msg → split su confini riga
        await send_long(update, final)
        return

    save_history(uid, messages)
    audit_query(uid, username, 'error',
                query=user_msg[:500], error='max_iterations',
                tools_used=tools_used, model=model_used,
                duration_ms=int((time.monotonic() - t_start) * 1000))
    await update.message.reply_text("⚠️ Loop tool troppo lungo, query abortita.")


# === Error handler ===
async def on_error(update: object, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    logger.error("Exception:", exc_info=ctx.error)
    if isinstance(update, Update) and update.effective_message:
        await update.effective_message.reply_text(f"❌ Errore: {ctx.error}")


# === Main ===
async def job_nuove_commesse(ctx: ContextTypes.DEFAULT_TYPE) -> None:
    """Polling ogni 5min: rileva nuove commesse importate da Onda e notifica."""
    import os.path
    state_file = os.path.join(os.path.dirname(__file__), 'last_check_ordini.txt')
    try:
        last = open(state_file).read().strip() if os.path.exists(state_file) else None
    except Exception:
        last = None
    import mysql.connector
    try:
        conn = mysql.connector.connect(
            host=os.environ['DB_HOST'],
            port=int(os.environ.get('DB_PORT', 3306)),
            database=os.environ['DB_NAME'],
            user=os.environ['DB_USER'],
            password=os.environ['DB_PASS'],
        )
        cur = conn.cursor(dictionary=True)
        if last:
            cur.execute("SELECT commessa, cliente_nome, descrizione, qta_richiesta, created_at FROM ordini WHERE created_at > %s ORDER BY created_at", (last,))
        else:
            cur.execute("SELECT MAX(created_at) AS m FROM ordini")
            last = str(cur.fetchone()['m'])
            with open(state_file, 'w') as fp:
                fp.write(last)
            cur.close(); conn.close()
            return
        nuove = cur.fetchall()
        cur.close(); conn.close()
        if not nuove:
            return
        for n in nuove:
            text = f"🆕 *Nuova commessa Onda*\n{n['commessa']} — {n['cliente_nome']}\n{(n['descrizione'] or '')[:120]}\nQta: {n['qta_richiesta']}"
            for uid in ALLOWED_USER_IDS:
                try:
                    await ctx.bot.send_message(chat_id=uid, text=text, parse_mode=ParseMode.MARKDOWN)
                except Exception as e:
                    logger.error(f"Push fallita uid={uid}: {e}")
        last_new = str(nuove[-1]['created_at'])
        with open(state_file, 'w') as fp:
            fp.write(last_new)
    except Exception as e:
        logger.error(f"job_nuove_commesse: {e}")


def main() -> None:
    if not ALLOWED_USER_IDS:
        logger.warning("ALLOWED_USER_IDS vuoto — bot rifiutera tutti i messaggi.")

    app = Application.builder().token(TELEGRAM_TOKEN).build()
    # Job notifiche proattive ogni 5min
    # Notifiche "Nuova commessa Onda" disabilitate (troppe, rumore).
    # Per riabilitare scommenta la riga sotto.
    # app.job_queue.run_repeating(job_nuove_commesse, interval=300, first=60)
    app.add_handler(CommandHandler("start", cmd_start))
    app.add_handler(CommandHandler("aiuto", cmd_aiuto))
    app.add_handler(CommandHandler("help", cmd_aiuto))
    app.add_handler(CommandHandler("commessa", cmd_commessa))
    app.add_handler(CommandHandler("alert", cmd_alert))
    app.add_handler(CommandHandler("oggi", cmd_oggi))
    app.add_handler(CommandHandler("top", cmd_top))
    app.add_handler(CommandHandler("fasi", cmd_fasi))
    app.add_handler(CommandHandler("pronte", cmd_pronte))
    app.add_handler(CommandHandler("terminate", cmd_terminate))
    app.add_handler(CommandHandler("presenti", cmd_presenti))
    app.add_handler(CommandHandler("esterne", cmd_esterne))
    app.add_handler(CommandHandler("reparti", cmd_reparti))
    app.add_handler(CommandHandler("sync_onda", cmd_sync_onda))
    app.add_handler(CommandHandler("sync_prinect", cmd_sync_prinect))
    app.add_handler(CommandHandler("ritardo", cmd_ritardo))
    app.add_handler(CommandHandler("ritardi", cmd_ritardo))
    app.add_handler(CommandHandler("note", cmd_note))
    app.add_handler(CommandHandler("varianti", cmd_varianti))
    app.add_handler(CommandHandler("consegne_sett", cmd_consegne_sett))
    app.add_handler(CommandHandler("tracking", cmd_tracking))
    app.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, handle_message))
    app.add_error_handler(on_error)

    logger.info(f"Bot avviato. Authorized users: {ALLOWED_USER_IDS}")
    app.run_polling(allowed_updates=Update.ALL_TYPES)


if __name__ == '__main__':
    main()
