# Registro Trattamento Dati — MES Grafica Nappa

Agisci come un Data Protection Officer (DPO) specializzato in GDPR per aziende manifatturiere italiane. Genera il Registro delle Attivita di Trattamento ai sensi dell'art. 30 del Regolamento UE 2016/679 (GDPR).

## FASE 1: ANALISI AUTOMATICA DEL CODEBASE

Lancia in parallelo questi agenti di ricerca per individuare TUTTI i dati personali trattati dal sistema:

### Agente 1 — Dati Dipendenti e Presenze
Lancia un agente `Explore` per cercare:
- Tabella `nettime_anagrafica`: campi (matricola, cognome, nome)
- Tabella `nettime_timbrature`: campi (matricola, data_ora, verso, terminale)
- Tabella `operatori`: campi personali (nome, cognome, codice_operatore, password)
- Tabella `turni`: campi (cognome_nome, data, turno)
- Tabella `fase_operatore`: pivot con data_inizio, data_fine, secondi_pausa
- `SyncPresenze.php`: come vengono raccolti i dati (NetTime share)
- `PresenzeController.php`: come vengono visualizzati
- `ExportPresenzeExcel.php`: come vengono esportati
- Retention policy (PulisciAuditLog: 90 giorni audit)

### Agente 2 — Audit e Tracciamento
Lancia un agente `Explore` per cercare:
- Tabella `audit_logs`: campi (user_id, user_name, ip, user_agent)
- Tabella `trusted_devices`: campi (device_name, ip_first_use)
- Tabella `operatore_tokens`: campi (token, expires_at)
- Tabella `sessions`: dati sessione (ip_address, user_agent, payload)
- AuditService: quali dati personali vengono loggati

### Agente 3 — Integrazioni Esterne
Lancia un agente `Explore` per cercare:
- Integrazione BRT: quali dati vengono inviati (nomi, indirizzi?)
- Integrazione Onda: quali dati personali vengono sincronizzati
- Integrazione Prinect: operatori, matricole
- Email SMTP: destinatari, contenuti
- Credenziali .env: dove vengono trasmessi dati

## FASE 2: GENERAZIONE DOCUMENTO

Genera un file HTML stampabile A4 con:

### Struttura (art. 30 GDPR)

```
INTESTAZIONE
- GRAFICA NAPPA S.R.L. — Via Gramsci, 19 — 81031 Aversa (CE)
- "REGISTRO DELLE ATTIVITA DI TRATTAMENTO"
- Art. 30 Regolamento UE 2016/679 (GDPR)
- Codice: DOC-GDPR-MES-001
- Versione: 1.0
- Data emissione
- Titolare del trattamento: Grafica Nappa S.R.L.
- Redatto da: Giovanni Pietropaolo

Per OGNI trattamento identificato nel codice:

1. DENOMINAZIONE del trattamento
2. FINALITA del trattamento
3. BASE GIURIDICA (art. 6 GDPR: consenso, contratto, obbligo legale, interesse legittimo)
4. CATEGORIE DI INTERESSATI (dipendenti, operatori, clienti)
5. CATEGORIE DI DATI PERSONALI trattati
6. DESTINATARI dei dati (interni, esterni)
7. TRASFERIMENTI verso paesi terzi (si/no)
8. TERMINI DI CANCELLAZIONE previsti (retention)
9. MISURE DI SICUREZZA tecniche e organizzative (riferimento a DOC-SEC-MES-001)
10. SISTEMA/APPLICAZIONE che effettua il trattamento
11. RESPONSABILE INTERNO

Sezioni obbligatorie:
- Informazioni sul Titolare del trattamento
- Elenco trattamenti in formato tabellare
- Misure di sicurezza (riferimento al documento sicurezza)
- Procedura aggiornamento registro
- Firma
```

### Trattamenti da documentare (basati sull'analisi del codice):
1. Gestione presenze e timbrature dipendenti
2. Gestione turni lavorativi
3. Tracciamento attivita operatori su fasi di produzione
4. Audit log accessi e operazioni
5. Gestione sessioni e dispositivi fidati (2FA)
6. Comunicazione dati a vettore BRT (spedizioni)
7. Sincronizzazione dati con ERP Onda

### Stile
- Identico a DOC-SEC-MES-001 (stessi font, colori, layout)
- HTML stampabile A4
- Tabelle formali con bordi
- Classificazione: RISERVATO
- Firma digitale compilabile (come il documento sicurezza)

### Output
Salva come `public/gdpr_registro_trattamenti.html`
