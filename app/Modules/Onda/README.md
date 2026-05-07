# Modulo Onda

Estrazione del God Class `App\Services\OndaSyncService` (1928 righe) verso un
modulo dedicato secondo il pattern **Strangler Fig**.

## Obiettivo

Sincronizza ordini, commesse, clienti e DDT dal gestionale ERP **Onda**
(SQL Server) verso il MES (MySQL).

Onda è il sistema legacy che la direzione vuole sostituire nel medio termine —
isolare l'integrazione qui significa che il MES non avrà mai un coupling diretto
con SQL Server, e in futuro potremo sostituire l'adapter (es. SAP, Onda v2)
senza toccare la business logic.

## Struttura

```
app/Modules/Onda/
├── Contracts/
│   └── OndaErpInterface.php       contratto astratto query Onda (no SQL)
├── Adapters/
│   └── OndaErpAdapter.php          impl SQL Server (sola I/O DB)
├── Services/
│   ├── OrdineSyncService.php      sync ordini produzione (TipoDocumento=2)
│   ├── ClienteSyncService.php     ricognizione clienti su `cliente_nome`
│   └── CommessaSyncService.php    sync singola commessa (no filtro data)
├── ValueObjects/
│   ├── CodiceCommessaOnda.php     parser codici NNNNNNN-AA + estrazione regex
│   └── DocumentoOnda.php           VO documento (id, tipo, data, ragione sociale)
├── Enums/
│   └── TipoDocumentoOnda.php      OrdineProduzione=2, DdtVendita=3, DdtFornitore=7
└── Events/
    ├── OrdineSincronizzato.php   dispatchato post-upsert ordine
    └── ClienteSincronizzato.php  dispatchato per ogni cliente distinto
```

## Wiring DI (ModulesServiceProvider)

```php
$this->app->bind(OndaErpInterface::class, OndaErpAdapter::class);
```

I tre `*SyncService` ricevono l'interfaccia via constructor injection:

```php
public function __construct(private readonly OndaErpInterface $onda) {}
```

In test si fornisce un fake/stub (vedi `tests/Unit/OrdineSyncServiceTest.php`)
senza dover collegare SQL Server.

## Stato della migrazione

**Iterazione attuale (questa PR):**

- I/O SQL spostato letteralmente in `OndaErpAdapter` (5 query da
  `OndaSyncService::sincronizza()` + `sincronizzaSingolaCommessa()` +
  `sincronizzaDDTFornitore()` + `sincronizzaDDTFornitureLavorazioni()`).
- `OndaSyncService` legacy ora chiama l'Adapter via container —
  `-112 righe` di SQL raw in meno (1928 → 1816).
- I servizi del modulo (`OrdineSyncService`, `CommessaSyncService`,
  `ClienteSyncService`) sono operativi ma in modalità "delegating wrapper":
  espongono l'API pulita ma il body principale resta nel legacy
  per non rompere il comportamento durante la migrazione.

**Iterazioni successive (next PRs):**

1. Spostare il body di `OndaSyncService::sincronizza()` (~870 righe) dentro
   `OrdineSyncService::sync()`, sostituendo `self::getMappaReparti()` /
   `self::getTipoReparto()` con un `MappaFasiOnda` config dedicato.
2. Spostare `sincronizzaSingolaCommessa` (~280 righe) in `CommessaSyncService`.
3. Convertire `sincronizzaDDTFornitore*` in due servizi dedicati
   (es. `App\Modules\Onda\Services\DdtFornitoreSyncService`).
4. Quando tutti i caller migrano, eliminare `OndaSyncService`.

## Metodi NON spostati (ancora)

- `getMappaReparti()` (~150 righe): mappatura statica codici fase Onda → reparto
  MES. Va estratta in `config/onda_mappa_reparti.php` o in una classe `MappaFasiOnda`
  ma è chiamata anche da `ImportExcelTutto` via `ReflectionMethod` (legacy
  coupling) — richiede aggiornamento contestuale di quel comando, fuori scope qui.
- `getTipoReparto()` (~140 righe): idem, va in `config/onda_tipo_reparto.php`.
- `sincronizzaDDTVendita()`: già wrapper di
  `Spedizione\Services\DdtSyncService::syncFromOnda()` (estrazione completata
  in PR precedente).

## Test

```bash
vendor/bin/phpunit tests/Unit/OrdineSyncServiceTest.php
```

Il test verifica che `OrdineSyncService::sync()` chiami l'adapter con la data
soglia corretta e mappi il risultato del legacy nel formato
`{inseriti, aggiornati, errori, ...}`.
