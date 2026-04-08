# v2.0 Release Check — MES Grafica Nappa

Skill omnicomprensiva per il rilascio della v2.0. Incorpora e coordina tutte le altre skill per una verifica completa pre-rilascio.

## OBIETTIVO
Verificare che la v2.0 sia pronta per il rilascio: funzionalità, performance, sicurezza, UI, bug, e differenze rispetto alla v1.0.

## FASE 1: DIFF v1 vs v2 (Analisi Differenze)

### Agente 1 — Differenze Codice
Lancia un agente per analizzare:
```
git diff master..def2.0 --stat
git diff master..def2.0 -- app/Http/Controllers/
git diff master..def2.0 -- resources/views/
git diff master..def2.0 -- app/Services/
git diff master..def2.0 -- routes/web.php
git diff master..def2.0 -- config/
```

Genera un report con:
- File NUOVI nella v2.0 (non esistono nel master)
- File MODIFICATI (con descrizione delle differenze)
- File RIMOSSI
- Route nuove o cambiate
- Config nuove o cambiate

### Agente 2 — Funzionalità v2.0 vs v1.0
Verifica che tutte le feature della v1.0 funzionino ancora nella v2.0:
- Login operatore/owner/admin
- Dashboard operatore: avvia/pausa/termina fase
- Dashboard owner: filtri, KPI, modali, contenteditable
- Dashboard spedizione: consegne, BRT tracking, DDT PDF
- Prestampa: lista + dettaglio + campi editabili
- Etichette: stampa, EAN, DataMatrix
- Report Ore: grafici, filtri, KPI
- Scheduling: Gantt, tabella priorità
- Sync Onda, Prinect, Fiery
- Notifiche, note, chat

## FASE 2: VERIFICHE INCROCIATE

### /bug-hunter sulla v2.0
Lancia la skill bug-hunter focalizzata su:
- Errori JS nella console (addEventListener su null, variabili undefined)
- PHP errors/warnings nei log
- Funzionalità rotte dal redesign UI
- Race condition tra sync e azioni utente

### /perf-check sulla v2.0
Lancia la skill perf-check per verificare:
- Tempi di caricamento delle pagine principali
- Query N+1 residue
- Script caricati inutilmente
- Polling eccessivo

### /security sulla v2.0
Verifica:
- Endpoint senza autenticazione
- IDOR su eliminaFase/aggiornaStato
- XSS via innerHTML
- CSRF token refresh funzionante
- Input validation

## FASE 3: CHECKLIST PRE-RILASCIO

### Funzionalità Core
- [ ] Login operatore funziona
- [ ] Login owner funziona
- [ ] Dashboard operatore: fasi visibili per reparto
- [ ] Dashboard operatore: avvia/pausa/termina fase
- [ ] Dashboard owner: tabella ordini con filtri
- [ ] Dashboard owner: KPI giornalieri
- [ ] Dashboard owner: modali (storico, BRT, note, presenti)
- [ ] Dashboard spedizione: DDT, consegne, BRT tracking
- [ ] Dashboard spedizione: pulsante PDF DDT
- [ ] Prestampa: lista commesse + dettaglio
- [ ] Etichette: stampa per tutti i clienti (IC, Tifata, semplificati)
- [ ] Scheduling: Gantt con dati scheduler PHP
- [ ] Report Ore: KPI + grafici Chart.js
- [ ] Fasi Terminate: lista + filtri
- [ ] Fustelle: overview
- [ ] Esterne: lista + rientro
- [ ] Reparti Overview
- [ ] Presenze: storico timbrature
- [ ] Chat MES: widget flottante funzionante

### UI Enterprise
- [ ] Tutte le viste usano layouts.mes
- [ ] Sidebar con navigazione corretta
- [ ] Topbar con titolo, dark mode, orologio, avatar
- [ ] Dark mode funziona su tutte le pagine
- [ ] Colori percorso produttivo visibili (base/rilievi/caldo/completo)
- [ ] Chat widget flottante in basso a destra
- [ ] Responsive su mobile/tablet
- [ ] Font Inter caricato con display:swap

### Performance
- [ ] Script lazy-loaded (Choices, QRcode, Echo solo dove servono)
- [ ] Query ottimizzate (no N+1 su owner dashboard)
- [ ] CSRF token refresh ogni 30min
- [ ] Polling chat: 10s aperta, 30s badge
- [ ] Pagine caricano in <3s

### Scheduler Mossa 37
- [ ] `php artisan scheduler:run` funziona senza errori
- [ ] Gantt mostra dati da scheduler PHP (non calcolo client-side)
- [ ] BRT/spedizione hanno date previste
- [ ] disponibile_da rispettato (macchina aspetta predecessori)
- [ ] Export Excel piano produzione

### Integrazioni
- [ ] Sync Onda funziona (bottone + schedulato)
- [ ] DDT PDF automatico su nuovi DDT
- [ ] Sync Prinect non sovrascrive terminata_manualmente
- [ ] Stato 5 (esterno) gestito ovunque

### Database
- [ ] Migration `php artisan migrate` senza errori
- [ ] Nessuna colonna duplicata
- [ ] Indici performance presenti

## FASE 4: DEPLOY PLAN

### Pre-deploy (sul server .60)
1. `cd C:\progetti\graficanappa-gestionale`
2. `git stash` (salva modifiche locali)
3. `git fetch origin`
4. `git checkout def2.0`
5. `composer install --no-dev`
6. `php artisan migrate`
7. `php artisan config:cache`
8. `php artisan route:cache`
9. `php artisan view:clear`
10. Aggiornare Apache DocumentRoot se necessario

### Post-deploy
1. Verificare login
2. Verificare dashboard owner
3. Verificare sync Onda
4. Verificare DDT PDF
5. Eseguire scheduler: `php artisan scheduler:run`
6. Monitorare logs per 1h

### Rollback (se problemi)
1. `git checkout master`
2. `php artisan config:cache`
3. `php artisan view:clear`
4. Riavviare Apache

## COME USARE

Quando l'utente invoca `/v2-release-check`:
1. Lancia Fase 1 (diff analysis) in parallelo
2. Esegui le verifiche incrociate (bug, perf, security)
3. Compila la checklist
4. Segnala problemi critici da risolvere prima del rilascio
5. Genera il deploy plan finale
