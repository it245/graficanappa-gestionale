# -*- coding: utf-8 -*-
"""Genera 5 PDF di contesto per Claude — MES Grafica Nappa."""
import os
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm
from reportlab.lib import colors
from reportlab.lib.enums import TA_LEFT, TA_JUSTIFY
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak, Preformatted, KeepTogether
)
from reportlab.pdfgen import canvas

OUT_DIR = os.path.dirname(os.path.abspath(__file__))

# ---------- Stili ----------
styles = getSampleStyleSheet()
H1 = ParagraphStyle("H1", parent=styles["Heading1"], fontName="Helvetica-Bold",
                    fontSize=18, spaceAfter=12, textColor=colors.HexColor("#1a3a6c"))
H2 = ParagraphStyle("H2", parent=styles["Heading2"], fontName="Helvetica-Bold",
                    fontSize=13, spaceAfter=8, spaceBefore=10,
                    textColor=colors.HexColor("#1a3a6c"))
H3 = ParagraphStyle("H3", parent=styles["Heading3"], fontName="Helvetica-Bold",
                    fontSize=11, spaceAfter=4, spaceBefore=6,
                    textColor=colors.HexColor("#444"))
BODY = ParagraphStyle("Body", parent=styles["BodyText"], fontName="Helvetica",
                      fontSize=10, leading=13.5, alignment=TA_JUSTIFY,
                      spaceAfter=6)
BULL = ParagraphStyle("Bull", parent=BODY, leftIndent=14, bulletIndent=4,
                      spaceAfter=2, alignment=TA_LEFT)
MONO = ParagraphStyle("Mono", parent=BODY, fontName="Courier", fontSize=8.5,
                      leading=10.5, leftIndent=8, textColor=colors.HexColor("#222"))
META = ParagraphStyle("Meta", parent=BODY, fontSize=8.5, textColor=colors.grey,
                      alignment=TA_LEFT)


def header_footer(canvas_obj, doc):
    canvas_obj.saveState()
    canvas_obj.setFont("Helvetica-Bold", 9)
    canvas_obj.setFillColor(colors.HexColor("#1a3a6c"))
    canvas_obj.drawString(2*cm, A4[1] - 1.2*cm, "MES Grafica Nappa")
    canvas_obj.setFont("Helvetica", 8)
    canvas_obj.setFillColor(colors.grey)
    canvas_obj.drawRightString(A4[0] - 2*cm, A4[1] - 1.2*cm,
                               doc.title or "")
    canvas_obj.setStrokeColor(colors.HexColor("#1a3a6c"))
    canvas_obj.setLineWidth(0.5)
    canvas_obj.line(2*cm, A4[1] - 1.35*cm, A4[0] - 2*cm, A4[1] - 1.35*cm)
    canvas_obj.setFont("Helvetica", 8)
    canvas_obj.setFillColor(colors.grey)
    canvas_obj.drawString(2*cm, 1.2*cm, "Documento di contesto — uso interno")
    canvas_obj.drawRightString(A4[0] - 2*cm, 1.2*cm, f"Pag. {doc.page}")
    canvas_obj.restoreState()


def build_pdf(filename, title, story):
    path = os.path.join(OUT_DIR, filename)
    doc = SimpleDocTemplate(
        path, pagesize=A4, title=title,
        leftMargin=2*cm, rightMargin=2*cm,
        topMargin=2*cm, bottomMargin=2*cm,
    )
    doc.build(story, onFirstPage=header_footer, onLaterPages=header_footer)
    return path


def P(text, style=BODY):
    return Paragraph(text, style)


def UL(items, style=BULL):
    return [Paragraph(f"&bull;&nbsp;&nbsp;{x}", style) for x in items]


def code_block(text):
    return Preformatted(text, MONO)


def make_table(data, col_widths=None, header=True):
    t = Table(data, colWidths=col_widths, repeatRows=1 if header else 0)
    style = [
        ("FONT", (0, 0), (-1, -1), "Helvetica", 8.5),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("GRID", (0, 0), (-1, -1), 0.3, colors.HexColor("#bbb")),
        ("LEFTPADDING", (0, 0), (-1, -1), 4),
        ("RIGHTPADDING", (0, 0), (-1, -1), 4),
        ("TOPPADDING", (0, 0), (-1, -1), 3),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3),
    ]
    if header:
        style += [
            ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#1a3a6c")),
            ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
            ("FONT", (0, 0), (-1, 0), "Helvetica-Bold", 8.5),
        ]
    style += [("ROWBACKGROUNDS", (0, 1), (-1, -1),
               [colors.white, colors.HexColor("#f4f6fa")])]
    t.setStyle(TableStyle(style))
    return t


# ============================================================
# PDF 1 — Architettura MES
# ============================================================
def doc1():
    s = []
    s += [P("01 — Architettura del MES", H1)]
    s += [P("Documento di contesto sulla struttura tecnica del MES "
            "(Manufacturing Execution System) di Grafica Nappa.", META)]
    s += [Spacer(1, 6)]

    s += [P("Stack tecnologico", H2)]
    s += UL([
        "<b>PHP 8.2+</b> (target 8.5) — runtime applicativo.",
        "<b>Laravel 12</b> — framework HTTP, ORM Eloquent, code, eventi, "
        "broadcasting (Reverb WebSocket).",
        "<b>MySQL</b> sul server <i>192.168.1.60</i> — database operativo del MES.",
        "<b>SQL Server</b> (Onda ERP) — sola lettura, fonte ufficiale anagrafiche "
        "commesse e articoli.",
        "<b>Blade + Alpine.js + Tailwind-like CSS</b> — UI server-rendered con "
        "interattivita leggera; PWA installabile su tablet.",
        "<b>Reverb / Pusher protocol</b> — notifiche push in tempo reale fra dashboard "
        "owner, operatore e spedizione.",
        "<b>Job queue</b> (database driver) — sync Onda, sync Prinect/Fiery, "
        "ricalcolo fasi, snapshot accounting.",
    ])

    s += [P("Organizzazione modulare (DDD)", H2)]
    s += [P("Il dominio e suddiviso in 9 moduli sotto <font face='Courier'>"
            "app/Modules/</font>. Ogni modulo segue la stessa struttura: "
            "<i>Contracts</i> (interfacce), <i>Enums</i> (valori finiti), "
            "<i>Rules</i> (validazioni di dominio), <i>Services</i> (use case "
            "applicativi), <i>Events</i> (effetti laterali), <i>ValueObjects</i> "
            "(immutabili).", BODY)]

    moduli = [
        ["Modulo", "Scopo", "Contenuti chiave"],
        ["Documenti",
         "Generazione PDF tecnici (scheda produzione, etichette DataMatrix, DDT).",
         "Contracts, Generators, Rules, Services, ValueObjects"],
        ["Fasi",
         "State machine delle fasi di lavorazione, transizioni, propagazione stati.",
         "Enums, Events, Exceptions, Rules, Services, StateMachine"],
        ["Macchine",
         "Registry macchine produttive, capacita, vincoli, mapping fase->macchina.",
         "MacchinaRegistry, Models, Rules, Services"],
        ["Magazzino",
         "Carico/scarico carta, lotti, movimenti, riservazioni per commessa.",
         "Enums, Events, Rules, Services, ValueObjects"],
        ["Notifiche",
         "Push browser, Telegram, in-app — fan-out per ruolo destinatario.",
         "Senders, Templates, Services, ValueObjects"],
        ["Operatori",
         "Anagrafica, ruoli, codice alfanumerico + PIN, permessi granulari.",
         "Enums, Permessi, Rules, Services"],
        ["Scheduling",
         "Mossa 37 — ottimizzatore priorita, batching, propagazione setup.",
         "Contracts, Enums, Rules, Services, ValueObjects"],
        ["Spedizione",
         "Pianificazione consegne, integrazione BRT SOAP, DDT, note bidirezionali.",
         "Enums, Events, Rules, Services, ValueObjects"],
        ["Stampa",
         "Adapter Prinect (XL106) e Fiery (V900/Indigo), snapshot accounting.",
         "Adapters, Contracts, Enums, Rules, Services, ValueObjects"],
    ]
    s += [make_table(moduli, col_widths=[2.6*cm, 6.4*cm, 7.4*cm])]
    s += [Spacer(1, 8)]

    s += [P("Dipendenze fra moduli (testuale)", H2)]
    diag = """
                       +------------------+
                       |    Operatori     |   (auth, ruoli, permessi)
                       +---------+--------+
                                 |
        +------------------------+------------------------+
        |                        |                        |
   +----v-----+            +-----v------+           +-----v-----+
   |  Fasi    |<---events--|  Stampa    |           | Scheduling|
   |  (FSM)   |            | (Prinect/  |---hint--->| (Mossa37) |
   +----+-----+            |   Fiery)   |           +-----+-----+
        |                  +-----+------+                 |
        |                        |                        |
   +----v-----+            +-----v------+           +-----v-----+
   | Macchine |            | Magazzino  |           | Notifiche |
   +----+-----+            +-----+------+           +-----+-----+
        |                        |                        |
        +-----------+------------+------------------------+
                    |
              +-----v------+              +------------+
              | Spedizione |--BRT SOAP--->| Documenti  |
              +------------+              +------------+
"""
    s += [code_block(diag)]

    s += [PageBreak()]
    s += [P("Convenzioni di codice", H2)]
    s += UL([
        "<b>Namespace:</b> <font face='Courier'>App\\Modules\\&lt;Modulo&gt;\\...</font>; "
        "i Service NON dipendono da Eloquent direttamente, ricevono repository.",
        "<b>Enums PHP 8.1:</b> stati fase, ruoli, tipologie macchina — niente magic strings.",
        "<b>Rules:</b> classi <i>SingleResponsibility</i> richiamabili da Form Request o Service.",
        "<b>Events:</b> Laravel events; listener iscritti in EventServiceProvider del modulo.",
        "<b>FQCN sempre:</b> nei <font face='Courier'>tinker</font> e nelle migration evitare alias ambigui.",
        "<b>Italiano nel dominio:</b> <font face='Courier'>fasi_catalogo</font>, <font face='Courier'>ordini</font>, "
        "<font face='Courier'>commesse</font>, <font face='Courier'>operatori</font> — in linea con il linguaggio del business.",
    ])

    s += [P("Database — tabelle principali", H2)]
    db = [
        ["Tabella", "Ruolo"],
        ["commesse", "Aggregato testata commessa Onda."],
        ["ordini", "Riga lavorazione (ogni 'fila' della commessa)."],
        ["fasi", "Istanze di fase per ogni ordine."],
        ["fasi_catalogo", "Catalogo fasi (CODICE -> reparto, sequenza)."],
        ["ordine_fasi", "Pivot ordine-fase con stato corrente, priorita, flag esterno."],
        ["operatore_fase", "Pivot operatore-fase: timbrature, pause, motivi."],
        ["ddt_spedizione", "Spedizioni BRT (multi-collo possibile)."],
        ["note_consegne", "Comunicazioni bidirezionali owner <-> spedizione."],
        ["movimenti_magazzino", "Carico/scarico carta con riferimento commessa."],
    ]
    s += [make_table(db, col_widths=[4.2*cm, 12.2*cm])]

    s += [P("Integrazioni esterne", H2)]
    s += UL([
        "<b>Onda ERP (SQL Server):</b> sync orario commesse + articoli; sola lettura.",
        "<b>Prinect REST API (XL106):</b> polling 1 min; tempi avviamento/esecuzione, fogli.",
        "<b>Fiery API (V900/Indigo):</b> polling 1 min v5 + accounting storico.",
        "<b>BRT SOAP:</b> creazione spedizioni, esiti, tracking.",
        "<b>NetTime (presenze):</b> share SMB + task ogni 5 min su .34.",
        "<b>Telegram bot:</b> notifiche operative (Windows service via nssm).",
        "<b>Solar-Log:</b> scraping portale, KPI fotovoltaico.",
    ])

    return build_pdf("01_Architettura_MES.pdf", "01 - Architettura MES", s)


# ============================================================
# PDF 2 — Flusso Produzione
# ============================================================
def doc2():
    s = []
    s += [P("02 — Flusso di produzione", H1)]
    s += [P("Ciclo di vita di una commessa: dall'ingresso in Onda alla "
            "consegna al cliente. Documento di riferimento per Claude su "
            "tipologie prodotto, sequenze fasi e ruolo dei fornitori esterni.", META)]
    s += [Spacer(1, 6)]

    s += [P("Lifecycle commessa", H2)]
    s += [P("La commessa nasce sempre in <b>Onda ERP</b> (testata + righe). Il MES "
            "la importa via SOAP/SQL ogni ora, oppure su richiesta con "
            "<font face='Courier'>php artisan onda:sync &lt;numero&gt;</font>. "
            "Da quel momento il MES e l'unica fonte di verita sullo stato di "
            "avanzamento; Onda resta riferimento per anagrafica, prezzo e DDT.", BODY)]
    flow = """
   [Onda ERP]
       |  sync orario / on-demand
       v
   [Commessa MES]
       |  esplosione righe
       v
   [Ordini] --(per ciascuno)--> [Fasi catalogo] = sequenza lavorazioni
                                       |
                                       v
                                 [ordine_fasi]   stato 0->1->2->3->4
                                       |
                                       +--> [operatore_fase]  timbrature
                                       +--> [Stampa adapter]  Prinect/Fiery
                                       +--> [Scheduling]      Mossa37
                                       v
                                 [Spedizione] --> [BRT] --> Cliente
"""
    s += [code_block(flow)]

    s += [P("Sequenza fasi (codici reparto)", H2)]
    seq = [
        ["Cod.", "Fase", "Reparto"],
        ["10", "Stampa offset", "stampa offset (XL106)"],
        ["11", "Stampa digitale", "stampa digitale (V900, Indigo)"],
        ["20", "Plastificazione", "allestimento"],
        ["30", "Stampa a caldo", "JOH (collo bottiglia)"],
        ["31", "Plastica lux", "allestimento"],
        ["35", "Finitura digitale", "finitura digitale (ZUND, MGI)"],
        ["37", "Taglio", "allestimento"],
        ["39", "Rilievi BOBST", "fustella piana (config rilievi)"],
        ["40", "Fustella", "fustella piana (config fustelle)"],
        ["100", "Finestratura", "allestimento"],
        ["110", "Piegaincolla", "PI01 / PI02 / PI03"],
        ["120", "Legatoria", "allestimento"],
        ["700", "Allestimento manuale", "allestimento"],
        ["999", "Spedizione", "spedizione"],
    ]
    s += [make_table(seq, col_widths=[1.5*cm, 5.5*cm, 9.4*cm])]

    s += [P("Diagramma sequenza tipica (offset + nobilitazione + astuccio)", H2)]
    seq_g = """
   10  --> 30   --> 31   --> 39   --> 40   --> 110  --> 999
  offset  caldo   plast   rilievi fustella piega   spedizione
   XL106   JOH    lux     BOBST   BOBST   incolla   BRT
"""
    s += [code_block(seq_g)]

    s += [PageBreak()]
    s += [P("Esempio commessa reale: astuccio 4 colori + plastica + caldo + fustella", H2)]
    es = [
        ["Step", "Fase", "Macchina", "Note"],
        ["1", "Prestampa", "—", "imposizione, prove colore, fustella FS####"],
        ["2", "Stampa offset 4C", "XL106 (10)", "tiratura + 5% scarto, 24h"],
        ["3", "Plastificazione opaca", "Linea plast (20)", "wait minimo 4h dopo stampa"],
        ["4", "Stampa a caldo (foil oro)", "JOH (30)", "collo bottiglia, terzo turno consigliato"],
        ["5", "Rilievi", "BOBST (39)", "config rilievi"],
        ["6", "Fustella", "BOBST (40)", "cambio config 1h dal punto precedente"],
        ["7", "Piegaincolla", "PI02 (110)", "batch entro +/- 5 gg per saturare"],
        ["8", "Imballaggio + DDT BRT", "Spedizione (999)", "etichetta DataMatrix"],
    ]
    s += [make_table(es, col_widths=[1.0*cm, 4.6*cm, 4.0*cm, 6.8*cm])]

    s += [P("Tipologie commesse (ricorrenti)", H2)]
    s += UL([
        "<b>Astucci pieghevoli</b> (cosmetica/farma): GC1/GC2 250-350 g/m^2, "
        "stampa offset + nobilitazioni + fustella + piegaincolla.",
        "<b>Opuscoli/depliant:</b> offset 4+1, piega, taglio. Spesso senza fustella.",
        "<b>Etichette IML</b> (in-mould labels): stampa offset, taglio ZUND, "
        "tolleranze stringenti.",
        "<b>Cartonati</b>: cartone teso, accoppiatura, fustella, montaggio manuale.",
        "<b>Tirature digitali</b>: V900/Indigo per quantita basse o varianti.",
    ])

    s += [P("Quando ricorrere a fornitori esterni (stato 5 / EXT)", H2)]
    s += UL([
        "<b>MGI foil digitale</b> per nobilitazioni che la JOH non puo fare "
        "(grafica variabile, formati piccoli ad alta tiratura).",
        "<b>Brossura/cucitura filo refe</b> per legatoria fuori capacita interna.",
        "<b>Tagli particolari</b> richiedenti macchinari non presenti.",
        "<b>Picchi di carico:</b> dirottamento parziale per rispettare consegna.",
    ])
    s += [P("Le fasi esterne sono escluse dalla dashboard operatore e dal "
            "scheduler interno; lo stato 5 resta finche il fornitore non "
            "consegna, dopodiche torna a 3 o 4 manualmente.", BODY)]

    return build_pdf("02_Flusso_Produzione.pdf", "02 - Flusso Produzione", s)


# ============================================================
# PDF 3 — Macchine e capacita
# ============================================================
def doc3():
    s = []
    s += [P("03 — Macchine e capacita", H1)]
    s += [P("Inventario delle macchine produttive, regole di setup e criteri di "
            "assegnazione fra reparti analoghi.", META)]
    s += [Spacer(1, 6)]

    s += [P("Tabella macchine", H2)]
    mac = [
        ["Macchina", "Reparto", "Tipologia", "Turni", "Cap. tipica", "Setup"],
        ["Heidelberg XL106", "Stampa offset", "Offset 6+L", "24h x 5gg", "12.000-18.000 fogli/h", "30-90 min"],
        ["Canon V900", "Stampa digitale", "Toner B2", "06-22 lun-ven", "4.500 SRA3/h", "<5 min"],
        ["HP Indigo", "Stampa digitale", "Toner liquido", "06-22 lun-ven", "3.500 SRA3/h", "<5 min"],
        ["JOH", "Allestimento (caldo)", "Stampa a caldo", "06-22 (3 turni racc.)", "Variabile", "45-90 min"],
        ["BOBST piana", "Fustella piana", "Fustellatrice 2 config", "06-22", "5.000-7.000 fogli/h", "60 min cambio config"],
        ["STEL", "Fustella", "Fustella cilindrica", "06-22", "Bassa tiratura", "30-60 min"],
        ["MGI", "Finitura digitale", "Foil digitale", "06-22", "Bassa tiratura", "10-15 min"],
        ["ZUND", "Finitura digitale", "Plotter da taglio", "06-22", "Variabile", "10 min"],
        ["Linea piegaincolla", "Allestimento", "PI01 / PI02 / PI03", "06-22", "8.000-15.000 pz/h", "60 min cambio config"],
    ]
    s += [make_table(mac, col_widths=[2.7*cm, 2.6*cm, 2.7*cm, 2.7*cm, 3.0*cm, 2.7*cm])]

    s += [P("Regole speciali", H2)]
    s += [P("<b>BOBST — cambio configurazione 1h.</b> La macchina ha due assetti: "
            "<i>rilievi</i> (sequenza 39) e <i>fustelle</i> (sequenza 40). Lo "
            "scheduler raggruppa lavori dello stesso assetto in blocchi consecutivi "
            "per assorbire il setup.", BODY)]
    s += [P("<b>Piegaincolla — 3 configurazioni.</b> PI01, PI02, PI03 condividono la "
            "stessa linea fisica; il cambio costa 1h. Stesso principio di batch "
            "consecutivo dello scheduler.", BODY)]
    s += [P("<b>JOH — terzo turno raccomandato.</b> E il <i>collo di bottiglia</i> "
            "principale (peso > BOBST > Piegaincolla). Nei picchi, attivare il "
            "turno notturno permette di smaltire l'arretrato senza spostare "
            "scadenze.", BODY)]
    s += [P("<b>XL106 24/7 lun-ven.</b> La sola macchina con turno continuo; quando "
            "scarica, lo scheduler anticipa lavori a bassa priorita per non "
            "sprecare capacita.", BODY)]
    s += [P("<b>Batching urgenza ±5 giorni.</b> Lavori con consegna entro 5 giorni "
            "dal target attuale possono essere accorpati per affinita di setup; "
            "oltre la finestra l'urgenza prevale sempre.", BODY)]

    s += [PageBreak()]
    s += [P("Quando assegnare a reparto X vs Y", H2)]
    s += [P("<b>Stampa offset vs digitale.</b> Soglia indicativa <i>~2.000 fogli</i>: "
            "sotto -> digitale (nessun setup), sopra -> offset (costo unitario "
            "minore). Eccezioni: dato variabile -> sempre digitale; carte speciali "
            "non gestibili da V900/Indigo -> offset.", BODY)]
    s += [P("<b>V900 vs Indigo.</b> V900 = produttivita pura (toner secco), Indigo = "
            "qualita fotografica e gamma colore estesa. La scelta arriva dal "
            "preventivo prestampa.", BODY)]
    s += [P("<b>Fustella piana (BOBST) vs cilindrica (STEL).</b> BOBST per tirature "
            "alte e formati medi; STEL per tirature basse o quando BOBST e "
            "saturata.", BODY)]
    s += [P("<b>Foil JOH vs MGI.</b> JOH e meccanico (cliche metallico): tirature "
            "alte, costo cliche. MGI e digitale: setup nullo, ma capacita oraria "
            "inferiore. Sotto soglia ~5.000 pz -> MGI. Sopra -> JOH.", BODY)]
    s += [P("<b>finitura digitale vs allestimento.</b> Sono <u>reparti distinti</u>. "
            "Finitura digitale = ZUND/MGI (lavorazioni post-stampa digitali). "
            "Allestimento = lavorazioni manuali, accoppiatura, montaggio. "
            "Errore frequente: confonderli — il MES li separa.", BODY)]

    s += [P("Capacita aggregata e colli di bottiglia", H2)]
    s += UL([
        "<b>Cap settimanale stimata (ore macchina utili):</b> XL106 ~120h, "
        "V900+Indigo ~160h, JOH ~80h (160h con 3 turni), BOBST ~80h, "
        "Piegaincolla ~80h x linee.",
        "<b>Saturazione tipica:</b> JOH > 90%, BOBST 70-85%, Piegaincolla 60-75%, "
        "stampa < 70%.",
        "<b>Quando agire:</b> se JOH > 95% per 2 settimane, attivare 3 turno o "
        "spostare nobilitazioni su MGI.",
    ])

    return build_pdf("03_Macchine_e_Capacita.pdf", "03 - Macchine e capacita", s)


# ============================================================
# PDF 4 — Stati e transizioni
# ============================================================
def doc4():
    s = []
    s += [P("04 — Stati delle fasi e transizioni", H1)]
    s += [P("State machine ufficiale delle fasi di lavorazione, casistiche di "
            "pausa/ripresa e logica di auto-terminazione Prinect/Fiery.", META)]
    s += [Spacer(1, 6)]

    s += [P("Stati", H2)]
    st = [
        ["Stato", "Significato", "Visibilita operatore"],
        ["0", "Non iniziata — la fase precedente non e ancora terminata.", "Nascosta"],
        ["1", "Pronta — dipendenze soddisfatte, attende avvio.", "Visibile, avviabile"],
        ["2", "Avviata — almeno un operatore ha timbrato inizio.", "Visibile, in corso"],
        ["3", "Terminata — tutti i pezzi prodotti, in attesa fase successiva.", "Read-only"],
        ["4", "Consegnata — passata al reparto successivo o spedita.", "Read-only"],
        ["5", "Esterna (EXT) — affidata a fornitore terzo.", "Nascosta dashboard"],
        ["pausa:&lt;motivo&gt;", "Stringa: fase in pausa con motivo (manutenzione, attesa carta, ecc).", "Visibile, pausa"],
    ]
    s += [make_table(st, col_widths=[3.2*cm, 8.6*cm, 4.6*cm])]

    s += [P("Matrice transizioni", H2)]
    m = [
        ["Da \\ A", "0", "1", "2", "3", "4", "5", "pausa:*"],
        ["0", "—", "auto (dep. ok)", "—", "—", "—", "manuale", "—"],
        ["1", "rollback", "—", "avvio op.", "—", "—", "manuale", "manuale"],
        ["2", "—", "fine pausa", "—", "fine op. / Prinect / Fiery", "—", "manuale", "pausa op."],
        ["3", "—", "—", "ripristino (auto)", "—", "consegna", "—", "—"],
        ["4", "—", "—", "—", "rollback", "—", "—", "—"],
        ["5", "—", "rientro EXT", "—", "rientro EXT", "—", "—", "—"],
        ["pausa:*", "—", "—", "ripresa", "—", "—", "—", "—"],
    ]
    s += [make_table(m, col_widths=[2.0*cm, 1.6*cm, 2.0*cm, 1.6*cm, 2.6*cm, 1.6*cm, 1.6*cm, 1.7*cm])]

    s += [P("Casistiche operative", H2)]
    s += [P("<b>Pausa con motivo.</b> Quando l'operatore mette in pausa, lo stato "
            "diventa una <i>stringa</i> che inizia con <font face='Courier'>pausa:</font>. "
            "Le query devono usare <font face='Courier'>whereRaw</font> con regex per "
            "non filtrarle come numeri (bug storico: la spedizione bloccava commesse "
            "con fasi in pausa, fixato 20/03).", BODY)]
    s += [P("<b>Ripresa.</b> Tornano a stato 2; le pause vengono accumulate in "
            "<font face='Courier'>operatore_fase.secondi_pausa</font> per il calcolo "
            "ore nette.", BODY)]
    s += [P("<b>Rientro EXT (5 -> 3 o 4).</b> Manuale: l'owner ricolloca la fase "
            "quando il fornitore consegna. Lo storico del passaggio resta come "
            "evento.", BODY)]
    s += [P("<b>Priorita manuale.</b> Flag <font face='Courier'>priorita_manuale</font> "
            "su <font face='Courier'>ordine_fasi</font>: bypassa il ranking "
            "automatico nella dashboard operatore (utile per urgenze cliente).", BODY)]
    s += [P("<b>Ricalcolo cascata.</b> "
            "<font face='Courier'>ricalcolaCommessa()</font> rivaluta tutti gli "
            "ordini della stessa commessa via "
            "<font face='Courier'>config/fasi_priorita.php</font> dopo cambio "
            "stato.", BODY)]

    s += [PageBreak()]
    s += [P("Auto-terminazione Prinect (XL106)", H2)]
    s += UL([
        "Polling ogni minuto su workstep XL106.",
        "<b>Protezione fase attiva:</b> se la fase e iniziata da <i>meno di 1h</i>, "
        "non viene terminata anche se Prinect dice COMPLETED (rumore API).",
        "<b>Auto-termina:</b> fase in stato 2 abbandonata da > 4h <u>e</u> giorno "
        "diverso dall'inizio -> stato 3 con tempo Prinect.",
        "<b>Ripristino:</b> se attivita recenti su un workstep gia chiuso, "
        "ritorna a stato 2 (operatore ha riaperto).",
        "<b>COMPLETED senza actualStartDate:</b> fix 17/03: ora terminano (prima "
        "restavano in 2 a vita).",
        "<b>Snapshot accounting:</b> tempo_avviamento_sec + tempo_esecuzione_sec "
        "salvati su ordine_fasi (sorgente di verita per Report Ore).",
    ])

    s += [P("Auto-terminazione Fiery (V900 / Indigo)", H2)]
    s += UL([
        "Polling ogni minuto v5 jobs (solo ultimo run) + accounting storico.",
        "<b>Non termina mai job in stampa</b> (status \"Printing\").",
        "<b>Ripristino 3 -> 2</b> se accounting mostra fogli aggiunti dopo "
        "chiusura (riapertura da console).",
        "<b>Snapshot:</b> fogli totali storici via Accounting API per non perdere "
        "dato fra polling.",
    ])

    s += [P("Errori frequenti da evitare", H2)]
    s += UL([
        "Filtrare <font face='Courier'>stato</font> con <font face='Courier'>"
        "WHERE stato &gt; 2</font> dimenticando le pause stringa.",
        "Considerare 'finitura digitale' = 'digitale' nelle query reparto: "
        "<u>sono distinti</u>.",
        "Modificare priorita ignorando il flag <font face='Courier'>priorita_manuale</font>.",
        "Forzare 5 -> 0: il rientro EXT deve sempre passare da 3 o 4.",
    ])

    return build_pdf("04_Stati_e_Transizioni.pdf", "04 - Stati e transizioni", s)


# ============================================================
# PDF 5 — Glossario
# ============================================================
def doc5():
    s = []
    s += [P("05 — Glossario e convenzioni", H1)]
    s += [P("Vocabolario tipografico, codifiche interne e regole di naming "
            "che ricorrono nelle conversazioni operative e nel codice.", META)]
    s += [Spacer(1, 6)]

    s += [P("Glossario tecnico tipografico", H2)]
    g = [
        ["Termine", "Significato"],
        ["FS####", "Codice fustella interna (es. FS1234). Identifica univocamente "
                  "il cliche di fustella conservato in archivio."],
        ["4C", "Quadricromia CMYK (4 colori)."],
        ["4+1", "Quadricromia + un Pantone (5 colori totali sulla XL106)."],
        ["6+L", "6 colori + verniciatura (XL106)."],
        ["Drip off", "Effetto opaco-lucido per contrasto, ottenuto con vernice + "
                    "vernice UV in registro."],
        ["Plastica opaca / lucida / soft touch / lux",
         "Tipi di plastificazione. Soft touch = effetto vellutato; lux = "
         "alta brillantezza con nobilitazione successiva."],
        ["Foil", "Pellicola metallizzata trasferita a caldo (JOH) o a freddo (MGI)."],
        ["Cliche", "Matrice metallica usata per stampa a caldo o per fustella."],
        ["Bordo carta", "Banda non stampabile attorno al foglio dovuta a pinze "
                       "macchina e pesce."],
        ["GC1 / GC2", "Cartoncini patinati: GC1 retro bianco (premium), GC2 retro "
                      "grigio (standard)."],
        ["KS / Bobst", "KS = piattaforma macchina; Bobst = brand storico per "
                       "fustellatrici e piegaincolla."],
        ["IML", "In-Mould Label, etichetta inserita nello stampo plastica."],
        ["Brossura", "Legatura con colla a caldo (no cucitura)."],
        ["Cordoni / cordonatura", "Linee di piega impresse a freddo per facilitare "
                                  "la chiusura dell'astuccio."],
    ]
    s += [make_table(g, col_widths=[3.6*cm, 12.8*cm])]

    s += [PageBreak()]
    s += [P("Convenzione codice articolo Onda", H2)]
    s += [P("Formato: <font face='Courier'><b>02W.MARCA.TIPO.GRAMMATURA.SEQ</b></font>", BODY)]
    es = [
        ["Token", "Significato", "Esempio"],
        ["02W", "Famiglia carta (W = bianca patinata).", "02W"],
        ["MARCA", "Codice produttore.", "FBB, BURGO, SAPPI"],
        ["TIPO", "Sottocategoria carta.", "INVERC, IGGE"],
        ["GRAMMATURA", "g/m^2.", "300, 350"],
        ["SEQ", "Progressivo formato/varianti.", "01, 02"],
    ]
    s += [make_table(es, col_widths=[3.0*cm, 8.0*cm, 5.4*cm])]
    s += [P("Esempio: <font face='Courier'>02W.FBB.INVERC.300.01</font> = patinata "
            "Fedrigoni Invercote 300 g/m^2, formato standard.", BODY)]

    s += [P("Etichette DataMatrix", H2)]
    s += [P("Etichette spedizione: <b>plain DataMatrix</b> (NON GS1). Il GTIN inizia con "
            "<font face='Courier'>A</font>; la quantita e codificata su <b>8 cifre "
            "zero-padded</b>. Nessun separatore FNC1, nessuna AI.", BODY)]
    s += [code_block("Esempio payload:  A0000123456780000040  (GTIN A...678  qty=00000040)")]

    s += [P("Reparti — naming preciso", H2)]
    s += [P("<b>'digitale' != 'finitura digitale'.</b> Sono due reparti distinti, "
            "errore comune nelle query e nelle dashboard:", BODY)]
    s += UL([
        "<b>digitale</b> = stampa digitale (V900, Indigo) — fase 11.",
        "<b>finitura digitale</b> = post-stampa digitale (ZUND, MGI) — fase 35.",
    ])
    s += [P("Altri reparti: <i>prestampa, stampa offset, allestimento, "
            "fustella piana, legatoria, spedizione, magazzino</i>.", BODY)]

    s += [P("Stati pivot operatore vs stato fase", H2)]
    s += [P("Lo <b>stato della fase</b> (<font face='Courier'>ordine_fasi.stato</font>) "
            "e diverso dallo <b>stato del singolo turno operatore</b> "
            "(<font face='Courier'>operatore_fase</font>): la fase puo essere in "
            "stato 2 anche se nessun operatore sta timbrando in quel momento "
            "(es. pausa pranzo macchina ferma). I due stati vanno aggregati "
            "correttamente nei report ore.", BODY)]

    s += [P("Convenzioni di lingua e UI", H2)]
    s += UL([
        "<b>Lingua dominio:</b> italiano. I codici PHP/SQL restano in inglese; "
        "le label utente sono in italiano.",
        "<b>Codice operatore:</b> <u>alfanumerico</u> (no <font face='Courier'>"
        "type=number</font>, no <font face='Courier'>inputmode=numeric</font>).",
        "<b>Tabelle owner:</b> descrizione max-width 220px con ellipsis per "
        "leggibilita.",
        "<b>Note unificate:</b> 'Informazioni generali / per fasi successive' "
        "con autore — visibili a operatore e owner.",
    ])

    s += [P("Acronimi", H2)]
    ac = [
        ["Acronimo", "Estensione"],
        ["MES", "Manufacturing Execution System"],
        ["ERP", "Enterprise Resource Planning (Onda)"],
        ["DDT", "Documento Di Trasporto"],
        ["BRT", "Bartolini (corriere)"],
        ["JDF/JMF", "Job Definition / Messaging Format (Prinect)"],
        ["FSM", "Finite State Machine"],
        ["DDD", "Domain-Driven Design"],
        ["EXT", "Lavorazione esterna (stato 5)"],
    ]
    s += [make_table(ac, col_widths=[3.0*cm, 13.4*cm])]

    return build_pdf("05_Glossario_e_Convenzioni.pdf",
                     "05 - Glossario e convenzioni", s)


def main():
    paths = []
    for fn in (doc1, doc2, doc3, doc4, doc5):
        paths.append(fn())
    print("\n".join(paths))


if __name__ == "__main__":
    main()
