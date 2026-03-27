# Security Documentation — MES Grafica Nappa

Agisci come un Senior Security Auditor e Technical Writer certificato ISO 27001. Il tuo obiettivo è analizzare il codebase del MES, scoprire TUTTI i protocolli e livelli di sicurezza implementati, e generare un documento ufficiale da archiviare.

Questo documento è UFFICIALE — va stampato, firmato e archiviato in azienda. Deve essere accurato al 100%, tecnico e conforme agli standard di documentazione ISO.

## FASE 1: ANALISI AUTOMATICA DEL CODEBASE (Multi-Agente)

Lancia in parallelo questi agenti di ricerca:

### Agente 1 — Autenticazione e Accesso
Lancia un agente `Explore` per cercare nel codebase:
- Middleware di autenticazione (OperatoreAuth, AdminAuth, OwnerMiddleware, OwnerOrAdmin)
- Login flow: come funziona il login operatore, admin, owner
- Token system (OperatoreToken)
- 2FA: tabelle trusted_devices, TwoFactorService, TwoFactorController
- Guard Laravel configurati in config/auth.php
- Session configuration (config/session.php): lifetime, encryption, cookie settings
- CSRF protection: middleware, refresh token, eccezioni

### Agente 2 — RBAC e Permessi
Lancia un agente `Explore` per cercare:
- spatie/laravel-permission: ruoli e permessi configurati
- RbacSeeder: quali ruoli esistono, quali permessi per ruolo
- HasRoles trait sul model Operatore
- Middleware di protezione route per ruolo
- Route groups e i middleware applicati (routes/web.php)

### Agente 3 — Audit, Logging e Monitoring
Lancia un agente `Explore` per cercare:
- AuditService: cosa viene tracciato (login, logout, cambio stato, delete)
- OrdineFaseObserver: eventi osservati
- Tabella audit_logs: struttura, indici, retention
- PulisciAuditLog command: pulizia automatica
- Rate limiting: configurazione in AppServiceProvider
- Log channels configurati

### Agente 4 — Protezione Dati e Headers
Lancia un agente `Explore` per cercare:
- SecurityHeaders middleware: quali header sono impostati
- Session encryption (SESSION_ENCRYPT)
- Credenziali in .env vs hardcoded nel codice
- NETTIME_SHARE_USER/PASS in .env
- Encryption dei campi sensibili (two_factor_secret encrypted)
- Input validation: FormRequest, validate(), strip_tags
- Output escaping: {!! !!} vs {{ }} nei blade templates
- CSRF token refresh mechanism

### Agente 5 — Configurazione Server e Rete
Lancia un agente `Explore` per cercare:
- .env configuration: APP_DEBUG, APP_ENV
- Database connections: come sono protette
- File .gitignore: cosa viene escluso dal repo
- Trusted proxies configuration
- CORS settings

## FASE 2: GENERAZIONE DOCUMENTO UFFICIALE

Dopo aver raccolto tutti i dati, genera un file HTML stampabile con questo schema:

### Struttura del documento

```
INTESTAZIONE UFFICIALE
- Intestazione: GRAFICA NAPPA S.R.L. — Via Gramsci, 19 — 81031 Aversa (CE)
- Titolo: "DOCUMENTO DI SICUREZZA INFORMATICA"
- Sottotitolo: "Protocolli di Sicurezza — Sistema MES (Manufacturing Execution System)"
- Codice documento: DOC-SEC-MES-001
- Versione: 1.0
- Classificazione: RISERVATO — USO INTERNO
- Data emissione
- Redatto da: Giovanni Pietropaolo — Analista e Progettista Basi Dati / Responsabile Sistemi Informativi

1. PREMESSA E AMBITO DI APPLICAZIONE
   - Finalita del documento
   - Perimetro del sistema (MES, server, rete, integrazioni)
   - Riferimenti normativi applicabili (ISO 27001, OWASP Top 10, GDPR art. 32)
   - Destinatari del documento

2. DESCRIZIONE DEL SISTEMA
   - Architettura applicativa (Laravel, MySQL, Blade, integrazioni)
   - Infrastruttura (server .60, rete locale, accesso)
   - Utenti e ruoli operativi
   - Integrazioni esterne (Onda, Prinect, Fiery, BRT, NetTime)

3. PROTOCOLLI DI SICUREZZA ATTIVI
   Per ogni protocollo trovato nel codice:
   - Codice protocollo (es. SEC-AUTH-001)
   - Denominazione
   - Descrizione tecnica
   - Stato: OPERATIVO / IN IMPLEMENTAZIONE / PIANIFICATO
   - Data di attivazione
   - Componenti tecnici coinvolti
   - Riferimento normativo (ISO/OWASP)
   - Responsabile
   - Note

4. CONTROLLO ACCESSI E AUTENTICAZIONE
   - Modalita di accesso per ruolo
   - Politica credenziali
   - Gestione sessioni
   - Autenticazione a due fattori (2FA)
   - Token di autenticazione per-tab

5. SISTEMA RBAC (Role-Based Access Control)
   - Ruoli definiti e relativi permessi
   - Matrice ruoli-permessi
   - Middleware di enforcement

6. TRACCIABILITA E AUDIT
   - Sistema di audit log
   - Eventi tracciati
   - Periodo di conservazione
   - Modalita di consultazione
   - Conformita requisiti di tracciabilita

7. PROTEZIONE DELLE COMUNICAZIONI
   - Security headers HTTP
   - Protezione CSRF
   - Cookie policy (HttpOnly, SameSite, Secure)
   - Cifratura sessioni

8. PROTEZIONE DEI DATI
   - Gestione credenziali e segreti (.env isolation)
   - Cifratura dati sensibili a riposo
   - Input validation e sanitization
   - Output encoding (XSS prevention)
   - Politica di conservazione dati

9. CONTROLLO DEL TRAFFICO
   - Rate limiting per endpoint
   - Protezione brute force su autenticazione
   - Architettura di rete (segmentazione, accesso esterno)

10. MATRICE DEI RISCHI E MITIGAZIONI
    Tabella formale:
    - ID | Minaccia | Probabilita (B/M/A) | Impatto (B/M/A) | Contromisura attiva | Rischio residuo | Stato

11. PIANO DI EVOLUZIONE SICUREZZA
    Protocolli pianificati per implementazioni future:
    - API Authentication (Laravel Sanctum) — per fase commerciale SaaS
    - Multi-tenancy con isolamento dati per cliente
    - Conformita GDPR completa (diritto all'oblio, portabilita dati, registro trattamenti)
    - Single Sign-On (SAML 2.0 / OAuth2)
    - IP Whitelisting per ruoli privilegiati
    - Vulnerability scanning automatizzato (composer audit, OWASP ZAP)
    - Web Application Firewall (WAF)
    - Penetration testing periodico

12. REGISTRO DELLE REVISIONI
    Tabella: Versione | Data | Descrizione modifica | Autore

13. APPROVAZIONE E FIRMA
    - Redatto da: _________________________ (Giovanni Pietropaolo)
      Qualifica: Analista e Progettista Basi Dati / Resp. Sistemi Informativi
      Data: __/__/____
      Firma: _________________________

    - Verificato e approvato da: _________________________
      Qualifica: Direzione Aziendale
      Data: __/__/____
      Firma: _________________________

    - Prossima revisione prevista: [6 mesi dalla data di emissione]
```

### Stile del documento
- HTML stampabile con CSS @media print ottimizzato per A4
- Font: Georgia per testo corpo, monospace per riferimenti tecnici
- Colori: rosso #D11317 per intestazione aziendale, nero per testo, grigio scuro per sezioni
- Tabelle con bordi solidi, header scuri
- Badge stato: OPERATIVO (verde #28a745), IN IMPLEMENTAZIONE (#0d6efd), PIANIFICATO (#e67e22)
- Numerazione sezioni gerarchica (1, 1.1, 1.1.1)
- Header pagina: "GRAFICA NAPPA S.R.L. — DOC-SEC-MES-001 — RISERVATO"
- Footer pagina: numero pagina
- Page break tra sezioni principali
- Indice dei contenuti
- Linguaggio tecnico formale (registro ISO)

### Output
Salva il documento come `public/security_protocols.html`.
NON creare file .md — solo HTML stampabile.

## PRINCIPI
- **Accuratezza assoluta**: ogni protocollo DEVE essere verificato nel codice — nessuna invenzione
- **Linguaggio tecnico ISO**: registro formale, terminologia standard sicurezza informatica
- **Ufficialita**: documento per archiviazione aziendale — impeccabile
- **Completezza**: ogni misura di sicurezza trovata nel codice va documentata
- **Verificabilita**: per ogni protocollo, indicare il componente tecnico di riferimento
