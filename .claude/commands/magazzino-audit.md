# Magazzino Audit — MES Grafica Nappa

Agisci come un Senior Supply Chain Analyst con esperienza in sistemi MES/WMS per tipografie industriali. Analizza il modulo magazzino del MES con occhio critico: coerenza, completezza, usabilità, e problemi potenziali.

## OBIETTIVO
1. Analizzare la struttura DB del magazzino (migration, model, relazioni)
2. Verificare coerenza tra categorie materiali e flusso produttivo reale
3. Identificare buchi funzionali (cosa manca per l'operatività quotidiana)
4. Fare l'avvocato del diavolo: trovare scenari che rompono la logica
5. Proporre migliorie concrete con priorità

## FASE 1: ANALISI STRUTTURA (lancia agenti in parallelo)

### Agente 1 — Schema DB e Model
Lancia un agente `Explore` per analizzare:
- TUTTE le migration in `database/migrations/*magazzino*`
- TUTTI i model in `app/Models/Magazzino*.php`
- Relazioni tra tabelle: articoli ↔ giacenze ↔ movimenti ↔ etichette
- Campi: sono sufficienti? Mancano campi critici?
- Indici: ci sono quelli necessari per le query frequenti?
- Vincoli: unique, foreign key, default values — sono corretti?

Verifica specificamente:
- La colonna `categoria` copre tutti i materiali (carta, foil, scatoloni, inchiostro, vernici)?
- L'UM `fg` come default ha senso per inchiostro/vernici (che vanno a kg)?
- La tabella `magazzino_giacenze` con chiave `(articolo_id, ubicazione_id, lotto)` — funziona senza ubicazioni?
- `magazzino_movimenti.quantita`: è signed? Può essere negativo per scarichi?
- `magazzino_etichette`: il QR code punta dove? L'URL è hardcoded?

### Agente 2 — Controller e Logica Business
Lancia un agente `Explore` per analizzare:
- `app/Http/Controllers/MagazzinoController.php`
- `app/Http/Controllers/MagazzinoMovimentoController.php`
- `app/Http/Controllers/MagazzinoOcrController.php`
- `app/Http/Controllers/MagazzinoEtichettaController.php`
- `app/Http/Controllers/MagazzinoScannerController.php`
- `app/Services/MagazzinoService.php` (se esiste)
- `app/Services/OcrBollaService.php`

Verifica:
- Il carico aggiorna correttamente la giacenza?
- Lo scarico verifica che ci sia giacenza sufficiente?
- Il reso funziona correttamente (incrementa giacenza)?
- La rettifica inventariale è implementata?
- L'OCR: cosa succede se fallisce? C'è fallback manuale?
- Lo scanner QR: cosa succede se il QR non esiste?
- Le transazioni DB sono usate? (carico/scarico devono essere atomici)
- C'è validazione input su tutti i form?
- Ci sono race condition possibili? (due operatori scaricano lo stesso bancale)

### Agente 3 — Flusso Produttivo e Coerenza
Lancia un agente `Explore` per verificare la coerenza con il flusso produttivo:
- Leggi `app/Services/OndaSyncService.php` — come importa i dati carta da Onda
- Leggi la config `config/fasi_ore.php` — le fasi usano qta_carta
- Leggi `app/Http/Controllers/DashboardOwnerController.php` — come mostra carta/formato
- Leggi le view in `resources/views/magazzino/`

Verifica:
- Il `cod_carta` degli ordini corrisponde al `codice` degli articoli magazzino?
- Quando un ordine viene importato da Onda, l'articolo viene creato automaticamente in magazzino?
- Lo scarico avviene SOLO per fase STAMPA? Come è implementato questo vincolo?
- Il formato supporto reale (supp_base_cm × supp_altezza_cm) è usato nel magazzino?
- Se un articolo non esiste in anagrafica, cosa succede al carico?
- La quantità carta nell'ordine (`qta_carta`) corrisponde a quella scaricata dal magazzino?

### Agente 4 — Scenari Critici (Avvocato del Diavolo)
Lancia un agente `Explore` per trovare scenari che rompono la logica:
- Cosa succede se scarico più carta di quella disponibile?
- Cosa succede se due operatori scaricano dallo stesso bancale contemporaneamente?
- Cosa succede se un ordine usa carta che non è in anagrafica magazzino?
- Cosa succede se la bolla OCR legge dati sbagliati e l'operatore conferma?
- Cosa succede se un bancale viene spostato ma il QR non viene aggiornato?
- Cosa succede se l'inchiostro viene usato in più commesse (scarico frazionato)?
- Cosa succede se arriva carta dal fornitore con lotto diverso ma stesso codice?
- Cosa succede se la giacenza va in negativo (scarico senza carico precedente)?
- Cosa succede se il wifi cade durante uno scarico (transazione parziale)?
- Cosa succede se un operatore scansiona un QR vecchio/cancellato?
- La soglia minima funziona con materiali a kg (inchiostro 2.5kg rimasti)?
- Il fabbisogno materiali tiene conto degli ordini in coda (non solo corrente)?

## FASE 2: REPORT

### Sezione 1: Struttura DB — Problemi trovati
| # | Problema | Gravità | File:riga | Fix proposto |
|---|----------|---------|-----------|-------------|

### Sezione 2: Logica Business — Buchi funzionali
| # | Scenario | Impatto | Fix proposto | Complessità |
|---|----------|---------|-------------|-------------|

### Sezione 3: Coerenza con produzione
| # | Incoerenza | Dove | Come risolvere |
|---|-----------|------|----------------|

### Sezione 4: Scenari critici (Avvocato del Diavolo)
| # | Scenario | Cosa succede ora | Cosa dovrebbe succedere | Fix |
|---|----------|-----------------|------------------------|-----|

### Sezione 5: Migliorie proposte (ordinate per priorità)
1. **CRITICO** — da fare prima del go-live
2. **ALTO** — da fare entro 1 settimana dal go-live
3. **MEDIO** — da fare entro 1 mese
4. **BASSO** — nice-to-have

### Sezione 6: Categorie materiali — Analisi
Per ogni categoria (carta, foil, scatoloni, inchiostro, vernici):
- UM corretta?
- Campi specifici mancanti?
- Flusso carico/scarico adatto?
- Soglia minima ha senso?
- Etichetta QR applicabile?

## REGOLE
- Ogni affermazione DEVE avere file:riga come riferimento
- Essere CRITICI: trovare problemi, non confermare che va tutto bene
- Considerare l'operatività reale: operatori con guanti, tablet, wifi instabile
- Il magazzino è in una tipografia, non in un centro logistico Amazon
- L'addetto magazzino è Emanuele Marcone (reparto spedizione)
- Prioritizzare sicurezza dati > usabilità > performance
- NON proporre soluzioni che richiedono hardware aggiuntivo
