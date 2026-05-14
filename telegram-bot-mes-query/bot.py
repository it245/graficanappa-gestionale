"""
Bot Telegram MES Grafica Nappa — interrogazione gestionale.
Comandi fissi + LLM Claude per query naturali.
"""
import json
import logging
import os
from datetime import datetime

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

anthropic_client = Anthropic(api_key=ANTHROPIC_API_KEY)

SYSTEM_PROMPT = """Sei un assistente per il MES di Grafica Nappa (tipografia, Italia).
Rispondi in italiano, sintetico, focus su dati produzione: commesse, fasi, macchine, operatori, scarti, tempi.

Stati fase: 0=caricato, 1=pronto, 2=avviato (in lavorazione), 3=terminato, 4=consegnato.
Reparti principali: stampa offset (XL106), digitale, fustella piana, piegaincolla, finestratura, legatoria, stampa a caldo (JOH).

Usa i tool per recuperare dati dal DB MES. Per le tabelle usa formato compatto.
Mai inventare dati: se i tool non danno risposta, dillo chiaramente.

Per messaggi conversazionali (saluti, grazie, ecc.) rispondi brevemente senza chiamare tool."""


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
        "👋 Bot MES Grafica Nappa pronto.\n\n"
        "Comandi:\n"
        "/commessa <num> — dettaglio commessa\n"
        "/alert — macchine ferme >30min\n"
        "/oggi — riepilogo giornaliero\n"
        "/top — top commesse offset 7gg\n"
        "/aiuto — questo messaggio\n\n"
        "Oppure scrivi liberamente: \"come va 67386?\", \"chi sta stampando?\""
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
    if not is_authorized(uid):
        await reject(update)
        return

    user_msg = update.message.text
    logger.info(f"[{uid}] {user_msg}")

    # Inizia tool-use loop
    messages = [{"role": "user", "content": user_msg}]
    max_iterations = 5

    for _ in range(max_iterations):
        resp = anthropic_client.messages.create(
            model=ANTHROPIC_MODEL,
            max_tokens=2048,
            system=SYSTEM_PROMPT,
            tools=tools.TOOLS_SCHEMA,
            messages=messages,
        )

        if resp.stop_reason == "tool_use":
            tool_uses = [b for b in resp.content if b.type == "tool_use"]
            messages.append({"role": "assistant", "content": resp.content})

            tool_results = []
            for tu in tool_uses:
                logger.info(f"Tool: {tu.name} {tu.input}")
                result = tools.dispatch_tool(tu.name, tu.input)
                tool_results.append({
                    "type": "tool_result",
                    "tool_use_id": tu.id,
                    "content": json.dumps(result, default=str, ensure_ascii=False),
                })
            messages.append({"role": "user", "content": tool_results})
            continue

        # Risposta finale
        text_blocks = [b.text for b in resp.content if b.type == "text"]
        final = "\n".join(text_blocks).strip() or "(nessuna risposta)"
        await update.message.reply_text(final[:4000])
        return

    await update.message.reply_text("⚠️ Loop tool troppo lungo, query abortita.")


# === Error handler ===
async def on_error(update: object, ctx: ContextTypes.DEFAULT_TYPE) -> None:
    logger.error("Exception:", exc_info=ctx.error)
    if isinstance(update, Update) and update.effective_message:
        await update.effective_message.reply_text(f"❌ Errore: {ctx.error}")


# === Main ===
def main() -> None:
    if not ALLOWED_USER_IDS:
        logger.warning("ALLOWED_USER_IDS vuoto — bot rifiutera tutti i messaggi.")

    app = Application.builder().token(TELEGRAM_TOKEN).build()
    app.add_handler(CommandHandler("start", cmd_start))
    app.add_handler(CommandHandler("aiuto", cmd_aiuto))
    app.add_handler(CommandHandler("help", cmd_aiuto))
    app.add_handler(CommandHandler("commessa", cmd_commessa))
    app.add_handler(CommandHandler("alert", cmd_alert))
    app.add_handler(CommandHandler("oggi", cmd_oggi))
    app.add_handler(CommandHandler("top", cmd_top))
    app.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, handle_message))
    app.add_error_handler(on_error)

    logger.info(f"Bot avviato. Authorized users: {ALLOWED_USER_IDS}")
    app.run_polling(allowed_updates=Update.ALL_TYPES)


if __name__ == '__main__':
    main()
