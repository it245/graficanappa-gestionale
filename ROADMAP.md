# MES Grafica Nappa — Roadmap

**Piano biennale AI-Assisted: Febbraio 2026 — Febbraio 2028**
Sviluppo: Giovanni Pietropaolo (Tirocinante) con supporto Claude Code (Anthropic AI).

## Timeline biennale (v1.0 → v10.0)

| Versione | Periodo | Stato | Descrizione |
|----------|---------|-------|-------------|
| v1.0 MES Completo | Q1 2026 (Gen-Feb) | ✅ Done | Fondamenta, produzione, costi, integrazioni Onda/Prinect/Fiery |
| v1.1 Miglioramenti | Q1 2026 (Mar) | ✅ Done | Priorità manuali, lavorazioni esterne, report ore, presenze NetTime |
| v2.0 Scheduler + Magazzino + OCR | Q1-Q2 2026 | 🔵 In corso | Scheduler ottimizzato, magazzino carta, OCR documenti |
| v2.5 Fabbisogno + Ordini Acquisto | Q2 2026 | 🟠 Next | Pianificazione fabbisogno carta, generazione ordini fornitori |
| v3.0 CRM + BI + AI Intelligence | Q3 2026 | 🟣 AI | Anagrafica clienti CRM, dashboard BI, AI per insight |
| v4.0 Pianificazione AI | Q4 2026 | 🟣 AI | Mossa37 production scheduling con API Claude |
| v5.0 Mobile App | Q1 2027 | ⚪ Future | App nativa operatori (iOS/Android) |
| v6.0 Multi-Site | Q2 2027 | ⚪ Future | Supporto multi-stabilimento |
| v7.0 IoT & Digital Twin | Q3 2027 | ⚪ Future | Sensori IoT macchine, digital twin produzione |
| v8.0 BI & Analytics Avanzata | Q4 2027 | ⚪ Future | Predittiva, ML, ottimizzazione costi |
| v9.0 Portale Clienti & API | Q4 2027 | ⚪ Future | Self-service clienti, API pubbliche |
| v10.0 ATT Vendite + SDI Fatturazione Elettronica | Q2 2028 | ⚪ Future | Generazione fattura elettronica XML, invio Sistema di Interscambio Agenzia Entrate |
| v11.0 PAS Acquisti + Ordini Fornitori | Q3 2028 | ⚪ Future | Documenti passivi gestiti in MES, ricezione fatture elettroniche |
| v12.0 OC Cartotecnica completa | Q4 2028 | ⚪ Future | Segnature, agglomerati, attrezzature, lavorazioni industria-specific tipografia |
| v13.0 IVA + Intrastat + Bollati + CONAI | Q1 2029 | ⚪ Future | Liquidazione IVA, registri, intrastat scambi UE, CONAI imballaggi |
| v14.0 Cespiti + Bilancio XBRL CEE | Q2 2029 | ⚪ Future | Ammortamenti, bilancio Camera di Commercio (XBRL automatico) |
| v15.0 Dismissione Onda + Migrazione storico | Q3 2029 | ⚪ Future | MES sostituisce Onda; periodo dual-write; cutover finale |
| **v16.0 AI Co-Pilot integrato** | **Q4 2029-2030** | 🟣 AI | Claude integrato in tutti i processi MES (assistente operatori, anomaly detection, suggerimenti dashboard) |
| **v17.0 AI Agents autonomi** | **2030-2031** | 🟣 AI | Sourcing fornitori, customer service first-line, scheduling auto |
| **v18.0 Predictive Maintenance + ML qualità stampa** | **2030-2031** | 🟣 AI | ML per Heidelberg XL, Bobst, Canon — previsione guasti |
| **v19.0 AI Vision** | **2031-2032** | 🟣 AI | Controllo qualità stampa via camera, OCR fatture passive, riconoscimento difetti |
| **v20.0 Autonomous Operations** | **2032+** | 🟣 AI | Sistema gestionale auto-ottimizzante, AI agents collaborativi, intervento umano solo per eccezioni |
| **Post-2032 — Manutenzione + evoluzione** | **2032+** | 🔄 Permanente | Manutenzione attiva, nuove integrazioni, compliance, supporto IT, formazione |

## L'AI nei processi (visione 2027-2032)

L'AI assistita non sarà solo strumento di sviluppo (come oggi con Claude Code), ma diverrà **parte integrante dei processi aziendali**:

- **2026 (v3.0)**: AI per CRM — suggerisce upsell, predice churn, scrive email commerciali draft
- **2026 (v4.0)**: AI scheduler Mossa37 — ottimizzazione produzione con LLM (Claude API)
- **2027 (v8.0)**: AI forecasting — previsione domanda, ottimizzazione magazzino carta
- **2029-2030 (v16.0)**: AI Co-Pilot integrato in tutti i processi MES
- **2030-2031 (v17.0)**: AI Agents autonomi per task ripetitivi
- **2030-2031 (v18.0)**: ML predictive maintenance macchine
- **2031-2032 (v19.0)**: AI Vision — controllo qualità stampa, OCR fatture
- **2032+ (v20.0)**: Autonomous Operations — sistema auto-ottimizzante

**Modello costo AI**: API LLM (Claude/GPT) + modelli ML self-hosted.
- Stima 2027: $200-500/mese
- Stima 2030: $1.000-3.000/mese (volume crescente)
- ROI: ore IT risparmiate + decisioni migliori + zero downtime macchine

📄 **Roadmap completa con dettaglio versioni**: `ROADMAP.html` (timeline Gantt + checklist v1.0-v10.0)

**Nota**: il piano biennale 2026-2028 copre lo sviluppo intensivo delle feature core. Da 2028+ il MES entra in **manutenzione attiva** (bug fix, security continua, integrazione nuove macchine officina, compliance, evoluzione tecnologica). Il ruolo di Responsabile IT Grafica Nappa è **permanente**, non legato al piano sviluppo.

---

## TODO — UI Overhaul Enterprise SaaS (PRIORITÀ MEDIA)

Stile target: **Linear / Notion / Vercel** — sfondo bianco/grigio chiaro, accent blu (#2563eb), font Inter, shadow leggere, spaziature generose, micro-interazioni discrete, tabelle dense ma leggibili.

**Stato**: rimandato — solo CSS + componenti Blade wrapper, **zero modifiche logica/controller/DB/JS handler**.

### Phase 1 — Foundation (1-2 giorni)
- [ ] Estrai token CSS in `public/css/mes-tokens.css` (cacheable)
- [ ] Aggiungi spacing scale (`--space-1: 4px` → `--space-12: 96px`)
- [ ] Aggiungi shadow scale (`--shadow-sm/md/lg/xl`)
- [ ] Aggiungi radius scale (`--radius-sm/md/lg/full`)
- [ ] Typography scale (`--text-xs: 11px` → `--text-3xl: 28px`)
- [ ] Carica Inter globalmente in `app.blade.php` (uniforma con `mes.blade.php`)
- [ ] Componente `<x-mes.button>` (props: variant, size, loading, disabled, icon, href)
- [ ] Componente `<x-mes.modal>` (props: id, title, size, backdrop-close, footer slot)
- [ ] Componente `<x-mes.card>` (props: title, subtitle, padding, hover, footer slot)
- [ ] Componente `<x-mes.input>` (props: type, name, value, label, error, help, prefix/suffix)
- [ ] Componente `<x-mes.table>` (props: dense, striped, hoverable, sticky-header)
- [ ] Rimuovi token collision in `fiery/dashboard.blade.php` (lines 6-16)

### Phase 2 — Pagine top (3-5 giorni)
- [ ] **Owner dashboard**: 121 modal Bootstrap → `<x-mes.modal>`, tabella dense, KPI bar, filtri sidebar
- [ ] **Operatore dashboard**: tab → segmented control, lazy-load QR, card commessa con tag pills
- [ ] **Spedizione dashboard**: card sezioni uniformi, tabelle componente

### Phase 3 — Pagine secondarie (2-3 giorni)
- [ ] Admin dashboard, cruscotto, lista_commesse
- [ ] Magazzino dashboard + giacenze/articoli/movimenti
- [ ] Owner: reparti_overview, esterne, fustelle, report_ore, fasi_terminate
- [ ] Prestampa pages
- [ ] Fiery contatori
- [ ] Auth login (split-screen moderno) + 2FA + errors

### Phase 4 — Polish + responsive (1-2 giorni)
- [ ] Breakpoints SCSS (320, 640, 1024, 1280)
- [ ] Sidebar collapse <1024px propagata a tutti i layout
- [ ] Tabelle scroll-x mobile con sticky-col commessa
- [ ] Touch target ≥44px mobile
- [ ] Skeleton loaders tabelle in caricamento
- [ ] Toast notifications `<x-mes.toast>`
- [ ] Hover states uniformi
- [ ] Focus rings accessibility (outline 2px accent, offset 2px)
- [ ] Dark mode completo (sync tra layout, contrast AA)
- [ ] Print stylesheet `public/css/print.css`
- [ ] PWA polish (splash, theme color, app icons 192/512)

**Stima totale: 7-12 giorni FTE**

---

## TODO — Integrazione Bobst Visionfold 110 (BLOCCATA — software non installato)

**Risposta Davide Alberti (Bobst Italia, 29/04/2026)**:
> "Le opzioni DATALINK e ERP INTERFACE non risultano essere state implementate in fase di acquisto e non ho trovato nessun ordine/quotazione dal 2015 ad oggi. La vostra Visionfold risulta non essere raggiungibile dal nostro servizio di assistenza remota Helpline+."

**Conseguenze**:
- ❌ DHL Web Open Data non attivo
- ❌ SQL Web Open Data non attivo
- ❌ MRP Web push non attivo
- ❌ JTI / Quality Mode / Manual Event non attivi
- ❌ Helpline+ assistenza remota non funzionante (probabile mancato collegamento rete o licenza scaduta)

**Strade alternative**:

### Opzione A — Counter pulse 24V + ESP32 (€20, fai-da-te)
- [ ] Aprire armadio elettrico Visionfold, foto morsettiera
- [ ] Schema elettrico `BSA0356C204 D81` → cercare uscita "PRODUCTION COUNT"
- [ ] Hardware: ESP32 + PC817 optoisolatore + custodia DIN
- [ ] Firmware: conta impulsi + POST `/api/contatore/piegaincolla`
- [ ] Solo conteggio pezzi, no job/velocità/target

### Opzione B — HDMI splitter + Raspberry Pi + OCR (€110)
- [ ] HDMI splitter 1→2 (uno al monitor originale, uno al Pi)
- [ ] HDMI capture USB
- [ ] Raspberry Pi 4 + Tesseract OCR
- [ ] Cattura screenshot ogni 5s, estrae 2937 (contatore), 10200 (target), 69 (m/min)
- [ ] POST a Laravel API
- [ ] Tutti i dati ricchi senza modificare macchina

### Opzione C — Pagare retrofit Bobst (€5.000-15.000 stimati)
- [ ] Richiedere quotazione a commerciale Bobst (chiedere a Davide)
- [ ] Attivazione DATALINK + ERP INTERFACE + Helpline+
- [ ] Integrazione nativa via DHL/SQL/MRP Web
- [ ] Decisione capo Antonio sul costo

**Decisione**: scegliere strada in base a budget + urgenza. Per ora bloccato.

---

## OBIETTIVO STRATEGICO — Sostituzione Onda ERP (LUNGO TERMINE)

**Visione**: il MES Grafica Nappa diventerà l'**unico sistema di gestione commesse**, sostituendo Onda ERP (SQL Server su .253).

**Perché**:
- Indipendenza da Onda (no più costi licenze, no più vincoli SOAP/SQL Server esterno)
- Controllo totale flusso produzione (oggi Onda è sorgente, MES sync; obiettivo: MES sorgente, Onda dismessa)
- Zero dipendenze esterne per il futuro
- MES diventa product completo per Grafica Nappa (anche se non più SaaS commerciale, può essere referenza interna)

**Stato attuale (2026)**:
- ✅ Sync Onda → MES via SOAP (ordini, fasi, costi, prezzi vendita) ogni ora
- ✅ Lettura SQL diretta Onda per import singola commessa, costi materiali (PRDDocRighe), DDT vendita
- ✅ MES ha tutti i dati operativi (ordini, fasi, presenze, costi, scarti, fogli stampati)
- 🟡 Manca creazione/modifica ordini direttamente in MES (oggi solo lettura da Onda)
- 🟡 Manca DDT vendita/acquisto generati da MES
- 🟡 Manca fatturazione (oggi su Onda)
- 🟡 Manca contabilità (oggi su Onda)

**Roadmap sostituzione Onda**:

### Fase 1 — Lettura completa (in corso)
- [x] Sync ordini, fasi, costi materiali, DDT vendita
- [ ] Sync clienti completo (anagrafica + storico) → CRM v3.0
- [ ] Sync fornitori (per ordini acquisto)
- [ ] Sync articoli/listini

### Fase 2 — Scrittura MES → Onda (bridge bidirezionale, 2026-2027)
- [ ] Creare ordine in MES → push su Onda via API (se Onda permette)
- [ ] Modificare fase MES → propaga su Onda
- [ ] DDT vendita generato in MES → aggiorna Onda

### Fase 3 — MES diventa sorgente (2027-2028)
- [ ] Creare ordine direttamente in MES (no più passaggio Onda)
- [ ] Anagrafica clienti gestita da MES
- [ ] Listini, prezzi, sconti gestiti da MES
- [ ] Generazione DDT vendita/acquisto da MES
- [ ] Stampa documenti fiscali (con engine PDF tipo `BollaLavorazioneService`)
- [ ] Fatturazione elettronica integrata (SDI)

### Fase 4 — Dismissione Onda (2028+)
- [ ] Migrazione dati storici Onda → MES (commesse archive, fatture, contabilità)
- [ ] Periodo parallelo dual-write (sicurezza)
- [ ] Cutover finale: spegnimento Onda
- [ ] Backup archive Onda freezato
- [ ] Risparmio costi licenze Onda → ROI MES

**Feature già pronte in MES per il post-Onda**:
- ✅ `BollaLavorazioneService::streamCommessa` — Scheda Produzione PDF completa multi-pagina
- ✅ `BollaLavorazioneService::stream` — Bolla per fase PDF
- (Oggi non usate perché Onda stampa la cartacea, ma pronte quando MES diventerà sorgente)

**Note operative**:
- Decisione 22/04/2026: non polire scheda produzione PDF per demo cliente (non valore differenziante per prospect). Focus su scheduler Mossa 37 + feature core.
- Tempistica realistica: dismissione Onda **non prima del 2028**. Servono fatturazione elettronica + integrazione SDI + migrazione anagrafiche.

---

## TODO — Mossa 37 (PRIORITÀ MEDIA, branch mossa37)

Step roadmap originale:
- [x] Step 1-4: migration, PriorityService, evento PhaseCompleted, propagazione
- [ ] **Step 5**: OptimizeScheduleJob + integrazione API Claude
- [ ] **Step 6**: dashboard fallback se API down
- [ ] **Step 7**: cron job schedulazione automatica
- [ ] **Step 8**: test end-to-end con dati produzione reali
- [ ] Decisione capo: deploy su master o restare branch separato

---

## TODO — Manutenzione e fix (PRIORITÀ VARIA)

### Sicurezza / privacy
- [ ] Stampare e firmare DOC-SEC-MES-001 (`http://gestionale/security_protocols.html`)
- [ ] Stampare e firmare DOC-GDPR-MES-001 (`http://gestionale/gdpr_registro_trattamenti.html`)
- [ ] Risolvere security audit findings (1 critico, 3 alti, 4 medi, 3 bassi — vedi `reference_security_audit.md`)

### Bug fix
- [ ] Fix CSRF "Page Expired" telefono/VPN: `.env` server → `SESSION_DOMAIN=` (vuoto)
- [ ] Configurare `.env` per tunnel tnnl.in: `APP_URL`, `SESSION_DOMAIN=.p.tnnl.in`, `SESSION_SECURE_COOKIE=true`
- [ ] Bug audit findings (3 critici, 6 alti, 8 medi, 2 bassi — vedi `reference_bug_audit_marzo2026.md`)

### Pulizie
- [ ] Seeder FasiCatalogo: rimuovere PIEGA2ANTECORDONE da legatoria (resta finitura digitale)
- [ ] Aggiungere `NETTIME_SHARE_USER`/`PASS` al `.env` server
- [ ] Performance audit findings (5 quick wins, 5 fix medi, indici SQL — vedi `reference_perf_audit.md`)

### MCP / Integrazioni
- [ ] Finire config `claude_desktop_config.json` Mac capo (Onda MCP)
  - Path: `/Library/Frameworks/Python.framework/Versions/3.14/bin/mssql_mcp_server`
  - Env: `MSSQL_SERVER`, `MSSQL_USER`, `MSSQL_PASSWORD`, `MSSQL_DATABASE`
  - ⌘Q Claude + riapri, test query Onda
- [ ] Solar-Log: script PHP/PowerShell login + scrape pagina rendimenti `https://solarlog-portal.it/emulated_yieldov_3650.html`
- [ ] Fiery EFI: chiamare service per firmware update (causa hang intermittente)

---

## TODO — HTTPS Apache (RIMANDATO)

**Status**: cert + vhost funzionanti su .60, ma browser non si fidano (cert in user store invece di machine store via certutil). Rollback fatto a HTTP.

**File pronti per riprovare**:
- `C:\Apache24\conf\gestionale.crt` (cert con SAN)
- `C:\Apache24\conf\gestionale.key`
- `C:\Apache24\conf\httpd-ssl-mes.conf` (vhost SSL pronto)
- `genera_cert.bat` (rigenera cert con SAN)

**Per riprovare in futuro**:
1. Riabilita `Include conf/httpd-ssl-mes.conf` in `httpd.conf`
2. Restart Apache
3. Importa cert in **Machine store** via mmc.exe (NON certutil):
   - `mmc.exe`
   - Aggiungi snap-in "Certificati" → "Account computer" → "Computer locale"
   - Espandi "Trusted Root Certification Authorities" → "Certificati"
   - Right-click → Tutte le attività → Importa
   - Seleziona `gestionale.crt`
   - Restart browser
4. Per altri PC: copia cert + ripeti import via mmc.exe in Machine store
5. Quando OK: `.env` produzione `APP_URL=https://gestionale` + `SESSION_SECURE_COOKIE=true` + `FORCE_HTTPS=true`

**Alternative migliori**:
- Cloudflare Tunnel (HTTPS automatico)
- mkcert tool (più affidabile di openssl + certutil)
- Win-acme + reverse proxy interno

---

## DONE recenti (28/04/2026)

- ✅ Fiery resilience: retry + stale cache 30 min + warm command
- ✅ Task scheduler `FieryWarmCache` ogni 60s su .60
- ✅ Polling dashboard Fiery 15s → 30s
- ✅ Rimosso `/jobs/printing` ridondante + `/accounting` da dashboard render
- ✅ Counter rinominato "Conteggio" → "Qta Prodotta" su dettaglio commessa
- ✅ Branch `feature/multi-tenant` cancellato (MES non più SaaS commerciale)
- ✅ File pitch SaaS rimossi (`docs/demo.pdf`, `MES_GraficaNappa_Overview.*`, `make_memo_pdf.py`)
