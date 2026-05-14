"""
Tool functions per interrogare DB MES.
Ogni funzione = tool esposto a Claude API per tool use.
Read + Write (audit su tabella sensori_letture per traccia).
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


def _execute(sql: str, params: tuple = ()) -> int:
    """UPDATE/INSERT/DELETE, ritorna rows affected."""
    conn = _conn()
    try:
        cur = conn.cursor()
        cur.execute(sql, params)
        conn.commit()
        n = cur.rowcount
        cur.close()
        return n
    finally:
        conn.close()


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

    def _stato_int(s):
        try:
            return int(s)
        except (TypeError, ValueError):
            return -1

    return {
        'commessa': commessa_padded,
        'ordini': ordini,
        'fasi': fasi,
        'n_fasi': len(fasi),
        'n_fasi_terminate': sum(1 for f in fasi if _stato_int(f.get('stato')) >= 3),
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


def get_stato_consegna(commessa: str) -> dict:
    """Stato consegna commessa basato SOLO su fasi BRT1 (reparto spedizione).
    Regola: tutte BRT1 stato=4 → TOTALE. Almeno una <4 → PARZIALE. Nessuna BRT1 → NON_SPEDITA.
    """
    if commessa.isdigit() and 4 <= len(commessa) <= 7:
        commessa_padded = commessa.zfill(7) + '-26'
    else:
        commessa_padded = commessa

    sql = """
        SELECT of.id, of.fase, of.stato, of.qta_consegnata, of.data_fine
        FROM ordine_fasi of
        JOIN ordini o ON o.id = of.ordine_id
        WHERE o.commessa = %s
          AND of.fase LIKE 'BRT%%'
          AND of.deleted_at IS NULL
    """
    fasi = _query(sql, (commessa_padded,))
    if not fasi:
        return {'commessa': commessa_padded, 'esito': 'NON_SPEDITA', 'n_fasi_brt': 0}

    stati = []
    for f in fasi:
        try:
            stati.append(int(f['stato']))
        except (TypeError, ValueError):
            stati.append(-1)

    all4 = all(s == 4 for s in stati)
    if all4:
        esito = 'TOTALE'
    else:
        esito = 'PARZIALE'

    return {
        'commessa': commessa_padded,
        'esito': esito,
        'n_fasi_brt': len(fasi),
        'stati_brt': stati,
    }


def get_fase_dettaglio(fase_id: int) -> dict:
    """Dettaglio singola fase: stati, qta, ordine, operatori."""
    sql = """
        SELECT of.*, o.commessa, o.cliente_nome, o.descrizione, r.nome AS reparto
        FROM ordine_fasi of
        JOIN ordini o ON o.id = of.ordine_id
        LEFT JOIN fasi_catalogo fc ON fc.fase = of.fase
        LEFT JOIN reparti r ON r.id = fc.reparto_id
        WHERE of.id = %s AND of.deleted_at IS NULL
    """
    rows = _query(sql, (fase_id,))
    if not rows:
        return {'errore': f'Fase id={fase_id} non trovata'}
    return rows[0]


def cerca_fasi(commessa: str | None = None, fase: str | None = None,
               stato: str | None = None, reparto: str | None = None,
               limit: int = 30) -> list[dict]:
    """Cerca fasi con filtri opzionali."""
    where = ["of.deleted_at IS NULL"]
    params = []
    if commessa:
        if commessa.isdigit() and 4 <= len(commessa) <= 7:
            commessa_padded = commessa.zfill(7) + '-26'
            where.append("o.commessa = %s")
            params.append(commessa_padded)
        else:
            where.append("o.commessa LIKE %s")
            params.append(f"%{commessa}%")
    if fase:
        where.append("of.fase LIKE %s")
        params.append(f"%{fase}%")
    if stato is not None and stato != '':
        where.append("of.stato = %s")
        params.append(str(stato))
    if reparto:
        where.append("LOWER(r.nome) LIKE LOWER(%s)")
        params.append(f"%{reparto}%")

    sql = f"""
        SELECT of.id, of.fase, of.stato, of.qta_prod, of.data_inizio, of.data_fine,
               o.commessa, o.cliente_nome, o.descrizione,
               r.nome AS reparto
        FROM ordine_fasi of
        JOIN ordini o ON o.id = of.ordine_id
        LEFT JOIN fasi_catalogo fc ON fc.fase = of.fase
        LEFT JOIN reparti r ON r.id = fc.reparto_id
        WHERE {' AND '.join(where)}
        ORDER BY of.id DESC
        LIMIT {int(limit)}
    """
    return _query(sql, tuple(params))


# === WRITE FUNCTIONS ===
def aggiorna_stato_fase(fase_id: int, nuovo_stato: int) -> dict:
    """Modifica stato fase (0=caricato, 1=pronto, 2=avviato, 3=terminato, 4=consegnato)."""
    if not (0 <= int(nuovo_stato) <= 4):
        return {'errore': 'Stato deve essere tra 0 e 4'}
    n = _execute(
        "UPDATE ordine_fasi SET stato = %s, updated_at = NOW() WHERE id = %s AND deleted_at IS NULL",
        (str(nuovo_stato), int(fase_id))
    )
    if n == 0:
        return {'errore': f'Fase id={fase_id} non trovata o stato gia uguale'}
    return {'ok': True, 'fase_id': fase_id, 'nuovo_stato': nuovo_stato}


def aggiorna_qta_prod(fase_id: int, qta: int) -> dict:
    """Aggiorna qta_prod fase."""
    n = _execute(
        "UPDATE ordine_fasi SET qta_prod = %s, updated_at = NOW() WHERE id = %s AND deleted_at IS NULL",
        (int(qta), int(fase_id))
    )
    if n == 0:
        return {'errore': f'Fase id={fase_id} non trovata'}
    return {'ok': True, 'fase_id': fase_id, 'qta_prod': qta}


def aggiorna_nota_fase(fase_id: int, nota: str) -> dict:
    """Aggiorna campo note di una fase."""
    n = _execute(
        "UPDATE ordine_fasi SET note = %s, updated_at = NOW() WHERE id = %s AND deleted_at IS NULL",
        (nota, int(fase_id))
    )
    if n == 0:
        return {'errore': f'Fase id={fase_id} non trovata'}
    return {'ok': True, 'fase_id': fase_id, 'note': nota}


def aggiorna_priorita_manuale(fase_id: int, priorita: float) -> dict:
    """Imposta priorita_manuale (flag) + valore priorita."""
    n = _execute(
        "UPDATE ordine_fasi SET priorita = %s, priorita_manuale = 1, updated_at = NOW() WHERE id = %s AND deleted_at IS NULL",
        (float(priorita), int(fase_id))
    )
    if n == 0:
        return {'errore': f'Fase id={fase_id} non trovata'}
    return {'ok': True, 'fase_id': fase_id, 'priorita': priorita, 'manuale': True}


def marca_terminata_manualmente(fase_id: int, valore: bool = True) -> dict:
    """Imposta flag terminata_manualmente (protegge fase da riapertura auto sync)."""
    n = _execute(
        "UPDATE ordine_fasi SET terminata_manualmente = %s, updated_at = NOW() WHERE id = %s AND deleted_at IS NULL",
        (1 if valore else 0, int(fase_id))
    )
    if n == 0:
        return {'errore': f'Fase id={fase_id} non trovata'}
    return {'ok': True, 'fase_id': fase_id, 'terminata_manualmente': bool(valore)}


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
        "name": "get_stato_consegna",
        "description": "Stato consegna commessa (TOTALE/PARZIALE/NON_SPEDITA) basato SULLE FASI BRT1. Tutte BRT1 stato=4 → TOTALE. Almeno una <4 → PARZIALE. USA QUESTO per domande 'consegnata totale o parziale?'.",
        "input_schema": {
            "type": "object",
            "properties": {
                "commessa": {"type": "string", "description": "Numero commessa"}
            },
            "required": ["commessa"],
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
    {
        "name": "get_fasi_terminate_oggi",
        "description": "Fasi terminate (stato=3) oggi. Opzionale filtro reparto.",
        "input_schema": {
            "type": "object",
            "properties": {"reparto": {"type": "string"}},
        },
    },
    {
        "name": "get_fasi_pronte",
        "description": "Fasi pronte stato=1 (mai avviate), ordinate per priorita.",
        "input_schema": {
            "type": "object",
            "properties": {"reparto": {"type": "string"}},
        },
    },
    {
        "name": "get_fase_dettaglio",
        "description": "Dettaglio completo di una fase per id (stato, qta, ordine, reparto).",
        "input_schema": {
            "type": "object",
            "properties": {"fase_id": {"type": "integer"}},
            "required": ["fase_id"],
        },
    },
    {
        "name": "cerca_fasi",
        "description": "Cerca fasi con filtri opzionali (commessa, fase, stato, reparto). Ritorna fino a 30 risultati.",
        "input_schema": {
            "type": "object",
            "properties": {
                "commessa": {"type": "string"},
                "fase": {"type": "string", "description": "Es. PI01, FIN01, STAMPAXL106"},
                "stato": {"type": "string", "description": "0,1,2,3,4"},
                "reparto": {"type": "string"},
                "limit": {"type": "integer", "default": 30},
            },
        },
    },
    {
        "name": "aggiorna_stato_fase",
        "description": "MODIFICA stato di una fase (0=caricato, 1=pronto, 2=avviato, 3=terminato, 4=consegnato). USA SOLO dopo conferma esplicita utente.",
        "input_schema": {
            "type": "object",
            "properties": {
                "fase_id": {"type": "integer"},
                "nuovo_stato": {"type": "integer"},
            },
            "required": ["fase_id", "nuovo_stato"],
        },
    },
    {
        "name": "aggiorna_qta_prod",
        "description": "MODIFICA qta_prod (quantita prodotta) fase. USA SOLO dopo conferma utente.",
        "input_schema": {
            "type": "object",
            "properties": {
                "fase_id": {"type": "integer"},
                "qta": {"type": "integer"},
            },
            "required": ["fase_id", "qta"],
        },
    },
    {
        "name": "aggiorna_nota_fase",
        "description": "MODIFICA note di una fase. USA SOLO dopo conferma utente.",
        "input_schema": {
            "type": "object",
            "properties": {
                "fase_id": {"type": "integer"},
                "nota": {"type": "string"},
            },
            "required": ["fase_id", "nota"],
        },
    },
    {
        "name": "aggiorna_priorita_manuale",
        "description": "MODIFICA priorita fase + setta flag manuale. USA SOLO dopo conferma utente.",
        "input_schema": {
            "type": "object",
            "properties": {
                "fase_id": {"type": "integer"},
                "priorita": {"type": "number"},
            },
            "required": ["fase_id", "priorita"],
        },
    },
    {
        "name": "marca_terminata_manualmente",
        "description": "Imposta flag terminata_manualmente (protegge da riapertura auto). USA SOLO dopo conferma utente.",
        "input_schema": {
            "type": "object",
            "properties": {
                "fase_id": {"type": "integer"},
                "valore": {"type": "boolean", "default": True},
            },
            "required": ["fase_id"],
        },
    },
]


def get_fasi_terminate_oggi(reparto: str | None = None) -> list[dict]:
    """Fasi terminate (stato=3) oggi. Opzionale filtro reparto."""
    oggi = datetime.now().strftime('%Y-%m-%d')
    sql = """
        SELECT of.id, of.fase, of.qta_prod, of.data_fine,
               o.commessa, o.cliente_nome,
               r.nome AS reparto
        FROM ordine_fasi of
        JOIN ordini o ON o.id = of.ordine_id
        LEFT JOIN fasi_catalogo fc ON fc.fase = of.fase
        LEFT JOIN reparti r ON r.id = fc.reparto_id
        WHERE of.stato = '3'
          AND DATE(of.data_fine) = %s
          AND of.deleted_at IS NULL
    """
    params: tuple = (oggi,)
    if reparto:
        sql += " AND LOWER(r.nome) LIKE LOWER(%s)"
        params = (oggi, f"%{reparto}%")
    sql += " ORDER BY of.data_fine DESC LIMIT 100"
    return _query(sql, params)


def get_fasi_pronte(reparto: str | None = None) -> list[dict]:
    """Fasi stato=1 (pronte ma non avviate). Da fare prossime."""
    sql = """
        SELECT of.id, of.fase, of.priorita, of.qta_fase,
               o.commessa, o.cliente_nome, o.descrizione, o.data_prevista_consegna,
               r.nome AS reparto
        FROM ordine_fasi of
        JOIN ordini o ON o.id = of.ordine_id
        LEFT JOIN fasi_catalogo fc ON fc.fase = of.fase
        LEFT JOIN reparti r ON r.id = fc.reparto_id
        WHERE of.stato = '1' AND of.deleted_at IS NULL
    """
    params: tuple = ()
    if reparto:
        sql += " AND LOWER(r.nome) LIKE LOWER(%s)"
        params = (f"%{reparto}%",)
    sql += " ORDER BY of.priorita ASC LIMIT 50"
    return _query(sql, params)


def dispatch_tool(name: str, args: dict) -> Any:
    """Dispatch tool call → funzione Python."""
    import traceback
    fn = {
        'get_commessa_info': get_commessa_info,
        'get_fasi_attive': get_fasi_attive,
        'get_fasi_terminate_oggi': get_fasi_terminate_oggi,
        'get_fasi_pronte': get_fasi_pronte,
        'get_macchine_ferme': get_macchine_ferme,
        'get_riepilogo_giornaliero': get_riepilogo_giornaliero,
        'get_top_commesse_offset': get_top_commesse_offset,
        'get_stato_consegna': get_stato_consegna,
        'get_operatore_fasi_oggi': get_operatore_fasi_oggi,
        'get_fase_dettaglio': get_fase_dettaglio,
        'cerca_fasi': cerca_fasi,
        'aggiorna_stato_fase': aggiorna_stato_fase,
        'aggiorna_qta_prod': aggiorna_qta_prod,
        'aggiorna_nota_fase': aggiorna_nota_fase,
        'aggiorna_priorita_manuale': aggiorna_priorita_manuale,
        'marca_terminata_manualmente': marca_terminata_manualmente,
    }.get(name)
    if not fn:
        return {'errore': f'Tool sconosciuto: {name}'}
    try:
        return fn(**args)
    except Exception as e:
        tb = traceback.format_exc()
        print(f"[TOOL ERROR] {name}({args}): {e}\n{tb}", flush=True)
        return {'errore': f'{type(e).__name__}: {e}'}
