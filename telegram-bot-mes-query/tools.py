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
        SELECT orf.id, orf.fase, orf.stato, orf.qta_prod, orf.fogli_buoni, orf.data_inizio,
               o.commessa, o.cliente_nome, o.descrizione, o.qta_richiesta,
               r.nome AS reparto
        FROM ordine_fasi orf
        JOIN ordini o ON o.id = orf.ordine_id
        LEFT JOIN fasi_catalogo fc ON fc.id = orf.fase_catalogo_id
        LEFT JOIN reparti r ON r.id = fc.reparto_id
        WHERE orf.stato = 2
    """
    params: tuple = ()
    if reparto:
        sql += " AND LOWER(r.nome) = LOWER(%s)"
        params = (reparto,)
    sql += " ORDER BY orf.data_inizio DESC LIMIT 50"
    return _query(sql, params)


def get_macchine_ferme(soglia_minuti: int = 30) -> list[dict]:
    """Macchine che hanno fasi avviate (stato=2) senza attivita Prinect recente."""
    sql = """
        SELECT orf.fase, o.commessa, o.cliente_nome, orf.data_inizio,
               (SELECT MAX(pa.start_time) FROM prinect_attivita pa
                WHERE pa.commessa_gestionale = o.commessa) AS ultima_att
        FROM ordine_fasi orf
        JOIN ordini o ON o.id = orf.ordine_id
        WHERE orf.stato = 2
          AND (orf.fase LIKE 'STAMPAXL%%' OR orf.fase = 'STAMPA' OR orf.fase LIKE 'STAMPA XL%%')
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
        SELECT orf.id, orf.fase, orf.stato, orf.qta_consegnata, orf.data_fine
        FROM ordine_fasi orf
        JOIN ordini o ON o.id = orf.ordine_id
        WHERE o.commessa = %s
          AND orf.fase LIKE 'BRT%%'
          AND orf.deleted_at IS NULL
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
        SELECT orf.*, o.commessa, o.cliente_nome, o.descrizione, r.nome AS reparto
        FROM ordine_fasi orf
        JOIN ordini o ON o.id = orf.ordine_id
        LEFT JOIN fasi_catalogo fc ON fc.id = orf.fase_catalogo_id
        LEFT JOIN reparti r ON r.id = fc.reparto_id
        WHERE orf.id = %s AND orf.deleted_at IS NULL
    """
    rows = _query(sql, (fase_id,))
    if not rows:
        return {'errore': f'Fase id={fase_id} non trovata'}
    return rows[0]


def cerca_fasi(commessa: str | None = None, fase: str | None = None,
               stato: str | None = None, reparto: str | None = None,
               limit: int = 30) -> list[dict]:
    """Cerca fasi con filtri opzionali."""
    where = ["orf.deleted_at IS NULL"]
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
        where.append("orf.fase LIKE %s")
        params.append(f"%{fase}%")
    if stato is not None and stato != '':
        where.append("orf.stato = %s")
        params.append(str(stato))
    if reparto:
        where.append("LOWER(r.nome) LIKE LOWER(%s)")
        params.append(f"%{reparto}%")

    sql = f"""
        SELECT orf.id, orf.fase, orf.stato, orf.qta_prod, orf.data_inizio, orf.data_fine,
               o.commessa, o.cliente_nome, o.descrizione,
               r.nome AS reparto
        FROM ordine_fasi orf
        JOIN ordini o ON o.id = orf.ordine_id
        LEFT JOIN fasi_catalogo fc ON fc.id = orf.fase_catalogo_id
        LEFT JOIN reparti r ON r.id = fc.reparto_id
        WHERE {' AND '.join(where)}
        ORDER BY orf.id DESC
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
        SELECT orf.id, orf.fase, orf.stato, o.commessa, o.cliente_nome, o.descrizione,
               ofo.data_inizio, ofo.data_fine
        FROM ordine_fase_operatore ofo
        JOIN ordine_fasi orf ON orf.id = ofo.ordine_fase_id
        JOIN ordini o ON o.id = orf.ordine_id
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
        "name": "get_lav_esterne",
        "description": "Fasi esterne (esterno=1). Opzionale filtro stato (1=pronta da inviare, 5=inviato, 3=ricevuto).",
        "input_schema": {
            "type": "object",
            "properties": {"stato": {"type": "string"}},
        },
    },
    {
        "name": "invia_a_esterno",
        "description": "MODIFICA: marca fase come inviata a fornitore esterno (stato=5, note='Inviato a: X'). USA dopo conferma.",
        "input_schema": {
            "type": "object",
            "properties": {
                "fase_id": {"type": "integer"},
                "fornitore": {"type": "string"},
            },
            "required": ["fase_id", "fornitore"],
        },
    },
    {
        "name": "ricevuta_da_esterno",
        "description": "MODIFICA: marca fase rientrata dal fornitore (stato=3, data_fine). USA dopo conferma.",
        "input_schema": {
            "type": "object",
            "properties": {"fase_id": {"type": "integer"}},
            "required": ["fase_id"],
        },
    },
    {
        "name": "get_presenti_oggi",
        "description": "Operatori attualmente presenti in azienda oggi (entrata senza uscita).",
        "input_schema": {"type": "object", "properties": {}},
    },
    {
        "name": "sync_onda",
        "description": "AZIONE: lancia sync Onda (importa nuove commesse). USA dopo conferma utente.",
        "input_schema": {"type": "object", "properties": {}},
    },
    {
        "name": "sync_prinect",
        "description": "AZIONE: lancia sync Prinect (aggiorna fasi stampa offset). USA dopo conferma utente.",
        "input_schema": {"type": "object", "properties": {}},
    },
    {
        "name": "aggiungi_riga_commessa",
        "description": "MODIFICA: aggiunge nuova fase a commessa esistente. USA dopo conferma utente.",
        "input_schema": {
            "type": "object",
            "properties": {
                "commessa": {"type": "string"},
                "fase": {"type": "string", "description": "Codice fase es. PI01, FIN01"},
                "qta_fase": {"type": "integer"},
                "priorita": {"type": "number"},
            },
            "required": ["commessa", "fase", "qta_fase"],
        },
    },
    {
        "name": "elimina_fase",
        "description": "MODIFICA: soft-delete fase (recuperabile). USA dopo conferma utente.",
        "input_schema": {
            "type": "object",
            "properties": {"fase_id": {"type": "integer"}},
            "required": ["fase_id"],
        },
    },
    {
        "name": "aggiorna_bulk_fasi",
        "description": "MODIFICA: update bulk su molte fasi (campo whitelist: stato/priorita/note/qta_prod/qta_fase). Filtri opzionali. USA con MOLTA cautela + conferma.",
        "input_schema": {
            "type": "object",
            "properties": {
                "campo": {"type": "string"},
                "valore": {"type": "string"},
                "commessa": {"type": "string"},
                "reparto": {"type": "string"},
                "stato_da": {"type": "string"},
            },
            "required": ["campo", "valore"],
        },
    },
    {
        "name": "ricalcola_stati_commessa",
        "description": "AZIONE: ricalcola stati/priorita per una commessa via artisan. USA dopo conferma.",
        "input_schema": {
            "type": "object",
            "properties": {"commessa": {"type": "string"}},
            "required": ["commessa"],
        },
    },
    {
        "name": "avvia_fase",
        "description": "MODIFICA: avvia fase (stato=2, data_inizio=NOW). Opzionale assegna operatore. USA dopo conferma.",
        "input_schema": {
            "type": "object",
            "properties": {
                "fase_id": {"type": "integer"},
                "operatore_id": {"type": "integer"},
            },
            "required": ["fase_id"],
        },
    },
    {
        "name": "termina_fase",
        "description": "MODIFICA: termina fase (stato=3, data_fine=NOW). qta_prod e scarti opzionali. USA dopo conferma.",
        "input_schema": {
            "type": "object",
            "properties": {
                "fase_id": {"type": "integer"},
                "qta_prod": {"type": "integer"},
                "scarti": {"type": "integer"},
            },
            "required": ["fase_id"],
        },
    },
    {
        "name": "set_cliche",
        "description": "MODIFICA: assegna numero cliché a ordine. USA dopo conferma.",
        "input_schema": {
            "type": "object",
            "properties": {
                "ordine_id": {"type": "integer"},
                "cliche_numero": {"type": "string"},
            },
            "required": ["ordine_id", "cliche_numero"],
        },
    },
    {
        "name": "clear_cliche",
        "description": "MODIFICA: rimuove cliché da ordine.",
        "input_schema": {
            "type": "object",
            "properties": {"ordine_id": {"type": "integer"}},
            "required": ["ordine_id"],
        },
    },
    {
        "name": "salva_nota_spedizione",
        "description": "MODIFICA: salva nota giornaliera spedizione (data oggi).",
        "input_schema": {
            "type": "object",
            "properties": {"testo": {"type": "string"}},
            "required": ["testo"],
        },
    },
    {
        "name": "salva_nota_tv",
        "description": "MODIFICA: salva nota ticker TV reparto (24h). USA dopo conferma.",
        "input_schema": {
            "type": "object",
            "properties": {"testo": {"type": "string"}},
            "required": ["testo"],
        },
    },
    {
        "name": "get_reparti_overview",
        "description": "Overview conteggio fasi per reparto e stato.",
        "input_schema": {"type": "object", "properties": {}},
    },
    {
        "name": "get_alert_ritardi",
        "description": "Operatori in ritardo oggi (entrata dopo 08:15).",
        "input_schema": {"type": "object", "properties": {}},
    },
    {
        "name": "get_audit_log",
        "description": "Audit log modifiche recenti. Opzionale filtro commessa.",
        "input_schema": {
            "type": "object",
            "properties": {
                "commessa": {"type": "string"},
                "limit": {"type": "integer", "default": 30},
            },
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
        SELECT orf.id, orf.fase, orf.qta_prod, orf.data_fine,
               o.commessa, o.cliente_nome,
               r.nome AS reparto
        FROM ordine_fasi orf
        JOIN ordini o ON o.id = orf.ordine_id
        LEFT JOIN fasi_catalogo fc ON fc.id = orf.fase_catalogo_id
        LEFT JOIN reparti r ON r.id = fc.reparto_id
        WHERE orf.stato = '3'
          AND DATE(orf.data_fine) = %s
          AND orf.deleted_at IS NULL
    """
    params: tuple = (oggi,)
    if reparto:
        sql += " AND LOWER(r.nome) LIKE LOWER(%s)"
        params = (oggi, f"%{reparto}%")
    sql += " ORDER BY orf.data_fine DESC LIMIT 100"
    return _query(sql, params)


def get_lav_esterne(stato: str | None = None) -> list[dict]:
    """Fasi esterne (esterno=1): inviate o da inviare a fornitori esterni."""
    sql = """
        SELECT orf.id, orf.fase, orf.stato, orf.note, orf.data_inizio, orf.data_fine,
               o.commessa, o.cliente_nome, o.descrizione, o.data_prevista_consegna
        FROM ordine_fasi orf
        JOIN ordini o ON o.id = orf.ordine_id
        WHERE orf.esterno = 1 AND orf.deleted_at IS NULL
    """
    params: tuple = ()
    if stato is not None:
        sql += " AND orf.stato = %s"
        params = (str(stato),)
    sql += " ORDER BY o.data_prevista_consegna ASC LIMIT 50"
    return _query(sql, params)


def invia_a_esterno(fase_id: int, fornitore: str) -> dict:
    """Marca fase come inviata a fornitore esterno: note + stato=5 (EXT inviato)."""
    nota = f"Inviato a: {fornitore}"
    n = _execute(
        "UPDATE ordine_fasi SET stato = '5', esterno = 1, note = %s, data_inizio = NOW(), updated_at = NOW() WHERE id = %s AND deleted_at IS NULL",
        (nota, int(fase_id))
    )
    if n == 0:
        return {'errore': f'Fase id={fase_id} non trovata'}
    return {'ok': True, 'fase_id': fase_id, 'fornitore': fornitore}


def ricevuta_da_esterno(fase_id: int) -> dict:
    """Marca fase ricevuta dal fornitore: stato=3 terminato, data_fine=NOW."""
    n = _execute(
        "UPDATE ordine_fasi SET stato = '3', data_fine = NOW(), updated_at = NOW() WHERE id = %s AND deleted_at IS NULL",
        (int(fase_id),)
    )
    if n == 0:
        return {'errore': f'Fase id={fase_id} non trovata'}
    return {'ok': True, 'fase_id': fase_id}


def get_presenti_oggi() -> dict:
    """Operatori presenti in azienda oggi (tabella presenze, uscita NULL)."""
    oggi = datetime.now().strftime('%Y-%m-%d')
    sql_pres = """
        SELECT cognome_nome, MIN(timestamp) AS entrata, MAX(timestamp) AS ultima
        FROM presenze
        WHERE DATE(timestamp) = %s AND tipo = 'I'
        GROUP BY cognome_nome
        HAVING NOT EXISTS (
            SELECT 1 FROM presenze p2
            WHERE p2.cognome_nome = presenze.cognome_nome
              AND DATE(p2.timestamp) = %s
              AND p2.tipo = 'O'
              AND p2.timestamp > MAX(presenze.timestamp)
        )
        ORDER BY cognome_nome
    """
    try:
        presenti = _query(sql_pres, (oggi, oggi))
    except Exception:
        presenti = []
    return {'data': oggi, 'totale': len(presenti), 'presenti': presenti}


def sync_onda() -> dict:
    """Lancia sync Onda (artisan onda:sync). Aspetta fine + ritorna esito."""
    import subprocess
    laravel_path = os.environ.get('LARAVEL_PATH', r'C:\progetti\gestionale-v2')
    try:
        result = subprocess.run(
            ['php', 'artisan', 'onda:sync'],
            cwd=laravel_path, capture_output=True, text=True, timeout=300
        )
        out = (result.stdout or '').strip()
        err = (result.stderr or '').strip()
        # Filtra MIB noise
        clean_out = '\n'.join(l for l in out.splitlines() if 'Cannot find module' not in l and 'MIB search' not in l)
        if result.returncode == 0:
            return {'ok': True, 'output': clean_out[-800:] or 'Completato senza output'}
        return {'errore': f'Exit {result.returncode}: {err[-500:] or clean_out[-500:]}'}
    except subprocess.TimeoutExpired:
        return {'errore': 'Timeout 5min su sync Onda'}
    except Exception as e:
        return {'errore': str(e)}


def sync_prinect() -> dict:
    """Lancia sync Prinect (artisan prinect:sync). Aspetta fine + ritorna esito."""
    import subprocess
    laravel_path = os.environ.get('LARAVEL_PATH', r'C:\progetti\gestionale-v2')
    try:
        result = subprocess.run(
            ['php', 'artisan', 'prinect:sync'],
            cwd=laravel_path, capture_output=True, text=True, timeout=180
        )
        out = (result.stdout or '').strip()
        err = (result.stderr or '').strip()
        clean_out = '\n'.join(l for l in out.splitlines() if 'Cannot find module' not in l and 'MIB search' not in l)
        if result.returncode == 0:
            return {'ok': True, 'output': clean_out[-800:] or 'Completato senza output'}
        return {'errore': f'Exit {result.returncode}: {err[-500:] or clean_out[-500:]}'}
    except subprocess.TimeoutExpired:
        return {'errore': 'Timeout 3min su sync Prinect'}
    except Exception as e:
        return {'errore': str(e)}


def aggiungi_riga_commessa(commessa: str, fase: str, qta_fase: int,
                            priorita: float = 0.0) -> dict:
    """Aggiunge nuova fase a una commessa esistente."""
    if commessa.isdigit() and 4 <= len(commessa) <= 7:
        commessa_padded = commessa.zfill(7) + '-26'
    else:
        commessa_padded = commessa
    ord = _query("SELECT id FROM ordini WHERE commessa = %s LIMIT 1", (commessa_padded,))
    if not ord:
        return {'errore': f'Commessa {commessa_padded} non trovata'}
    fc = _query("SELECT id, reparto_id FROM fasi_catalogo WHERE fase = %s LIMIT 1", (fase,))
    fcat_id = fc[0]['id'] if fc else None
    reparto_id = fc[0]['reparto_id'] if fc else None
    n = _execute(
        "INSERT INTO ordine_fasi (ordine_id, fase, fase_catalogo_id, reparto_id, qta_fase, "
        "stato, priorita, created_at, updated_at) "
        "VALUES (%s, %s, %s, %s, %s, '0', %s, NOW(), NOW())",
        (ord[0]['id'], fase, fcat_id, reparto_id, int(qta_fase), float(priorita))
    )
    return {'ok': True, 'commessa': commessa_padded, 'fase': fase, 'qta': qta_fase}


def elimina_fase(fase_id: int) -> dict:
    """Soft delete fase (set deleted_at = NOW)."""
    n = _execute("UPDATE ordine_fasi SET deleted_at = NOW() WHERE id = %s AND deleted_at IS NULL", (int(fase_id),))
    if n == 0:
        return {'errore': f'Fase id={fase_id} non trovata o gia eliminata'}
    return {'ok': True, 'fase_id': fase_id}


def aggiorna_bulk_fasi(campo: str, valore: str, commessa: str | None = None,
                       reparto: str | None = None, stato_da: str | None = None) -> dict:
    """Update bulk su ordine_fasi. Solo campi whitelisted."""
    whitelist = {'stato', 'priorita', 'note', 'qta_prod', 'qta_fase'}
    if campo not in whitelist:
        return {'errore': f'Campo non permesso: {campo}. Whitelist: {whitelist}'}
    where = ["orf.deleted_at IS NULL"]
    params = [valore]
    if commessa:
        if commessa.isdigit() and 4 <= len(commessa) <= 7:
            commessa = commessa.zfill(7) + '-26'
        where.append("o.commessa = %s")
        params.append(commessa)
    if reparto:
        where.append("LOWER(r.nome) LIKE LOWER(%s)")
        params.append(f"%{reparto}%")
    if stato_da is not None and stato_da != '':
        where.append("orf.stato = %s")
        params.append(str(stato_da))
    sql = (f"UPDATE ordine_fasi orf "
           f"JOIN ordini o ON o.id = orf.ordine_id "
           f"LEFT JOIN fasi_catalogo fc ON fc.id = orf.fase_catalogo_id "
           f"LEFT JOIN reparti r ON r.id = fc.reparto_id "
           f"SET orf.{campo} = %s, orf.updated_at = NOW() "
           f"WHERE {' AND '.join(where)}")
    n = _execute(sql, tuple(params))
    return {'ok': True, 'campo': campo, 'valore': valore, 'fasi_modificate': n}


def ricalcola_stati_commessa(commessa: str) -> dict:
    """Ricalcola stati fasi via comando artisan."""
    import subprocess
    if commessa.isdigit() and 4 <= len(commessa) <= 7:
        commessa = commessa.zfill(7) + '-26'
    laravel_path = os.environ.get('LARAVEL_PATH', r'C:\progetti\gestionale-v2')
    try:
        result = subprocess.run(
            ['php', 'artisan', 'priorita:ricalcola'],
            cwd=laravel_path, capture_output=True, text=True, timeout=60
        )
        return {'ok': True, 'commessa': commessa, 'output': (result.stdout or result.stderr)[:500]}
    except Exception as e:
        return {'errore': str(e)}


def avvia_fase(fase_id: int, operatore_id: int | None = None) -> dict:
    """Avvia fase: stato=2, data_inizio=NOW."""
    n = _execute(
        "UPDATE ordine_fasi SET stato = '2', data_inizio = NOW(), updated_at = NOW() "
        "WHERE id = %s AND deleted_at IS NULL",
        (int(fase_id),)
    )
    if n == 0:
        return {'errore': f'Fase id={fase_id} non trovata'}
    if operatore_id:
        try:
            _execute(
                "INSERT IGNORE INTO fase_operatore (fase_id, operatore_id, data_inizio, created_at, updated_at) "
                "VALUES (%s, %s, NOW(), NOW(), NOW())",
                (int(fase_id), int(operatore_id))
            )
        except Exception:
            pass
    return {'ok': True, 'fase_id': fase_id, 'stato': 2}


def termina_fase(fase_id: int, qta_prod: int | None = None,
                 scarti: int | None = None) -> dict:
    """Termina fase: stato=3, data_fine=NOW, qta_prod e scarti opzionali."""
    sets = ["stato = '3'", "data_fine = NOW()", "updated_at = NOW()"]
    params = []
    if qta_prod is not None:
        sets.append("qta_prod = %s")
        params.append(int(qta_prod))
    if scarti is not None:
        sets.append("scarti = %s")
        params.append(int(scarti))
    params.append(int(fase_id))
    n = _execute(
        f"UPDATE ordine_fasi SET {', '.join(sets)} WHERE id = %s AND deleted_at IS NULL",
        tuple(params)
    )
    if n == 0:
        return {'errore': f'Fase id={fase_id} non trovata'}
    return {'ok': True, 'fase_id': fase_id, 'stato': 3, 'qta_prod': qta_prod, 'scarti': scarti}


def set_cliche(ordine_id: int, cliche_numero: str) -> dict:
    """Assegna cliché a un ordine."""
    n = _execute(
        "UPDATE ordini SET cliche_numero = %s, cliche_match_type = 'manual', "
        "cliche_matched_at = NOW(), updated_at = NOW() WHERE id = %s",
        (cliche_numero, int(ordine_id))
    )
    if n == 0:
        return {'errore': f'Ordine id={ordine_id} non trovato'}
    return {'ok': True, 'ordine_id': ordine_id, 'cliche_numero': cliche_numero}


def clear_cliche(ordine_id: int) -> dict:
    """Rimuove cliché da ordine."""
    n = _execute(
        "UPDATE ordini SET cliche_numero = NULL, cliche_match_type = NULL, "
        "cliche_matched_at = NULL, updated_at = NOW() WHERE id = %s",
        (int(ordine_id),)
    )
    return {'ok': True, 'ordine_id': ordine_id}


def salva_nota_spedizione(testo: str) -> dict:
    """Salva nota giornaliera spedizione (data oggi, campo contenuto_pm)."""
    oggi = datetime.now().strftime('%Y-%m-%d')
    n = _execute(
        "INSERT INTO note_spedizione (data, contenuto_pm, created_at, updated_at) "
        "VALUES (%s, %s, NOW(), NOW()) "
        "ON DUPLICATE KEY UPDATE contenuto_pm = %s, updated_at = NOW()",
        (oggi, testo, testo)
    )
    return {'ok': True, 'data': oggi, 'nota': testo}


def salva_nota_tv(testo: str) -> dict:
    """Salva nota ticker TV via subprocess php (Laravel Cache)."""
    import subprocess
    laravel_path = os.environ.get('LARAVEL_PATH', r'C:\progetti\gestionale-v2')
    safe_text = testo.replace("'", "\\'")
    try:
        result = subprocess.run(
            ['php', 'artisan', 'tinker', '--execute',
             f"Cache::put('kiosk_nota_tv', '{safe_text}', now()->addHours(24));"],
            cwd=laravel_path, capture_output=True, text=True, timeout=30
        )
        return {'ok': True, 'nota': testo}
    except Exception as e:
        return {'errore': str(e)}


def get_reparti_overview() -> list[dict]:
    """Conteggio fasi per reparto + stato (overview)."""
    sql = """
        SELECT r.nome AS reparto, orf.stato, COUNT(*) AS n
        FROM ordine_fasi orf
        LEFT JOIN fasi_catalogo fc ON fc.id = orf.fase_catalogo_id
        LEFT JOIN reparti r ON r.id = fc.reparto_id
        WHERE orf.deleted_at IS NULL AND orf.stato IN ('0','1','2','3')
        GROUP BY r.nome, orf.stato
        ORDER BY r.nome, orf.stato
    """
    return _query(sql)


def get_alert_ritardi() -> list[dict]:
    """Operatori in ritardo oggi (entrata dopo orario turno). Semplice query."""
    oggi = datetime.now().strftime('%Y-%m-%d')
    sql = """
        SELECT cognome_nome, MIN(timestamp) AS entrata
        FROM presenze
        WHERE DATE(timestamp) = %s AND tipo = 'I'
        GROUP BY cognome_nome
        HAVING TIME(MIN(timestamp)) > '08:15:00'
        ORDER BY entrata DESC
        LIMIT 30
    """
    try:
        return _query(sql, (oggi,))
    except Exception:
        return []


def get_audit_log(commessa: str | None = None, limit: int = 30) -> list[dict]:
    """Audit log ultime modifiche. Opzionale per commessa."""
    if commessa:
        if commessa.isdigit() and 4 <= len(commessa) <= 7:
            commessa = commessa.zfill(7) + '-26'
        sql = """
            SELECT al.*, o.commessa
            FROM audit_logs al
            LEFT JOIN ordini o ON o.id = al.ordine_id
            WHERE o.commessa = %s
            ORDER BY al.created_at DESC LIMIT %s
        """
        try:
            return _query(sql, (commessa, int(limit)))
        except Exception:
            return []
    sql = "SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT %s"
    try:
        return _query(sql, (int(limit),))
    except Exception:
        return []


def get_fasi_pronte(reparto: str | None = None) -> list[dict]:
    """Fasi stato=1 (pronte ma non avviate). Da fare prossime."""
    sql = """
        SELECT orf.id, orf.fase, orf.priorita, orf.qta_fase,
               o.commessa, o.cliente_nome, o.descrizione, o.data_prevista_consegna,
               r.nome AS reparto
        FROM ordine_fasi orf
        JOIN ordini o ON o.id = orf.ordine_id
        LEFT JOIN fasi_catalogo fc ON fc.id = orf.fase_catalogo_id
        LEFT JOIN reparti r ON r.id = fc.reparto_id
        WHERE orf.stato = '1' AND orf.deleted_at IS NULL
    """
    params: tuple = ()
    if reparto:
        sql += " AND LOWER(r.nome) LIKE LOWER(%s)"
        params = (f"%{reparto}%",)
    sql += " ORDER BY orf.priorita ASC LIMIT 50"
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
        'get_lav_esterne': get_lav_esterne,
        'invia_a_esterno': invia_a_esterno,
        'ricevuta_da_esterno': ricevuta_da_esterno,
        'get_presenti_oggi': get_presenti_oggi,
        'sync_onda': sync_onda,
        'sync_prinect': sync_prinect,
        'aggiungi_riga_commessa': aggiungi_riga_commessa,
        'elimina_fase': elimina_fase,
        'aggiorna_bulk_fasi': aggiorna_bulk_fasi,
        'ricalcola_stati_commessa': ricalcola_stati_commessa,
        'avvia_fase': avvia_fase,
        'termina_fase': termina_fase,
        'set_cliche': set_cliche,
        'clear_cliche': clear_cliche,
        'salva_nota_spedizione': salva_nota_spedizione,
        'salva_nota_tv': salva_nota_tv,
        'get_reparti_overview': get_reparti_overview,
        'get_alert_ritardi': get_alert_ritardi,
        'get_audit_log': get_audit_log,
    }.get(name)
    if not fn:
        return {'errore': f'Tool sconosciuto: {name}'}
    try:
        return fn(**args)
    except Exception as e:
        tb = traceback.format_exc()
        print(f"[TOOL ERROR] {name}({args}): {e}\n{tb}", flush=True)
        return {'errore': f'{type(e).__name__}: {e}'}
