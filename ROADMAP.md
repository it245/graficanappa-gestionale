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

## TODO — Integrazione Bobst Visionfold 110 (PRIORITÀ ALTA, in attesa)

In attesa risposta Davide Alberti (Bobst Italia, support@mybobst.com).

- [ ] Email Davide inviata 28/04/2026 — IP eWOD + credenziali DHL/SQL Web Open Data
- [ ] Quando arriva IP: scrivi `app/Http/Services/BobstService.php`
  - [ ] `getProductionStatus()` via DHL → JobUnitCount, MachineState, LinearSpeed, JobName
  - [ ] `getJobsHistory()` via SQL → query Operations, Events, Jobs
  - [ ] `syncOperations()` → match OpRef Bobst con commessa MES, aggiorna qta_prod
- [ ] Test connessione: `curl http://IP_eWOD:85/dhlweb` e `:85/sqlweb`
- [ ] Push commessa MES → HMI Bobst via MRP Web (`cmd=push&OpRef=...`)
- [ ] Schedule warm cache Bobst (analogo a Fiery warm)
- [ ] Verifica machine class (MC4? altro?) e tabelle disponibili

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
