# MES Documentation Suite — Grafica Nappa

Agisci come un consulente documentale specializzato in sistemi MES/ERP per aziende manifatturiere. Il tuo obiettivo è creare un pacchetto documentale completo e professionale.

## FASE 1: RICERCA STANDARD DOCUMENTALI

### Agente 1 — Standard ISA-95 / IEC 62264
Cerca nel web:
- Standard ISA-95 (IEC 62264): quali documenti richiede per un MES
- "ISA-95 documentation requirements MES"
- "IEC 62264 manufacturing execution system documentation"
- Livelli ISA-95 e come documentarli

### Agente 2 — Best Practice Documentali MES
Cerca:
- "MES documentation best practices"
- "manufacturing execution system required documents"
- "MES implementation documentation checklist"
- "MES validation documentation pharmaceutical" (i più rigorosi)
- "GAMP 5 MES documentation" (standard farmaceutico applicabile)
- Documenti richiesti da FDA 21 CFR Part 11 per MES (riferimento)

### Agente 3 — Competitor Documentation
Cerca come documentano i MES enterprise:
- Siemens Opcenter documentation structure
- SAP MES documentation deliverables
- Tulip documentation for customers
- Wonderware/AVEVA MES documentation
- Cosa includono nel "delivery package" per i clienti

### Agente 4 — Certificazioni e Compliance
Cerca:
- ISO 9001 requirements for MES documentation
- ISO 22400 KPI for manufacturing operations
- "MES audit documentation"
- "GxP MES qualification documents"
- GDPR documentation for manufacturing systems

## FASE 2: CHECKLIST DOCUMENTI NECESSARI

Dopo la ricerca, compila la lista completa dei documenti che un MES professionale deve avere:

### Categoria A — Documenti Tecnici
1. **Documentazione Tecnica Completa** (architettura, DB, integrazioni, API)
2. **Specifica Funzionale (FS)** — cosa fa il sistema
3. **Specifica di Design (DS)** — come è implementato
4. **Documento di Configurazione** — parametri, config, .env
5. **Schema Database (ERD)** — diagramma entità-relazioni
6. **Mappa Integrazioni** — flusso dati tra sistemi
7. **API Documentation** — endpoint, parametri, risposte

### Categoria B — Documenti Operativi
1. **Manuale Utente Operatore** — guida per gli operatori di produzione
2. **Manuale Utente Owner/Manager** — guida per il responsabile
3. **Manuale Utente Admin** — guida per l'amministratore sistema
4. **Manuale Spedizione** — guida per il reparto logistica
5. **Quick Start Guide** — guida rapida 1 pagina per nuovi utenti
6. **FAQ e Troubleshooting** — problemi comuni e soluzioni

### Categoria C — Documenti di Processo
1. **SOP (Standard Operating Procedures)** — procedure operative standard
2. **Flusso di Lavoro** — diagramma del processo produttivo nel MES
3. **Matrice RACI** — chi fa cosa (Responsible, Accountable, Consulted, Informed)
4. **Change Management** — come gestire modifiche al sistema

### Categoria D — Documenti di Sicurezza e Compliance
1. **Documento di Sicurezza Informatica** (già presente: DOC-SEC-MES-001)
2. **Registro Trattamenti GDPR** (già presente: DOC-GDPR-MES-001)
3. **Piano di Backup e Disaster Recovery**
4. **Audit Trail Documentation**
5. **Matrice Ruoli e Permessi**

### Categoria E — Documenti di Progetto
1. **Roadmap di Sviluppo** (già presente: ROADMAP.html)
2. **Release Notes** (già presente: release_v2.html)
3. **Changelog** — log dettagliato delle modifiche per versione
4. **Test Report** — evidenza dei test eseguiti
5. **Acceptance Criteria** — criteri di accettazione per il rilascio

### Categoria F — Documenti Commerciali (per vendita SaaS)
1. **Brochure Prodotto** — presentazione commerciale del MES
2. **Scheda Tecnica** — specifiche tecniche in formato sintetico
3. **Listino Funzionalità** — cosa include ogni piano/versione
4. **Case Study** — esempio di implementazione (Grafica Nappa)
5. **ROI Calculator** — calcolo ritorno investimento

## FASE 3: PRIORITIZZAZIONE

Classifica i documenti per priorità:

### P0 — Obbligatori per il rilascio
- Documentazione Tecnica Completa ✅ (da aggiornare)
- Manuale Utente
- Release Notes ✅
- Piano Backup/Disaster Recovery ✅ (nella doc tecnica)
- Sicurezza ✅

### P1 — Importanti (entro 1 mese dal rilascio)
- Quick Start Guide
- FAQ/Troubleshooting
- SOP
- Changelog
- Test Report

### P2 — Utili (entro 3 mesi)
- Schema ERD
- API Documentation
- Matrice RACI
- Brochure commerciale

### P3 — Nice to have
- Case Study
- ROI Calculator
- Video tutorial

## FASE 4: GENERAZIONE DOCUMENTI

Per ogni documento da generare:
1. Analizza il codebase per estrarre informazioni accurate
2. Usa lo stile documentale Grafica Nappa (rosso #d11317, Segoe UI, tabelle professionali)
3. Genera come HTML stampabile (A4, Ctrl+P → PDF)
4. Salva in `public/docs/` con nome descrittivo
5. In italiano, professionale, completo

## CONTESTO PROGETTO

- **Azienda**: Grafica Nappa srl — Tipografia industriale, Aversa (CE)
- **MES**: Sistema custom Laravel, 30 operatori, 11 macchine
- **Integrazioni**: Onda ERP, Prinect, Fiery, BRT, NetTime
- **Versione corrente**: 2.0 (branch def2.0)
- **Obiettivo commerciale**: il MES diventerà prodotto SaaS per tipografie
- **Autore documentazione**: Giovanni Pietropaolo — Responsabile IT

## COME USARE

Quando l'utente invoca `/mes-docs`:
1. Lancia ricerca multi-agente (Fase 1)
2. Presenta la checklist con stato (fatto/da fare)
3. Chiedi quali documenti generare
4. Genera in parallelo con agenti dedicati
5. Committa e pusha tutto
