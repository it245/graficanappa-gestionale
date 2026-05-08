# Strangler Fig Progress

Generato: 2026-05-07
Branch: `def2.0`

Stato dell'estrazione modulare DDD nel MES Grafica Nappa. I "wrapper legacy"
sono classi originali (pre-modular) che ora delegano ai nuovi moduli sotto
`App\Modules\*`. Restano in vita per backward compatibility con cron, console
commands e script di utility che non sono ancora stati migrati.

Eliminazione fisica dei wrapper: solo quando il numero di chiamanti residui
arriva a zero. NON forzare la migrazione se richiede refactor non banali —
la stabilità di produzione viene prima.

---

## Wrapper legacy ancora presenti

### 1. OndaSyncService (`app/Services/OndaSyncService.php`)

- **Righe**: 334 (era ~2009 prima dell'estrazione)
- **Stato**: thin wrapper, classe e metodi annotati `@deprecated`
- **Modulo target**:
  - `App\Modules\Onda\Services\OrdineSyncService::sync()`
  - `App\Modules\Onda\Services\CommessaSyncService::sync()`
  - `App\Modules\Reparti\Services\RepartoService::mappaSlugToId()`
  - `App\Modules\Reparti\Services\RepartoService::tipoFromCodice()`
  - `App\Modules\Spedizione\Services\DdtSyncService::syncFromOnda()`

- **Chiamanti residui** (5 file in `app/`):
  - `app/Console/Commands/SyncOnda.php` — cron `onda:sync`. Migrato a
    `OrdineSyncService::sync()` + `CommessaSyncService::sync()` +
    `DdtSyncService::syncFromOnda()`. **Restano sul wrapper solo le 2
    chiamate `sincronizzaDDTFornitore` / `sincronizzaDDTFornitureLavorazioni`**
    finché non viene estratto un modulo dedicato.
  - `app/Console/Commands/ImportExcelTutto.php` — Reflection su
    `OndaSyncService::getMappaReparti` per fallback mappa reparti (skip:
    refactor pesante).
  - `app/Http/Controllers/DashboardOwnerController.php` — endpoint sync
    manuale dal pannello owner. Migrato a `OrdineSyncService::sync()` +
    `DdtSyncService::syncFromOnda()`. Restano le 2 chiamate DDT Fornitore.
  - `app/Http/Controllers/DashboardOperatoreController.php:351` — **non
    migrato** (non era in scope inventory; 1 sola chiamata
    `OndaSyncService::sincronizza()`).
  - `app/Http/Controllers/DashboardSpedizioneController.php:590-593` —
    **non migrato** (non era in scope inventory; 4 chiamate statiche batch
    identiche al pattern owner).
  - Script standalone `confronta_tutte.php`, `import_commessa_onda.php` —
    skip (legacy una-tantum root).

- **Migrazione sicura "1-line"?** No. I metodi `sincronizzaDDTFornitore` e
  `sincronizzaDDTFornitureLavorazioni` non hanno ancora controparte in un modulo
  dedicato (solo `DdtSyncService` per le vendite). Inoltre `getMappaReparti`
  viene letto via `ReflectionMethod` in `ImportExcelTutto` — modificarlo
  richiede una migrazione coordinata.

- **Stato eliminazione**: pronto quando 0 chiamanti.

---

### 2. PrinectService (`app/Http/Services/PrinectService.php`)

- **Righe**: 197
- **Stato**: thin wrapper sopra `PrinectHttpAdapter`, classe annotata
  `@deprecated`. Mantiene 5 metodi legacy non ancora promossi nel Contract
  `PrinectApiInterface`.
- **Modulo target**:
  - `App\Modules\Prinect\Adapters\PrinectHttpAdapter` (transport REST)
  - `App\Modules\Prinect\Contracts\PrinectApiInterface` (interfaccia DI)
  - `App\Modules\Prinect\Services\*` (business logic: jobs, accounting, ink,
    auto-termina)

- **Chiamanti residui** (1 file in `app/`):
  - `app/Modules/Stampa/Adapters/PrinectAdapter.php` — il modulo Stampa
    inietta direttamente `PrinectService` come compatibility layer
    intenzionale (manterrà la dipendenza finché non promosso a
    `PrinectApiInterface`).
  - Migrati: `CommessaController` (`show`, `preview`), `KioskController`,
    `DashboardOwnerController` (`index`, `dettaglioCommessa`, `scheduling`)
    → ora iniettano `PrinectApiInterface` e usano `getWorksteps()` /
    `getWorkstepPreview()` / `getDeviceActivity()` / `getDevices()`.
  - Script standalone `retrofit_prinect_scarti.php` — skip (one-shot root).

- **Migrazione sicura "1-line"?** Sì per i Controller (i metodi usati erano
  già esposti da `PrinectApiInterface`, solo rinominato `getJobWorksteps` →
  `getWorksteps`). Resta da migrare il `PrinectAdapter` per non dipendere
  più da `PrinectService`.

- **Stato eliminazione**: pronto quando `PrinectAdapter` migra l'iniezione
  a `PrinectApiInterface`.

---

### 3. FieryService (`app/Http/Services/FieryService.php`)

- **Righe**: 581
- **Stato**: wrapper monolitico (non ancora thin). Aggiunto `@deprecated`
  docblock di classe in questo round.
- **Modulo target**:
  - `App\Modules\Stampa\Adapters\FieryAdapter` (per casi standard via
    `StampaIntegrationInterface`)
  - Service Fiery-specifici (accounting, server-status, login API v5) NON
    ancora estratti — restano in `FieryService` finché non viene promosso un
    modulo Stampa\Services\Fiery dedicato.

- **Chiamanti residui** (1 file in `app/`):
  - `app/Modules/Stampa/Adapters/FieryAdapter.php` — il modulo Stampa
    inietta `FieryService` come dipendenza per implementare l'interfaccia
    comune (caso intenzionale, non migrabile finché non si estraggono i
    metodi Fiery-only).
  - Migrati: `SyncFiery`, `WarmFieryCache`, `FieryReport` → ora iniettano
    `FieryAdapter` e usano i passthrough `fieryServerStatus()` /
    `fieryJobs()` / `fieryAccountingPerCommessa()` /
    `fieryFetchAccountingRaw()` / `fieryEstraiCommessaDaTitolo()` /
    `isOnline()`. Il login API v5 inline in `SyncFiery` e `FieryReport` è
    stato sostituito dal metodo `fieryFetchAccountingRaw()` dell'Adapter.

- **Migrazione sicura "1-line"?** Sì per i 3 console command, perché il
  `FieryAdapter` espone già passthrough Fiery-specifici. Resta da rifattorare
  il `FieryAdapter` stesso quando i metodi Fiery-only saranno promossi in
  Service nel modulo Stampa.

- **Stato eliminazione**: pronto quando i metodi Fiery-only vengono promossi
  in un Service del modulo Stampa e il `FieryAdapter` smette di iniettare
  `FieryService`.

---

## Moduli cablati live (validati produzione 2026-05-07)

Sync Onda live: `179 ordini creati, 371 aggiornati, 191 fasi, 102 DDT vendita`,
0 regressioni.

| Modulo | Caller principale | Stato |
|---|---|---|
| Fasi | `OrdineFaseController`, `DashboardOperatoreController` | live |
| Commessa | `CommessaController`, `OndaSyncService::sincronizzaSingolaCommessa` | live |
| Stampa | `DashboardOwnerController`, `KioskController` (via Adapter Prinect/Fiery) | live |
| Spedizione | `DashboardSpedizioneController`, `SyncOnda` (DDT vendita) | live |
| Onda | `SyncOnda`, `OndaSyncService` (thin wrapper) | live |
| Reparti | `OndaSyncService::getMappaReparti`, `ImportExcelTutto` | live |
| Fustelle | `DashboardOwnerController`, `CommessaController` | live |
| Magazzino | `MagazzinoController`, scarico carta da MES web | live |
| Notifiche | Polling owner ↔ spedizione, push browser | live |
| Prinect | `PrinectAdapter` → `PrinectHttpAdapter` (via wrapper) | live |
| Operatori | `OperatoreController`, kiosk auth | live (11/16) |

Moduli ancora da estrarre completamente: Fiery (services Fiery-only),
DDT Fornitore, DDT Forniture Lavorazioni, schedulazione Mossa 37 (in branch
separato).

---

## Pending eliminazioni — checklist

### OndaSyncService
- [ ] Estrarre `sincronizzaDDTFornitore` in `App\Modules\Spedizione\Services\DdtFornitoreSyncService`
- [ ] Estrarre `sincronizzaDDTFornitureLavorazioni` in `App\Modules\Spedizione\Services\DdtLavorazioniSyncService`
- [ ] Migrare `SyncOnda` console command alle nuove DI
- [ ] Migrare `DashboardOwnerController` (4 chiamate statiche) alle nuove DI
- [ ] Sostituire `ReflectionMethod(OndaSyncService, getMappaReparti)` con
      `app(RepartoService::class)->mappaSlugToId()` in `ImportExcelTutto`
- [ ] Spostare `confronta_tutte.php` e `import_commessa_onda.php` come Artisan
      commands se ancora utili (oggi sono script root)
- [ ] Eliminare `app/Services/OndaSyncService.php`

### PrinectService
- [ ] Promuovere i 5 metodi legacy non in Contract (devices, worksteps,
      preview, ink, jobs) in `PrinectApiInterface`
- [ ] Migrare `PrinectAdapter` (modulo Stampa) a iniettare
      `PrinectApiInterface` invece di `PrinectService`
- [ ] Migrare `CommessaController::show/preview` alla DI di `PrinectApiInterface`
- [ ] Migrare `KioskController` e `DashboardOwnerController` alla DI
- [ ] Convertire o eliminare `retrofit_prinect_scarti.php`
- [ ] Eliminare `app/Http/Services/PrinectService.php`

### FieryService
- [ ] Estrarre Service nel modulo Stampa per i metodi Fiery-only
      (`getServerStatus`, `getJobs`, `getAccountingPerCommessa`,
      `getAccountingRaw`, login API v5)
- [ ] Migrare i 3 console command (`fiery:sync`, `fiery:warm`, `fiery:report`)
      ai nuovi Service
- [ ] Migrare `FieryAdapter` (modulo Stampa) per non dipendere da
      `FieryService` ma dai nuovi Service
- [ ] Eliminare `app/Http/Services/FieryService.php`

---

## Note operative

- **NON eliminare wrapper finché chiamanti > 0**. Romperebbe cron in
  produzione (Onda sync ogni ora, Fiery ogni minuto, Prinect sync, ecc.).
- **NON cherry-pickare migrazioni su master** (vedi memoria utente:
  master = solo richiesto, redesign su def2.0).
- **Test esistenti devono restare verdi** dopo ogni round di cleanup.
- **Documentare ogni step di migrazione** in commit separati per facilitare
  rollback.
