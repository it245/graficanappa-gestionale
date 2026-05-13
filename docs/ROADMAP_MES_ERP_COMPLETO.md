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

## 8. ADDENDUM (12 MAGGIO 2026) — API INTERNA MES + MCP SERVER

Richiesta capo Antonio: esporre MES come API e MCP **per uso interno**.
- Query natural language via Claude Desktop / Cursor (capo + dev)
- Eventuali integrazioni custom in azienda
- Base per futura esposizione partner (Dedalo / SaaS multi-tenant) quando sarà il momento

**Scope ATTUALE = solo interno**. No partner exposure, no Sanctum complesso, no Swagger pubblico, no webhook esterni.

### 8.1 Fasi implementazione (scope interno)

| Fase | Durata | Deliverable | Budget |
|---|---|---|---|
| A1. API REST v1 read-only (uso interno) | 1-2 settimane | 8-10 endpoint core (commesse, fasi, macchine, reports). Auth: IP whitelist o token semplice .env | EUR 2-3K dev |
| A2. MCP server Python (FastMCP) | 1 settimana | 6 tool MCP (query/lista/genera/stato/report/cerca). Stdio locale per Claude Desktop | EUR 2K dev |
| A3. API write minimale (opzionale) | 3-5 giorni | PATCH stato fasi, POST etichette | EUR 1K dev |

Totale interno: **2-3 settimane**, ~EUR 4-5K dev.

### 8.1bis Estensioni future (NON in scope ora)

Quando il capo deciderà esposizione esterna:
- Sanctum tokens + scope per tenant
- Rate limit + Swagger pubblico
- Webhook outbound HMAC firmati
- CORS whitelist partner
- API v2 GraphQL
- SaaS multi-tenant via API

### 8.2 Tech stack (scope interno)

- **API REST**: Laravel 12 + API Resources, prefisso `/api/v1/`
- **Auth interna**: token statico in `.env` (`MES_API_TOKEN`) o IP whitelist Apache/middleware
- **MCP server**: Python 3.13 + FastMCP SDK Anthropic, wrappa API REST sotto
- **Deploy MCP**: locale via stdio (Claude Desktop su Mac capo e PC dev)
- **Docs**: README markdown interno (no Swagger pubblico)

### 8.3 Endpoint API v1 catalogo

GET /api/v1/commesse                 lista filtrata
GET /api/v1/commesse/{commessa}      dettaglio + fasi
GET /api/v1/ordini-fasi              query stato/reparto/operatore
GET /api/v1/ordini-fasi/{id}         dettaglio fase
GET /api/v1/macchine                 lista macchine + status
GET /api/v1/macchine/{id}/stato      Prinect/Fiery live
GET /api/v1/reports/giornaliero      KPI direzione
GET /api/v1/reports/ore              ore lavorate per periodo
GET /api/v1/clienti                  anagrafica
GET /api/v1/articoli                 magazzino
POST /api/v1/etichette               genera DataMatrix
PATCH /api/v1/ordini-fasi/{id}/stato cambio stato
POST /api/v1/webhook/subscribe       abbonamento eventi
GET /api/v1/health                   liveness

### 8.4 Tool MCP

| Tool | Function |
|---|---|
| query_commessa(id) | Dettagli commessa + fasi + scheduling |
| lista_fasi_attive(reparto?) | Fasi stato avviato/pronto |
| genera_preventivo(carta, qta, fasi) | Quote engine |
| stato_macchine() | XL106 / Fiery / Indigo live |
| report_giornaliero(data) | KPI fasi terminate + ore + scarti |
| cerca_cliente(nome) | Anagrafica + storico ordini |

### 8.5 Use case

- **Capo Antonio**: chat Claude Desktop → "quante commesse in ritardo oggi?" → MCP risponde
- **Dedalo (partner)**: HTTP integration MES nel loro gestionale → query commesse cliente in real-time
- **Cron AI**: scheduler Mossa 37 chiama API per propagazione fasi cross-instance multi-tenant
- **Mobile app**: app cliente B2B vede stato proprio ordine via API

### 8.6 Sicurezza API

- Token Sanctum con scope (read, write, admin)
- Rate limit per token
- Audit log automatico per ogni chiamata
- Webhook firmati HMAC-SHA256
- CORS whitelist per partner

### 8.7 Roadmap integrazione

| Q | Milestone |
|---|---|
| Q2 2026 | API REST v1 read-only + Sanctum + Swagger |
| Q3 2026 | MCP server Python + tool base |
| Q3 2026 | API write endpoints + webhook |
| Q4 2026 | Partner integration Dedalo (pilot) |
| Q1 2027 | API v2 + GraphQL (opzionale) |
| Q2 2027 | SaaS multi-tenant via API |

Status: PROPOSTA — attesa approvazione capo
Generato: 12 maggio 2026
