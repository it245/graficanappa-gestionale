# MES Grafica Nappa — Roadmap

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

## DONE recenti (28/04/2026)

- ✅ Fiery resilience: retry + stale cache 30 min + warm command
- ✅ Task scheduler `FieryWarmCache` ogni 60s su .60
- ✅ Polling dashboard Fiery 15s → 30s
- ✅ Rimosso `/jobs/printing` ridondante + `/accounting` da dashboard render
- ✅ Counter rinominato "Conteggio" → "Qta Prodotta" su dettaglio commessa
- ✅ Branch `feature/multi-tenant` cancellato (MES non più SaaS commerciale)
- ✅ File pitch SaaS rimossi (`docs/demo.pdf`, `MES_GraficaNappa_Overview.*`, `make_memo_pdf.py`)
