"""
Tool functions per interrogare DB MES.
Ogni funzione = tool esposto a Claude API per tool use.
Tutte sono READ-ONLY (no UPDATE/INSERT/DELETE).
"""
import os
import mysql.connector
from datetime import datetime, timedelta
from typing import Any


def _conn():
    return mysql.connector.connect(
        host=os.environ['DB_HOST'],
        port=int(os.environ.get('DB_PORT', 3306)),
        database=os.environ['DB_NAME'],
        user=os.environ['DB_USER'],
        password=os.environ['DB_PASS'],
        connection_timeout=10,
    )


def _query(sql: str, params: tuple = ()) -> list[dict]:
    conn = _conn()
    try:
        cur = conn.cursor(dictionary=True)
        cur.execute(sql, params)
        rows = cur.fetchall()
        cur.close()
        return rows
    finally:
        conn.close()


def get_commessa_info(commessa: str) -> dict:
    """Dettaglio commessa: cliente, descrizione, fasi, qta, stato."""
    # Pad numero se solo cifre (es. "67386" → "0067386-26")
    if commessa.isdigit() and 4 <= len(commessa) <= 7:
        commessa_padded = commessa.zfill(7) + '-26'
    else:
        commessa_padded = commessa

    ordini = _query(
        "SELECT id, commessa, cliente_nome, cod_art, descrizione, qta_richiesta, um, "
        "data_prevista_consegna, qta_carta, carta, qta_ddt_vendita "
        "FROM ordini WHERE commessa = %s",
        (commessa_padded,),
    )
    if not ordini:
        return {'errore': f'Commessa {commessa_padded} non trovata.'}

    ordine_ids = [o['id'] for o in ordini]
    placeholders = ','.join(['%s'] * len(ordine_ids))
    fasi = _query(
        f"SELECT id, ordine_id, fase, stato, qta_prod, fogli_buoni, fogli_scarto, "
        f"data_inizio, data_fine "
        f"FROM ordine_fasi WHERE ordine_id IN ({placeholders}) ORDER BY id",
        tuple(ordine_ids),
    )

    return {
        'commessa': commessa_padded,
        'ordini': ordini,
        'fasi': fasi,
        'n_fasi': len(fasi),
        'n_fasi_terminate': sum(1 for f in fasi if f['stato'] >= 3),
    }


def get_fasi_attive(reparto: str | None = None) -> list[dict]:
    """Fasi attualmente in lavorazione (stato=2). Opzionale filtro per reparto."""
    sql = """
        SELECT of.id, of.fase, of.stato, of.qta_prod, of.fogli_buoni, of.data_inizio,
               o.commessa, o.cliente_nome, o.descrizione, o.qta_richiesta,
               r.nome AS reparto
        FROM ordine_fasi of
        JOIN ordini o ON o.id = of.ordine_id
        LEFT JOIN fasi_catalogo fc ON fc.fase = of.fase
        LEFT JOIN reparti r ON r.id = fc.reparto_id
        WHERE of.stato = 2
    """
    params: tuple = ()
    if reparto:
        sql += " AND LOWER(r.nome) = LOWER(%s)"
        params = (reparto,)
    sql += " ORDER BY of.data_inizio DESC LIMIT 50"
    return _query(sql, params)


def get_macchine_ferme(soglia_minuti: int = 30) -> list[dict]:
    """Macchine che hanno fasi avviate (stato=2) senza attivita Prinect recente."""
    sql = """
        SELECT of.fase, o.commessa, o.cliente_nome, of.data_inizio,
               (SELECT MAX(pa.start_time) FROM prinect_attivita pa
                WHERE pa.commessa_gestionale = o.commessa) AS ultima_att
        FROM ordine_fasi of
        JOIN ordini o ON o.id = of.ordine_id
        WHERE of.stato = 2
          AND (of.fase LIKE 'STAMPAXL%%' OR of.fase = 'STAMPA' OR of.fase LIKE 'STAMPA XL%%')
    """
    rows = _query(sql)
    ora = datetime.now()
    ferme = []
    for r in rows:
        ult = r.get('ultima_att')
        if not ult:
            continue
        diff_min = (ora - ult).total_seconds() / 60
        if diff_min >= soglia_minuti:
            r['minuti_ferma'] = int(diff_min)
            ferme.append(r)
    return ferme


def get_riepilogo_giornaliero() -> dict:
    """Riepilogo oggi: fasi terminate, ore lavorate, fasi in lavorazione."""
    oggi = datetime.now().strftime('%Y-%m-%d')
    fasi_term = _query(
        "SELECT COUNT(*) AS n FROM ordine_fasi WHERE stato >= 3 AND DATE(data_fine) = %s",
        (oggi,),
    )
    fasi_attive = _query(
        "SELECT COUNT(*) AS n FROM ordine_fasi WHERE stato = 2",
    )
    consegnate = _query(
        "SELECT COUNT(*) AS n FROM ordine_fasi WHERE stato = 4 AND DATE(data_fine) = %s",
        (oggi,),
    )
    return {
        'oggi': oggi,
        'fasi_terminate': fasi_term[0]['n'],
        'fasi_in_lavorazione': fasi_attive[0]['n'],
        'consegnate': consegnate[0]['n'],
    }


def get_top_commesse_offset(giorni: int = 7) -> list[dict]:
    """Top commesse per fogli buoni stampa offset ultimi N giorni (Prinect)."""
    sql = """
        SELECT commessa_gestionale, prinect_job_name,
               SUM(good_cycles) AS buoni, SUM(waste_cycles) AS scarto,
               COUNT(*) AS n_attivita
        FROM prinect_attivita
        WHERE start_time >= NOW() - INTERVAL %s DAY
          AND commessa_gestionale IS NOT NULL
        GROUP BY commessa_gestionale, prinect_job_name
        ORDER BY buoni DESC
        LIMIT 10
    """
    return _query(sql, (giorni,))


def get_operatore_fasi_oggi(nome: str) -> list[dict]:
    """Fasi a cui un operatore ha lavorato oggi (via pivot ordine_fase_operatore)."""
    oggi = datetime.now().strftime('%Y-%m-%d')
    sql = """
        SELECT of.id, of.fase, of.stato, o.commessa, o.cliente_nome, o.descrizione,
               ofo.data_inizio, ofo.data_fine
        FROM ordine_fase_operatore ofo
        JOIN ordine_fasi of ON of.id = ofo.ordine_fase_id
        JOIN ordini o ON o.id = of.ordine_id
        JOIN operatori op ON op.id = ofo.operatore_id
        WHERE LOWER(op.nome) LIKE LOWER(%s)
          AND DATE(ofo.data_inizio) = %s
        ORDER BY ofo.data_inizio DESC
    """
    return _query(sql, (f'%{nome}%', oggi))


# === SCHEMA Anthropic tool use ===
TOOLS_SCHEMA = [
    {
        "name": "get_commessa_info",
        "description": "Recupera dettaglio commessa MES: cliente, descrizione, qta, fasi, stati. Input: numero commessa (es. '67386' o '0067386-26').",
        "input_schema": {
            "type": "object",
            "properties": {
                "commessa": {"type": "string", "description": "Numero commessa (5-7 cifre o codice completo)"}
            },
            "required": ["commessa"],
        },
    },
    {
        "name": "get_fasi_attive",
        "description": "Lista fasi attualmente in lavorazione (stato=2 'Avviato'). Opzionalmente filtra per reparto.",
        "input_schema": {
            "type": "object",
            "properties": {
                "reparto": {"type": "string", "description": "Nome reparto (es. 'stampa offset', 'piegaincolla', 'fustella piana'). Lasciare vuoto per tutti."}
            },
        },
    },
    {
        "name": "get_macchine_ferme",
        "description": "Macchine offset che hanno fasi avviate ma nessuna attivita Prinect da N minuti.",
        "input_schema": {
            "type": "object",
            "properties": {
                "soglia_minuti": {"type": "integer", "description": "Soglia minuti di inattivita (default 30)"}
            },
        },
    },
    {
        "name": "get_riepilogo_giornaliero",
        "description": "Riepilogo produzione oggi: fasi terminate, fasi in lavorazione, consegnate.",
        "input_schema": {"type": "object", "properties": {}},
    },
    {
        "name": "get_top_commesse_offset",
        "description": "Top 10 commesse per fogli buoni stampa offset ultimi N giorni (dati Prinect).",
        "input_schema": {
            "type": "object",
            "properties": {
                "giorni": {"type": "integer", "description": "Periodo in giorni (default 7)"}
            },
        },
    },
    {
        "name": "get_operatore_fasi_oggi",
        "description": "Fasi a cui un operatore ha lavorato oggi.",
        "input_schema": {
            "type": "object",
            "properties": {
                "nome": {"type": "string", "description": "Nome operatore (anche parziale)"}
            },
            "required": ["nome"],
        },
    },
]


def dispatch_tool(name: str, args: dict) -> Any:
    """Dispatch tool call → funzione Python."""
    fn = {
        'get_commessa_info': get_commessa_info,
        'get_fasi_attive': get_fasi_attive,
        'get_macchine_ferme': get_macchine_ferme,
        'get_riepilogo_giornaliero': get_riepilogo_giornaliero,
        'get_top_commesse_offset': get_top_commesse_offset,
        'get_operatore_fasi_oggi': get_operatore_fasi_oggi,
    }.get(name)
    if not fn:
        return {'errore': f'Tool sconosciuto: {name}'}
    try:
        return fn(**args)
    except Exception as e:
        return {'errore': str(e)}
