# Modulo `Prinect`

## Scopo

Modulo dedicato all'integrazione **Heidelberg Prinect Pressroom Manager** (XL106).

Estratto dal monolite `App\Http\Services\PrinectService` con pattern
**Strangler Fig**: il service legacy (e l'adapter `Modules\Stampa\PrinectAdapter`)
continuano a esistere come wrapper di compatibilità; nuovo codice deve
dipendere dai contratti di questo modulo.

## Struttura

```
Modules/Prinect/
├── Contracts/
│   └── PrinectApiInterface          # contratto REST Heidelberg, no Http
├── Adapters/
│   └── PrinectHttpAdapter           # impl con Http facade + Basic Auth
├── Services/
│   ├── PrinectJobsService           # query jobs/worksteps + mapping commessa
│   ├── PrinectInkService            # inkConsumption + cache 1h
│   ├── PrinectAccountingService     # tempi avviamento/esecuzione da activities
│   └── PrinectAutoTerminaService    # logica auto-termina (fix 66811)
├── ValueObjects/
│   ├── WorkstepId                   # (jobId, workstepId) immutabile
│   ├── TempiStampa                  # avviamento + esecuzione in secondi
│   └── ConsumoInchiostro            # CMYK + spot in grammi
├── Enums/
│   └── StatoWorkstep                # WAITING/RUNNING/COMPLETED/ABORTED
└── Events/
    ├── WorkstepAvviato
    └── WorkstepCompleted
```

## API pubblica

```
interface PrinectApiInterface
    isConfigured(): bool
    getDevices(): ?array
    getDeviceActivity(string $deviceId, ?string $start, ?string $end): ?array
    getDeviceConsumption(string $deviceId, ?string $start, ?string $end): ?array
    getJobs(?string $modifiedSince, ?string $globalStatus): ?array
    getJob(string $jobId): ?array
    getWorksteps(string $jobId): ?array
    getWorkstepActivities(string $jobId, string $workstepId): ?array
    getWorkstepInk(string $jobId, string $workstepId): ?array
    getWorkstepQuality(string $jobId, string $workstepId): ?array
    getWorkstepPreview(string $jobId, string $workstepId): ?array
    getMilestones(?string $workstepId = null): ?array

PrinectJobsService
    getWorkstepsStampa(jobId): Collection<workstep[]>     // filtra ConventionalPrinting
    getWorkstepsByStato(jobId, StatoWorkstep): Collection
    commessaToJobId('0066811-26'): '66811'
    jobIdToCommessa('66811', '26'): '0066811-26'
    estraiJobIdNumerico(int|string|null): ?string         // statico
    stampaConfermata(jobId, workstep[]): bool             // fix bug 66811

PrinectInkService
    getConsumo(WorkstepId, force=false): ?ConsumoInchiostro
    invalidaCache(WorkstepId): void
    preWarm(iterable<WorkstepId>): int

PrinectAccountingService
    getTempi(WorkstepId): TempiStampa
    aggregaAttivita(iterable): TempiStampa                // accetta API raw o Eloquent

PrinectAutoTerminaService
    deveTerminare(Collection<workstep>, qtaCarta): bool
    deveRipristinare(Collection<workstep>): bool
    eAbbandonata(?DateTime, now=null): bool
    attivitaRecente(?DateTime, now=null): bool
    filtraConfermati(jobId, Collection): Collection
    ultimaDataFine(Collection): ?Carbon
```

## Esempio

```php
use App\Modules\Prinect\Contracts\PrinectApiInterface;
use App\Modules\Prinect\Services\PrinectJobsService;
use App\Modules\Prinect\Services\PrinectInkService;
use App\Modules\Prinect\ValueObjects\WorkstepId;

// Iniettare l'interfaccia, mai l'adapter concreto.
$jobs = app(PrinectJobsService::class);
$worksteps = $jobs->getWorkstepsStampa('66811');

$ink  = app(PrinectInkService::class);
$cons = $ink->getConsumo(new WorkstepId('66811', 'WS-001'));
// Float CMYK + spot in grammi
```

## Bind

Il binding `PrinectApiInterface => PrinectHttpAdapter` vive in
`App\Providers\ModulesServiceProvider::register()`. In test si può
sostituire con un fake:

```php
$this->app->bind(PrinectApiInterface::class, FakePrinectApi::class);
```

## Compatibilità (NON eliminare)

- `App\Http\Services\PrinectService`     — wrapper legacy, esiste
- `App\Http\Services\PrinectSyncService` — sync DB legacy, esiste
- `App\Modules\Stampa\Adapters\PrinectAdapter` — wrapper verso
  `StampaIntegrationInterface`, continua a essere il punto di iniezione
  per i Controller.

I tre file sopra restano in produzione. Il modulo nuovo è **additivo**.

## Vincoli operativi

- Config env immutate (`PRINECT_API_URL`, `PRINECT_API_USER`,
  `PRINECT_API_PASS`, `PRINECT_DEVICE_XL106_ID`).
- Cron schedule del queue worker (`CachePrinectInkJob` ogni 2 min)
  invariato.
- Logica auto-termina worksteps preservata bit-for-bit (fix 66811).
- Log shape `Log::info('Prinect sync', [...])` invariata.

## Test

`tests/Unit/Modules/Prinect/PrinectJobsServiceTest.php` mocka
`PrinectApiInterface` per coprire i predicati puri di `stampaConfermata`
e i mapping commessa↔job.
