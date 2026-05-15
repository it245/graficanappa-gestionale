# ROADMAP: MES Grafica Nappa --> ERP + MES Integrato
## Trasformazione Completa Sistema Gestionale (2026-2029)

Data Creazione: 29 Aprile 2026
Versione: 1.0
Status: Proposta Strategica per Board Approval

## EXECUTIVE SUMMARY

### Situazione Attuale
Grafica Nappa opera con due sistemi critici disintegrati:
- Onda ERP (legacy SQL Server, 20+ anni): 328M+ righe su 798 tabelle
- MES Laravel (nuovo, partial): Pianificazione + esecuzione, read-only da Onda

### Lacune Critiche
- Zero controllo automatico giacenze real-time
- Telematici (SDI, IVA, Intrastat) offline/manuali
- Cartotecnica dispersa tra sistemi
- Zero export XBRL/bilancio
- Contabilità disconnessa da production

### Visione Target (2029)
ERP + MES integrato con:
- MES primario; Onda legacy read-only
- CDC bidirezionale real-time
- Compliance SDI, IVA, Intrastat, XBRL nativa
- Dashboard C-level: margini, cashflow real-time

### Investimento Totale
- Personale IT: EUR 480K-600K (36-40 mesi, 2.5 FTE)
- Partner/SaaS: EUR 150K-200K
- Infra/Tools: EUR 80K-120K
- TOTALE: EUR 710K-920K

ROI: -18 mesi (automazione manuale + giacenze ottimizzate + visibility)

## 1. MAPPA ONDA: 798 TABELLE, 328M+ RIGHE

Aree funzionali critiche:
- ANA (Analitica): 57 tabelle, 45K righe, 60% MES coverage
- STD (Master Data): 95 tabelle, 45K righe, 95% MES coverage
- MAG (Giacenze): 130 tabelle, 2M righe, 40% MES coverage
- PRD (Produzione): 85 tabelle, 1.1M righe, 75% MES coverage
- ATT (Vendite): 38 tabelle, 2M righe, 50% MES coverage
- PAS (Acquisti): 25 tabelle, 450K righe, 0% MES coverage
- COG (Contabilità): 56 tabelle, 750K righe, 0% MES coverage
- OC (Cartotecnica): 152 tabelle, 3.5M righe, 20% MES coverage
- TEL (Telematici): 6 tabelle, 340 righe, 0% MES coverage

Current Coverage: 45% integrated, 55% legacy/manual

## 2. COMPLIANCE ITALIA: OBBLIGHI LEGALI

Telematici mancanti:
- SDI Fatturazione: Obbligo 01/01/2019, stato manual, azione auto XML+API fase 6, costo 5K
- IVA Liquidazione: Obbligo trimestrale, stato Excel, azione auto-calc+export fase 8, costo 2K
- Intrastat: Obbligo se export UE, stato manual, azione auto EDIFACT fase 8, costo 5K
- XBRL Bilancio: Obbligo annuale, stato Excel, azione auto XBRL CEE fase 10, costo 7K
- Cespiti: Obbligo bilancio, stato Excel, azione asset registry+depr fase 9, costo 5K
- CONAI: Obbligo imballaggi, stato manual, azione track+report fase 9, costo 3K
- Bollo: Obbligo documenti >77 euro, stato manual, azione auto-flag fase 7, costo 0.5K

Total Compliance Cost: EUR 27.5K dev + EUR 18K advisory

## 3. ROADMAP: 10 FASI SEQUENZIALI (36-40 MESI)

Timeline:
Q2 2026: FASE 1-2 (Setup infra + STD sync)
Q3 2026: FASE 3 (MAG real-time + PRD expand)
Q4 2026: FASE 4 (ANA complete + forecasting)
Q1 2027: FASE 5 (PRD bidirezionale + costi)
Q2 2027: FASE 6 (ATT + SDI)
Q3 2027: FASE 7 (COG prima nota + OC sync)
Q4 2027: FASE 8 (IVA + Intrastat + OC normalization)
Q1 2028: FASE 9 (Cespiti + CONAI + cartotecnica advanced)
Q2 2028: FASE 10 (XBRL + Bobst JTI + Onda sunset)
Q3-Q4 2028: Stabilizzazione

FASE 1 (6 weeks): Setup Infra + CDC
- SQL Server Cluster AAG
- Kafka event streaming
- CDC Onda --> Kafka (lag <5 sec)
- API Gateway + monitoring
- Budget: EUR 35K infra

FASE 2 (8 weeks): STD Master Data Sync
- STDArticoli, STDClienti, STDFornitori synced
- Nightly reconciliation
- Budget: EUR 25K dev

FASE 3 (10 weeks): MAG Real-time
- MAGGiacenze real-time sync
- Prenotazioni tracking
- 4-week forecast module
- Budget: EUR 45K dev + EUR 15K SaaS

FASE 4 (8 weeks): ANA + Forecasting
- ANA commesse bidirezionale
- Cost allocation automated
- C-level dashboard
- Budget: EUR 40K dev

FASE 5 (10 weeks): PRD Bidirezionale
- OdP create/update synced
- Material consumption auto
- WIP + scrap tracking
- Budget: EUR 60K dev + EUR 10K testing

FASE 6 (12 weeks): ATT + SDI
- Invoices auto-sent SDI
- Delivery confirmation captured
- Budget: EUR 50K dev + EUR 6K SaaS

FASE 7 (12 weeks): COG Prima Nota + OC Sync
- Journal entries auto from PRD/ATT
- OC_ read-only sync
- Cartotecnica job cards auto
- Budget: EUR 80K dev + EUR 3K advisory

FASE 8 (14 weeks): IVA + Intrastat + OC Normalization
- IVA liquidazione auto
- Intrastat export auto
- OC data normalize
- Budget: EUR 90K dev + EUR 5K advisory

FASE 9 (12 weeks): Cespiti + CONAI + Cartotecnica
- Asset registry + depreciation auto
- CONAI reporting auto
- Bobst JTI API ready
- Budget: EUR 70K dev + EUR 5K Bobst

FASE 10 (14 weeks): XBRL + Bobst JTI Live
- XBRL CEE bilancio auto
- Bobst JTI live deployment
- Onda deprecated (read-only)
- Budget: EUR 80K dev + EUR 13K Bobst

## 4. TECH STACK ADDITIONS

Backend:
- CDC (Kafka): EUR 20K setup, EUR 3K/anno
- DB Cluster (AAG): EUR 30K, EUR 8K/anno
- API Gateway (Kong): EUR 5K, EUR 2K/anno
- Monitoring: EUR 3K, EUR 1K/anno
- ELK Stack: EUR 8K, EUR 4K/anno
- Redis Cluster: EUR 5K, EUR 2K/anno

Frontend/BI:
- Dashboard (Grafana): EUR 25K, EUR 8K/anno
- Mobile App: EUR 20K, EUR 2K/anno

Partners/SaaS:
- SDI (FattureInCloud): EUR 3.5K, EUR 6K/anno
- Bobst JTI: EUR 10K, EUR 5K/anno
- Advisory: EUR 10K, EUR 2K/anno

TOTALE TECH: EUR 139.5K setup, EUR 43K/anno

## 5. RISK ANALYSIS & MITIGATION

Critical Risks (RED):
1. CDC lag >60 sec (40% prob) --> Test pre-prod, fallback batch, SLA <5s
2. Data inconsistency (30% prob) --> Nightly reconciliation, audit trail 100%
3. Compliance deadlines missed (25% prob) --> Regulatory advisor Q1, parallel run
4. Onda SQL Server HW failure (20% prob) --> Upgrade NOW, backup cluster, DR drills

High Risks (YELLOW):
5. User resistance (50% prob) --> Training, champions program, incentivize
6. Budget overrun (60% prob) --> Change control, EUR 150K contingency, gates
7. Bobst integration delay (35% prob) --> Lock contract Q1, backup solution
8. Key personnel turnover (25% prob) --> Document, cross-train, salary review

## 6. GOVERNANCE & ORGANIZATION

Steering Committee (Monthly):
- CFO (sponsor)
- COO (operations)
- IT Manager (technical lead)
- Controller (compliance)
- Plant Manager (production)

Core Team:
- Project Manager: 1 FTE (ASAP)
- Backend Engineers: 2 FTE
- DB Admin: 1 FTE
- DevOps: 0.5 FTE
- QA Engineer: 1 FTE
- Business Analyst: 0.5 FTE
- Compliance Advisor: 0.5 FTE external

Year 1 Payroll: EUR 170K (new hires + contractor)

## 7. IMMEDIATE ACTIONS (APRIL 2026)

1. Board Approval: EUR 800K budget + contingency
2. Governance: Constitute steering committee
3. Hiring: PM + Backend Engineer (start MAY)
4. Hardware Audit: Onda SQL Server capacity + failover
5. Partner Agreements: FattureInCloud + Bobst contracts
6. PoC: CDC test on Onda replica (40 hrs)
7. Training Plan: Identify champions per team
8. Compliance: Hire regulatory advisor

## CONCLUSIONE

GO DECISION: Proceed with Roadmap

Justification:
1. ROI positivo end-2028 (-18 mesi payback)
2. Compliance obbligatoria (SDI, IVA, Intrastat, XBRL)
3. Operational gains:
   - EUR 150K/anno manual automation
   - EUR 200K+ cash libero (giacenze -15%)
   - Pricing power + profitability visibility
   - Zero regulatory risk

Success Criteria (End-2028):
- 99.95% uptime, <5 sec sync lag
- 100% invoices auto-sent SDI
- IVA, Intrastat, XBRL automated
- Onda fully deprecated
- >95% user adoption
- Zero production incidents month 3+
- EUR 0.50 cost/transaction (vs. EUR 1.20 today)

Status: APPROVED FOR DEVELOPMENT
Generated: 29 April 2026

---

## ADDENDUM 10 — Repositioning Strategico: MES = Estensione, NON Sostituto (15/05/2026)

### Decisione
Il MES Grafica Nappa NON sostituirà Onda ERP. Sarà estensione/BI/operations
layer sopra ERP esistente. Posizionamento commerciale: anti-commodity.

### Razionale
- Competere come ERP completo = guerra al ribasso vs Zucchetti/Team System
  (€20-50/mese commodity)
- Posizionamento vincente: MES specializzato tipografia + BI operativo +
  scheduling produzione + analytics che ERP generalisti NON fanno
- Vendita SaaS futura: "MES tipografico universale" sopra qualunque ERP
  (Onda, Zucchetti, SAP), connector layer dedicato

### Implicazioni progetto
1. **Logica contabile/fiscale** → NON duplicare. Leggi da Onda via SOAP/SQL
2. **Produzione/operations** → owned dal MES (Onda non le ha)
3. **Dashboard finanziari** → presentare dati Onda in UI MES, no riscrittura

### Nuove dashboard MES da implementare (legge Onda)
- **Cashflow forecast** — fatture emesse + scadenze + pagamenti previsti
- **Recupero crediti** — clienti morosi, top 10 con importo + giorni scaduti
- **Margine commessa** — costi materiale MES + ore MES vs fatturato Onda
- **Top clienti YTD** — fatturato + commesse + scarto medio
- **Carta critica** — magazzino sotto soglia + impatto commesse attive
- **Scarto produzione** — top macchine + trend settimanale
- **Vincoli consegna** — commesse a rischio penale per ritardo

### Architettura
- Modulo `app/Modules/BI/` (nuovo)
- Service `OndaFinanceService` (estensione `OndaErpAdapter`)
- View Blade nel layout MES standard
- Cache Redis 15min su query Onda (heavy SQL Server)

### Effort stimato
- Service Onda finance read: 1 giorno
- 7 dashboard BI: 1 settimana
- Layout + componenti: 2 giorni
- Test + tuning: 2 giorni
- **Totale: ~2 settimane**

### Priorità
Media. Da pianificare DOPO completamento moduli rimanenti (Presenze,
Reportistica, AuditLog) e UI Overhaul Phase 1-2.

---

## ADDENDUM 9 — CLI MES via Printing Press (15/05/2026)

### Idea
Generare una Command Line Interface dedicata al MES (`mes-cli`) tramite
[Printing Press](https://printingpress.dev). Permette ad agenti AI
(Claude Code, Codex, Cursor) di interrogare il MES con un comando
terminale invece di chiamate HTTP/MCP.

### Vantaggi
- **35× meno token** rispetto a un server MCP (no schema upfront)
- Output preformattato e filtrato (no JSON gonfio)
- Latenza sub-100ms (chiamata locale, no roundtrip HTTP)
- Comandi componibili in shell pipeline

### Esempi comandi target
```
mes-cli fasi --stato 1 --reparto stampa
mes-cli commessa 67375
mes-cli ritardi --severita critico
mes-cli esterne --fornitore legokart
mes-cli presenti --oggi
```

### Architettura proposta
- CLI in Go (binario standalone, no dipendenze runtime)
- Si connette a MySQL .60 in lettura (read-only credentials)
- Schema generato da OpenAPI MES o reverse engineering routes Laravel
- Installazione: copia binario in `/usr/local/bin/` sul .60

### Setup richiesto
- Go 1.21+ + npm sul PC dev
- Printing Press CLI installato
- Genera da: pagina admin MES + routes Laravel (ispezione network)
- Test su 5-10 endpoint priority

### Decisione/Status
**Differito**. Marginale rispetto a script PHP esistenti (`check_xxx.php`).
Riprendere quando:
- Claude Code lavora frequentemente su .60 e serve interrogazione rapida
- Si vuole esporre MES ad altri agent AI senza creare API REST
- API REST v1 implementata (task #41) — allora CLI può consumarla
  direttamente come fonte

### Effort stimato
- Setup Printing Press + generazione CLI base: 2-4 ore
- Tuning + comandi custom: 1 giorno
- Deploy + test su .60: 2 ore
